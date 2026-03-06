<?php
// models/User.php
class User {
    private $user_id;
    private $first_name;
    private $middle_initial;
    private $last_name;
    private $email;
    private $password_hash;
    private $phone_number;
    private $role;
    private $host_status;
    private $is_verified;
    private $created_at;

    public function __construct($data) {
        $this->user_id        = $data['user_id'];
        $this->first_name     = $data['first_name'];
        $this->middle_initial = $data['middle_initial'] ?? '';
        $this->last_name      = $data['last_name'];
        $this->email          = $data['email'];
        $this->password_hash  = $data['password_hash'];
        $this->phone_number   = $data['phone_number'] ?? '';
        $this->role           = $data['role'];
        $this->host_status    = $data['host_status']  ?? 'none';
        $this->is_verified    = $data['is_verified']  ?? 0;
        $this->created_at     = $data['created_at']   ?? null;
    }

    // Getters
    public function getId()            { return $this->user_id; }
    public function getFirstName()     { return $this->first_name; }
    public function getMiddleInitial() { return $this->middle_initial; }
    public function getLastName()      { return $this->last_name; }
    public function getFullName() {
        $mi = $this->middle_initial ? ' ' . $this->middle_initial . ' ' : ' ';
        return trim($this->first_name . $mi . $this->last_name);
    }
    public function getEmail()        { return $this->email; }
    public function getPhoneNumber()  { return $this->phone_number; }
    public function getRole()         { return $this->role; }
    public function getHostStatus()   { return $this->host_status; }
    public function getIsVerified()   { return (bool)$this->is_verified; }
    public function getCreatedAt()    { return $this->created_at; }
    public function getPasswordHash() { return $this->password_hash; }

    public function isActiveHost() {
        return $this->host_status === 'active_host';
    }

    public function canRequestShelter() {
        return $this->host_status !== 'active_host';
    }
}
