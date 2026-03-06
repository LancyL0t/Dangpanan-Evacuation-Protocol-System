<?php
// models/OccupantModel.php

class OccupantModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Check in an evacuee using their approval code
     */
    public function checkIn($approval_code, $shelter_id) {
        try {
            $this->db->beginTransaction();

            // 1. Find the approved request with this code
            $stmt = $this->db->prepare("
                SELECT r.*, u.first_name, u.last_name, u.phone_number 
                FROM requests r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.approval_code = ? 
                AND r.shelter_id = ?
                AND r.status = 'approved'
            ");
            $stmt->execute([$approval_code, $shelter_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Invalid approval code or request not found'
                ];
            }

            // 2. Check if already checked in
            $checkStmt = $this->db->prepare("
                SELECT occupant_id 
                FROM occupants 
                WHERE request_id = ? AND status = 'active'
            ");
            $checkStmt->execute([$request['id']]);
            if ($checkStmt->fetch()) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'This evacuee is already checked in'
                ];
            }

            // 3. Check shelter capacity
            $capacityStmt = $this->db->prepare("
                SELECT current_capacity, max_capacity 
                FROM shelter 
                WHERE shelter_id = ?
            ");
            $capacityStmt->execute([$shelter_id]);
            $shelter = $capacityStmt->fetch(PDO::FETCH_ASSOC);

            if ($shelter['current_capacity'] + $request['group_size'] > $shelter['max_capacity']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Shelter capacity exceeded'
                ];
            }

            // 4. Create occupant record
            $insertStmt = $this->db->prepare("
                INSERT INTO occupants 
                (request_id, user_id, shelter_id, group_size, approval_code, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $insertStmt->execute([
                $request['id'],
                $request['user_id'],
                $shelter_id,
                $request['group_size'],
                $approval_code,
                $request['notes']
            ]);

            // 5. Update request status
            $updateRequestStmt = $this->db->prepare("
                UPDATE requests 
                SET status = 'checked_in' 
                WHERE id = ?
            ");
            $updateRequestStmt->execute([$request['id']]);

            // 6. Update shelter capacity
            $updateCapacityStmt = $this->db->prepare("
                UPDATE shelter 
                SET current_capacity = current_capacity + ? 
                WHERE shelter_id = ?
            ");
            $updateCapacityStmt->execute([$request['group_size'], $shelter_id]);

            // 7. Update is_full flag
            $updateFullStmt = $this->db->prepare("
                UPDATE shelter 
                SET is_full = CASE 
                    WHEN current_capacity >= max_capacity THEN 1 
                    ELSE 0 
                END 
                WHERE shelter_id = ?
            ");
            $updateFullStmt->execute([$shelter_id]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Check-in successful',
                'evacuee' => [
                    'name' => $request['first_name'] . ' ' . $request['last_name'],
                    'group_size' => $request['group_size'],
                    'phone' => $request['phone_number']
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Check-in failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all active occupants for a shelter
     */
    public function getOccupantsByShelter($shelter_id) {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   u.first_name, 
                   u.last_name, 
                   u.email, 
                   u.phone_number,
                   r.notes as request_notes
            FROM occupants o
            JOIN users u ON o.user_id = u.user_id
            JOIN requests r ON o.request_id = r.id
            WHERE o.shelter_id = ? 
            AND o.status = 'active'
            ORDER BY o.checked_in_at DESC
        ");
        $stmt->execute([$shelter_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Remove an occupant (check them out)
     */
    public function removeOccupant($occupant_id, $shelter_id) {
        try {
            $this->db->beginTransaction();

            // 1. Get occupant details
            $stmt = $this->db->prepare("
                SELECT * FROM occupants 
                WHERE occupant_id = ? 
                AND shelter_id = ? 
                AND status = 'active'
            ");
            $stmt->execute([$occupant_id, $shelter_id]);
            $occupant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$occupant) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Occupant not found or already removed'
                ];
            }

            // 2. Update occupant status
            $updateStmt = $this->db->prepare("
                UPDATE occupants 
                SET status = 'removed', 
                    checked_out_at = NOW() 
                WHERE occupant_id = ?
            ");
            $updateStmt->execute([$occupant_id]);

            // 2b. Update the linked request status to 'completed' so the evacuee can request again
            $updateRequestStmt = $this->db->prepare("
                UPDATE requests 
                SET status = 'completed' 
                WHERE id = ?
            ");
            $updateRequestStmt->execute([$occupant['request_id']]);

            // 3. Decrease shelter capacity
            $capacityStmt = $this->db->prepare("
                UPDATE shelter 
                SET current_capacity = GREATEST(0, current_capacity - ?) 
                WHERE shelter_id = ?
            ");
            $capacityStmt->execute([$occupant['group_size'], $shelter_id]);

            // 4. Update is_full flag
            $updateFullStmt = $this->db->prepare("
                UPDATE shelter 
                SET is_full = CASE 
                    WHEN current_capacity >= max_capacity THEN 1 
                    ELSE 0 
                END 
                WHERE shelter_id = ?
            ");
            $updateFullStmt->execute([$shelter_id]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Occupant removed successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to remove occupant: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get occupant count for a shelter
     */
    public function getOccupantCount($shelter_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count, 
                   SUM(group_size) as total_people
            FROM occupants 
            WHERE shelter_id = ? 
            AND status = 'active'
        ");
        $stmt->execute([$shelter_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verify approval code exists and get details
     */
    public function verifyApprovalCode($approval_code, $shelter_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   u.first_name, 
                   u.last_name, 
                   u.phone_number,
                   s.shelter_name
            FROM requests r
            JOIN users u ON r.user_id = u.user_id
            JOIN shelter s ON r.shelter_id = s.shelter_id
            WHERE r.approval_code = ? 
            AND r.shelter_id = ?
            AND r.status = 'approved'
        ");
        $stmt->execute([$approval_code, $shelter_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            return [
                'success' => false,
                'message' => 'Invalid approval code'
            ];
        }

        // Check if already checked in
        $checkStmt = $this->db->prepare("
            SELECT occupant_id 
            FROM occupants 
            WHERE request_id = ? AND status = 'active'
        ");
        $checkStmt->execute([$request['id']]);
        
        if ($checkStmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Already checked in',
                'already_checked_in' => true
            ];
        }

        return [
            'success' => true,
            'evacuee' => [
                'name' => $request['first_name'] . ' ' . $request['last_name'],
                'phone' => $request['phone_number'],
                'group_size' => $request['group_size'],
                'notes' => $request['notes']
            ]
        ];
    }

    /**
     * Evacuee self-checkout: marks occupant as checked_out and resets request to 'completed'
     */
    public function checkOut($user_id) {
        try {
            $this->db->beginTransaction();

            // 1. Find the active occupant record for this user
            $stmt = $this->db->prepare("
                SELECT o.*, r.id as req_id
                FROM occupants o
                JOIN requests r ON o.request_id = r.id
                WHERE o.user_id = ? AND o.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $occupant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$occupant) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'No active check-in found'];
            }

            // 2. Mark occupant as checked_out
            $this->db->prepare("
                UPDATE occupants 
                SET status = 'checked_out', checked_out_at = NOW() 
                WHERE occupant_id = ?
            ")->execute([$occupant['occupant_id']]);

            // 3. Mark request as completed so evacuee can request again
            $this->db->prepare("
                UPDATE requests SET status = 'completed' WHERE id = ?
            ")->execute([$occupant['req_id']]);

            // 4. Decrease shelter capacity
            $this->db->prepare("
                UPDATE shelter 
                SET current_capacity = GREATEST(0, current_capacity - ?) 
                WHERE shelter_id = ?
            ")->execute([$occupant['group_size'], $occupant['shelter_id']]);

            // 5. Update is_full flag
            $this->db->prepare("
                UPDATE shelter 
                SET is_full = CASE 
                    WHEN current_capacity >= max_capacity THEN 1 
                    ELSE 0 
                END 
                WHERE shelter_id = ?
            ")->execute([$occupant['shelter_id']]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Checked out successfully'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Check-out failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get check-in history for a shelter
     */
    public function getCheckInHistory($shelter_id, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   u.first_name, 
                   u.last_name,
                   u.phone_number
            FROM occupants o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.shelter_id = ?
            ORDER BY o.checked_in_at DESC
            LIMIT ?
        ");
        $stmt->execute([$shelter_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
