<?php
// models/ShelterModel.php

require_once 'models/Shelter.php';

class ShelterModel {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all shelters (for public/evacuees)
     */
    public function getAllShelters() {
        $stmt = $this->db->prepare("SELECT * FROM shelter WHERE is_active = 1");
        $stmt->execute();
        $shelters = [];
        
        while ($row = $stmt->fetch()) {
            $shelters[] = new Shelter($row);
        }
        
        return $shelters;
    }

    /**
     * Get all shelters for admin (including inactive)
     */
    /**
 * Fetches all shelters including host information for the Admin Dashboard
 */
public function getAllForAdmin() {
    // The table name MUST be 'shelter' (singular) to match your DB
    $stmt = $this->db->prepare("
        SELECT s.*, u.first_name, u.last_name, u.email as host_email
        FROM shelter s
        LEFT JOIN users u ON s.host_id = u.user_id
        ORDER BY s.shelter_id DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Get shelter by ID
     */
    public function getShelterById($shelter_id) {
        $stmt = $this->db->prepare("SELECT * FROM shelter WHERE shelter_id = ?");
        $stmt->execute([$shelter_id]);
        $data = $stmt->fetch();
        
        return $data ? new Shelter($data) : null;
    }
    
    /**
     * Get shelter by host ID
     */
    public function getShelterByHostId($host_id) {
        $stmt = $this->db->prepare("SELECT * FROM shelter WHERE host_id = ?");
        $stmt->execute([$host_id]);
        $data = $stmt->fetch();
        
        return $data ? new Shelter($data) : null;
    }
    
    /**
     * Create new shelter
     */
    public function create($data) {
        $sql = "INSERT INTO shelter (
            host_id, shelter_name, location, latitude, longitude, 
            contact_number, max_capacity, current_capacity, 
            amenities, supplies, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['host_id'],
            $data['shelter_name'],
            $data['location'],
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['contact_number'],
            $data['max_capacity'],
            $data['amenities'],
            $data['supplies']
        ]);
    }
    
    /**
     * Update shelter by ID (admin)
     */
    public function updateById($shelter_id, $data) {
        // Build dynamic SQL based on what fields are provided
        $fields = [];
        $values = [];

        if (isset($data['shelter_name'])) {
            $fields[] = "shelter_name = ?";
            $values[] = $data['shelter_name'];
        }
        if (isset($data['location'])) {
            $fields[] = "location = ?";
            $values[] = $data['location'];
        }
        if (isset($data['latitude'])) {
            $fields[] = "latitude = ?";
            $values[] = $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $fields[] = "longitude = ?";
            $values[] = $data['longitude'];
        }
        if (isset($data['contact_number'])) {
            $fields[] = "contact_number = ?";
            $values[] = $data['contact_number'];
        }
        if (isset($data['max_capacity'])) {
            $fields[] = "max_capacity = ?";
            $values[] = $data['max_capacity'];
        }
        if (isset($data['current_capacity'])) {
            $fields[] = "current_capacity = ?";
            $values[] = $data['current_capacity'];
        }
        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $values[] = $data['is_active'];
        }
        if (isset($data['amenities'])) {
            $fields[] = "amenities = ?";
            $values[] = is_array($data['amenities']) ? json_encode($data['amenities']) : $data['amenities'];
        }
        if (isset($data['supplies'])) {
            $fields[] = "supplies = ?";
            $values[] = is_array($data['supplies']) ? json_encode($data['supplies']) : $data['supplies'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $shelter_id;
        $sql = "UPDATE shelter SET " . implode(", ", $fields) . " WHERE shelter_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Update shelter by host ID
     */
    public function updateShelter($host_id, $data) {
        $sql = "UPDATE shelter SET 
                shelter_name = ?, 
                location = ?, 
                latitude = ?, 
                longitude = ?,
                contact_number = ?, 
                max_capacity = ?,
                amenities = ?,
                supplies = ?
                WHERE host_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['shelter_name'],
            $data['location'],
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['contact_number'],
            $data['max_capacity'],
            $data['amenities'],
            $data['supplies'],
            $host_id
        ]);
    }
    
    /**
     * Update shelter settings
     */
    public function updateSettings($host_id, $data) {
        $sql = "UPDATE shelter SET 
                shelter_name = ?, 
                max_capacity = ?,
                contact_number = ?,
                location = ?,
                amenities = ?
                WHERE host_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['shelter_name'],
            $data['max_capacity'],
            $data['contact_number'],
            $data['location'],
            json_encode($data['amenities'] ?? []),
            $host_id
        ]);
    }
    
    /**
     * Update shelter stock/supplies
     */
    public function updateSupplies($host_id, $supplies) {
        $stmt = $this->db->prepare("UPDATE shelter SET supplies = ? WHERE host_id = ?");
        return $stmt->execute([json_encode($supplies), $host_id]);
    }
    
    /**
     * Toggle shelter active status
     */
    public function toggleStatus($shelter_id, $is_active) {
        $stmt = $this->db->prepare("UPDATE shelter SET is_active = ? WHERE shelter_id = ?");
        return $stmt->execute([$is_active ? 1 : 0, $shelter_id]);
    }
    
    /**
     * Update capacity and check if full
     */
    public function updateCapacity($shelter_id, $amount) {
        $stmt = $this->db->prepare("
            UPDATE shelter 
            SET current_capacity = current_capacity + ? 
            WHERE shelter_id = ?
        ");
        $stmt->execute([$amount, $shelter_id]);
        
        // Check if full
        $checkFull = $this->db->prepare("
            UPDATE shelter 
            SET is_full = CASE 
                WHEN current_capacity >= max_capacity THEN 1 
                ELSE 0 
            END 
            WHERE shelter_id = ?
        ");
        return $checkFull->execute([$shelter_id]);
    }
    
    /**
     * Check if shelter exists for host
     */
    public function shelterExists($host_id) {
        $stmt = $this->db->prepare("SELECT shelter_id FROM shelter WHERE host_id = ?");
        $stmt->execute([$host_id]);
        return $stmt->fetch() ? true : false;
    }

    /**
     * Delete shelter (admin)
     */
    public function delete($shelter_id) {
        $stmt = $this->db->prepare("DELETE FROM shelter WHERE shelter_id = ?");
        return $stmt->execute([$shelter_id]);
    }

    /**
     * Get shelter statistics
     */
    public function getStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_shelters,
                SUM(max_capacity) as total_capacity,
                SUM(current_capacity) as total_occupancy,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_shelters
            FROM shelter
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}