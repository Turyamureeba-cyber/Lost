<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$term = $_GET['term'] ?? '';

try {
    // Search across regions, districts, and counties
    $query = "(SELECT region_name as label, region_id as value, 'region' as type FROM region WHERE region_name LIKE ?)
              UNION
              (SELECT district_name as label, district_id as value, 'district' as type FROM district WHERE district_name LIKE ?)
              UNION
              (SELECT county_name as label, county_id as value, 'county' as type FROM county WHERE county_name LIKE ?)
              LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $searchTerm = "%$term%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    
    $results = $stmt->fetchAll();
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    echo json_encode([]);
}