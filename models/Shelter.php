<?php
// models/Shelter.php

class Shelter {
    private $shelter_id;
    private $shelter_name;
    private $location;
    private $latitude;
    private $longitude;
    private $contact_number;
    private $type;
    private $current_capacity;
    private $max_capacity;
    private $amenities;
    private $supplies;
    private $distance;
    private $is_full;
    private $is_active;
    private $host_id;
    
    public function __construct($data) {
        $this->shelter_id = $data['shelter_id'] ?? null;
        $this->shelter_name = $data['shelter_name'] ?? '';
        $this->location = $data['location'] ?? '';
        $this->latitude = $data['latitude'] ?? null;
        $this->longitude = $data['longitude'] ?? null;
        $this->contact_number = $data['contact_number'] ?? '';
        $this->type = $data['type'] ?? null;
        $this->current_capacity = $data['current_capacity'] ?? 0;
        $this->max_capacity = $data['max_capacity'] ?? 0;
        $this->amenities = $data['amenities'] ?? '';
        $this->supplies = $data['supplies'] ?? '';
        $this->distance = $data['distance'] ?? null;
        $this->is_full = $data['is_full'] ?? 0;
        $this->is_active = $data['is_active'] ?? 1;
        $this->host_id = $data['host_id'] ?? null;
    }
    
    // Getters
    public function getId() { return $this->shelter_id; }
    public function getName() { return $this->shelter_name; }
    public function getLocation() { return $this->location; }
    public function getLatitude() { return $this->latitude; }
    public function getLongitude() { return $this->longitude; }
    public function getContactNumber() { return $this->contact_number; }
    public function getType() { return $this->type; }
    public function getCurrentCapacity() { return $this->current_capacity; }
    public function getMaxCapacity() { return $this->max_capacity; }
    public function getAmenities() { return $this->amenities; }
    public function getSupplies() { return $this->supplies; }
    public function getDistance() { return $this->distance; }
    public function isFull() { return (bool)$this->is_full; }
    public function isActive() { return (bool)$this->is_active; }
    public function getHostId() { return $this->host_id; }
    
    // Setters
    public function setName($name) { $this->shelter_name = $name; }
    public function setLocation($location) { $this->location = $location; }
    public function setCurrentCapacity($capacity) { $this->current_capacity = $capacity; }
    public function setMaxCapacity($capacity) { $this->max_capacity = $capacity; }
    public function setAmenities($amenities) { $this->amenities = $amenities; }
    public function setSupplies($supplies) { $this->supplies = $supplies; }
    public function setActive($is_active) { $this->is_active = $is_active; }
    
    // Helper methods
    public function getAvailableSpace() {
        return $this->max_capacity - $this->current_capacity;
    }
    
    public function getOccupancyPercentage() {
        if ($this->max_capacity == 0) return 0;
        return round(($this->current_capacity / $this->max_capacity) * 100, 2);
    }
    
    public function getAmenitiesArray() {
        return json_decode($this->amenities, true) ?? [];
    }
    
    public function getSuppliesArray() {
        return json_decode($this->supplies, true) ?? [];
    }
    
    public function toArray() {
        return [
            'shelter_id' => $this->shelter_id,
            'shelter_name' => $this->shelter_name,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'contact_number' => $this->contact_number,
            'type' => $this->type,
            'current_capacity' => $this->current_capacity,
            'max_capacity' => $this->max_capacity,
            'amenities' => $this->amenities,
            'supplies' => $this->supplies,
            'distance' => $this->distance,
            'is_full' => $this->is_full,
            'is_active' => $this->is_active,
            'host_id' => $this->host_id,
        ];
    }
}