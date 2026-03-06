<?php
// api/get_shelters.php
// This endpoint returns shelter data with coordinates for map display

header('Content-Type: application/json');
require_once '../config/db.php';

try {
    // Fetch all active shelters with their location data
    $stmt = $pdo->prepare("
        SELECT 
            shelter_id,
            shelter_name,
            location,
            latitude,
            longitude,
            contact_number,
            max_capacity,
            current_capacity,
            is_full,
            is_active,
            amenities,
            supplies
        FROM shelter 
        WHERE is_active = 1
        ORDER BY shelter_name ASC
    ");
    
    $stmt->execute();
    $shelters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each shelter to add useful computed data
    foreach ($shelters as &$shelter) {
        // Calculate capacity percentage
        $shelter['capacity_percentage'] = $shelter['max_capacity'] > 0 
            ? round(($shelter['current_capacity'] / $shelter['max_capacity']) * 100) 
            : 0;
        
        // Determine status
        if ($shelter['is_full']) {
            $shelter['status'] = 'full';
        } elseif ($shelter['capacity_percentage'] >= 80) {
            $shelter['status'] = 'limited';
        } else {
            $shelter['status'] = 'available';
        }
        
        // Decode JSON fields
        if ($shelter['amenities']) {
            $shelter['amenities'] = json_decode($shelter['amenities'], true);
        }
        if ($shelter['supplies']) {
            $shelter['supplies'] = json_decode($shelter['supplies'], true);
        }
    }
    
    echo json_encode([
        'success' => true,
        'shelters' => $shelters
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>