<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    $query = "
        SELECT 
            internship_id,
            title,
            company_name,
            description,
            required_domain,
            min_cgpa,
            required_experience,
            location,
            duration_months,
            stipend,
            application_deadline
        FROM internships
        WHERE is_active = 1
        ORDER BY created_at DESC
    ";
    
    $result = $conn->query($query);
    
    $internships = [];
    while ($row = $result->fetch_assoc()) {
        $internships[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($internships),
        'internships' => $internships
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'internships' => []
    ]);
}

$conn->close();
?>
