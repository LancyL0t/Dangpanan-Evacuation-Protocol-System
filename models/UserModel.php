<?php
// models/UserModel.php

require_once 'User.php';

class UserModel {
    private $db;

    public function __construct($db_conn) {
        $this->db = $db_conn;
    }

    /**
     * Expose the DB connection for controller-level queries
     */
    public function getDb() {
        return $this->db;
    }

    // Check if email is already taken
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ? true : false;
    }

    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch();
        
        return $data ? new User($data) : false;
    }

    // Register a new user
    public function register($data) {
        $sql = "INSERT INTO users (first_name, middle_initial, last_name, email, password_hash, phone_number, gov_id_url, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['first_name'],
            $data['middle_initial'] ?? '',
            $data['last_name'],
            $data['email'],
            $hashedPassword,
            $data['phone_number'],
            $data['gov_id_url'],
            $data['role']
        ]);
    }

    // ============ ADMIN CRUD METHODS ============

    /**
     * Get all users
     */
    public function getAll() {
        $stmt = $this->db->prepare("SELECT user_id, first_name, middle_initial, last_name, email, phone_number, role, gov_id_url, is_verified, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by ID
     */
    public function getById($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetch();
        
        return $data ? new User($data) : null;
    }

    /**
     * Update user
     */
    public function update($user_id, $data) {
        // Build dynamic SQL based on what fields are provided
        $fields = [];
        $values = [];

        if (isset($data['first_name'])) {
            $fields[] = "first_name = ?";
            $values[] = $data['first_name'];
        }
        if (isset($data['middle_initial'])) {
            $fields[] = "middle_initial = ?";
            $values[] = $data['middle_initial'];
        }
        if (isset($data['last_name'])) {
            $fields[] = "last_name = ?";
            $values[] = $data['last_name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $values[] = $data['email'];
        }
        if (isset($data['phone_number'])) {
            $fields[] = "phone_number = ?";
            $values[] = $data['phone_number'];
        }
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $values[] = $data['role'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (isset($data['is_verified'])) {
            $fields[] = "is_verified = ?";
            $values[] = (int)$data['is_verified'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $user_id;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Toggle is_verified for a user
     */
    public function toggleVerify($user_id) {
        $stmt = $this->db->prepare("UPDATE users SET is_verified = NOT is_verified WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    /**
     * Delete user
     */
    public function delete($user_id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    /**
     * Get user count by role
     */
    public function getCountByRole($role) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE role = ?");
        $stmt->execute([$role]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Get total user count
     */
    public function getTotalCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }

    // ============ HOST STATUS MANAGEMENT ============

    /**
     * Update user's host status
     * @param int $user_id
     * @param string $status 'none', 'active_host', or 'relinquished'
     * @return bool
     */
    public function updateHostStatus($user_id, $status) {
        $validStatuses = ['none', 'active_host', 'relinquished'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE users SET host_status = ? WHERE user_id = ?");
        return $stmt->execute([$status, $user_id]);
    }

    /**
     * Relinquish host status for a user
     * This also deactivates their shelter
     * @param int $user_id
     * @return bool
     */
    public function relinquishHostStatus($user_id) {
        try {
            // Begin transaction
            $this->db->beginTransaction();

            // Update user status to relinquished
            $stmt = $this->db->prepare("UPDATE users SET host_status = 'relinquished' WHERE user_id = ?");
            $stmt->execute([$user_id]);

            // Deactivate the user's shelter
            $stmt = $this->db->prepare("UPDATE shelter SET is_active = 0 WHERE host_id = ?");
            $stmt->execute([$user_id]);

            // Commit transaction
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get host status for a user
     * @param int $user_id
     * @return string|null
     */
    public function getHostStatus($user_id) {
        $stmt = $this->db->prepare("SELECT host_status FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['host_status'] : null;
    }

    /**
     * Check if user can request shelter
     * @param int $user_id
     * @return bool
     */
    public function canRequestShelter($user_id) {
        $status = $this->getHostStatus($user_id);
        return $status !== 'active_host';
    }

    /**
     * Restore host status (for future enhancement)
     * @param int $user_id
     * @return bool
     */
  public function restoreHostStatus($user_id) {
    try {
        $this->db->beginTransaction();

        // Update user status to active_host
        $stmt1 = $this->db->prepare("UPDATE users SET host_status = 'active_host' WHERE user_id = ?");
        $stmt1->execute([$user_id]);

        // Reactivate the shelter
        $stmt2 = $this->db->prepare("UPDATE shelter SET is_active = 1 WHERE host_id = ?");
        $stmt2->execute([$user_id]);

        $this->db->commit();
        return true;
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Restore Host Error: " . $e->getMessage());
        return false;
    }
}
}