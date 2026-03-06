<?php
// models/RequestEntity.php

class RequestEntity {
    private $id;
    private $user_id;
    private $shelter_id;
    private $group_size;
    private $notes;
    private $status;
    private $is_notified;
    private $created_at;
    
    // Additional joined data
    private $user_name;
    private $user_email;
    private $user_phone;
    private $shelter_name;
    
    public function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->shelter_id = $data['shelter_id'] ?? null;
        $this->group_size = $data['group_size'] ?? 1;
        $this->notes = $data['notes'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->is_notified = $data['is_notified'] ?? 0;
        $this->created_at = $data['created_at'] ?? null;
        
        // Joined data
        $this->user_name = ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '');
        $this->user_email = $data['email'] ?? '';
        $this->user_phone = $data['phone_number'] ?? '';
        $this->shelter_name = $data['shelter_name'] ?? '';
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->user_id; }
    public function getShelterId() { return $this->shelter_id; }
    public function getGroupSize() { return $this->group_size; }
    public function getNotes() { return $this->notes; }
    public function getStatus() { return $this->status; }
    public function isNotified() { return (bool)$this->is_notified; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUserName() { return $this->user_name; }
    public function getUserEmail() { return $this->user_email; }
    public function getUserPhone() { return $this->user_phone; }
    public function getShelterName() { return $this->shelter_name; }
    
    // Status checkers
    public function isPending() { return $this->status === 'pending'; }
    public function isApproved() { return $this->status === 'approved'; }
    public function isDeclined() { return $this->status === 'declined'; }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'shelter_id' => $this->shelter_id,
            'group_size' => $this->group_size,
            'notes' => $this->notes,
            'status' => $this->status,
            'is_notified' => $this->is_notified,
            'created_at' => $this->created_at,
            'user_name' => $this->user_name,
            'user_email' => $this->user_email,
            'user_phone' => $this->user_phone,
            'shelter_name' => $this->shelter_name,
        ];
    }
}