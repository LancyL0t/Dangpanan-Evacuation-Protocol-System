<?php
// models/RequestModel.php

require_once 'models/RequestEntity.php';
class RequestModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create new request
     */
    public function createRequest($data) {
        $sql = "INSERT INTO requests (user_id, shelter_id, group_size, notes, status, is_notified) 
                VALUES (?, ?, ?, ?, 'pending', 0)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['user_id'],
            $data['shelter_id'],
            $data['group_size'],
            $data['notes']
        ]);
    }
    
    /**
     * Get pending requests for a shelter
     */
    public function getPendingRequests($shelter_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.first_name, u.last_name, u.email, u.phone_number 
            FROM requests r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.shelter_id = ? AND r.status = 'pending'
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$shelter_id]);
        
        $requests = [];
        while ($row = $stmt->fetch()) {
            $requests[] = new RequestEntity($row);
        }
        
        return $requests;
    }
    
    /**
     * Get request by ID
     */
    public function getRequestById($request_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.first_name, u.last_name, u.email, u.phone_number 
            FROM requests r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.id = ?
        ");
        $stmt->execute([$request_id]);
        $data = $stmt->fetch();
        
        return $data ? new RequestEntity($data) : null;
    }
    
    /**
     * Approve request with approval code generation
     */
    public function approveRequest($request_id) {
        // Generate unique approval code
        $approval_code = $this->generateApprovalCode($request_id);
        
        $stmt = $this->db->prepare("
            UPDATE requests 
            SET status = 'approved', 
                approval_code = ?,
                approved_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$approval_code, $request_id]);
    }
    
    /**
     * Generate unique 6-digit approval code
     */
    private function generateApprovalCode($request_id) {
        // Format: exactly 6 digits (100000 - 999999)
        return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Decline request with optional reason
     */
    public function declineRequest($request_id, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE requests 
            SET status = 'declined',
                rejection_reason = ?,
                reviewed_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$reason, $request_id]);
    }
    
    /**
     * Mark request as notified
     */
    public function markAsNotified($request_id) {
        $stmt = $this->db->prepare("UPDATE requests SET is_notified = 1 WHERE id = ?");
        return $stmt->execute([$request_id]);
    }
    
    /**
     * Check for unnotified approved requests
     */
    public function getUnnotifiedApprovals($user_id) {
        $stmt = $this->db->prepare("
            SELECT r.id, s.shelter_name 
            FROM requests r 
            JOIN shelter s ON r.shelter_id = s.shelter_id 
            WHERE r.user_id = ? AND r.status = 'approved' AND r.is_notified = 0
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    public function getApprovedRequests($shelter_id) {
    $stmt = $this->db->prepare("
        SELECT r.*, u.first_name, u.last_name 
        FROM shelter_requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.shelter_id = ? AND r.status = 'Approved'
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$shelter_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Get user's active requests with shelter details
     */
    public function getUserActiveRequests($user_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   s.shelter_name, 
                   s.location, 
                   s.latitude, 
                   s.longitude,
                   s.contact_number,
                   r.approval_code,
                   r.reviewed_at,
                   r.approved_at,
                   r.rejection_reason
            FROM requests r 
            JOIN shelter s ON r.shelter_id = s.shelter_id 
            WHERE r.user_id = ? AND r.status IN ('pending', 'approved', 'declined')
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get specific request with full details
     */
    public function getRequestWithDetails($request_id, $user_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   s.shelter_name, 
                   s.location, 
                   s.latitude, 
                   s.longitude,
                   s.contact_number,
                   s.type,
                   r.approval_code,
                   r.reviewed_at,
                   r.approved_at,
                   r.rejection_reason,
                   r.created_at
            FROM requests r 
            JOIN shelter s ON r.shelter_id = s.shelter_id 
            WHERE r.id = ? AND r.user_id = ?
        ");
        $stmt->execute([$request_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user has any pending/approved requests
     */
    public function hasActiveRequest($user_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM requests 
            WHERE user_id = ? AND status IN ('pending', 'approved')
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Cancel (delete) a request
     */
    public function cancelRequest($request_id, $user_id) {
        $stmt = $this->db->prepare("
            DELETE FROM requests 
            WHERE id = ? AND user_id = ? AND status IN ('pending', 'approved')
        ");
        return $stmt->execute([$request_id, $user_id]);
    }

    /**
     * Mark request as under review (when host views it)
     */
    public function markAsReviewing($request_id) {
        $stmt = $this->db->prepare("
            UPDATE requests 
            SET reviewed_at = NOW()
            WHERE id = ? AND reviewed_at IS NULL
        ");
        return $stmt->execute([$request_id]);
    }

    /**
     * Get request status with progress information
     */
    public function getRequestProgress($request_id, $user_id) {
        $stmt = $this->db->prepare("
            SELECT r.*,
                   s.shelter_name,
                   s.location,
                   s.latitude,
                   s.longitude,
                   s.contact_number,
                   CASE 
                       WHEN r.status = 'declined' THEN 'rejected'
                       WHEN r.status = 'approved' THEN 'approved'
                       WHEN r.reviewed_at IS NOT NULL THEN 'reviewing'
                       ELSE 'submitted'
                   END as progress_stage
            FROM requests r
            JOIN shelter s ON r.shelter_id = s.shelter_id
            WHERE r.id = ? AND r.user_id = ?
        ");
        $stmt->execute([$request_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}