<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$internship_id = isset($input['internship_id']) ? intval($input['internship_id']) : 0;

if ($internship_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid internship ID']);
    exit;
}

try {
    // Check if already registered
    $check_stmt = $conn->prepare("
        SELECT registration_id 
        FROM user_internship_registrations 
        WHERE user_id = ? AND internship_id = ?
    ");
    $check_stmt->bind_param("ii", $user_id, $internship_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'You have already registered for this internship'
        ]);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
    
    // Register for internship
    $insert_stmt = $conn->prepare("
        INSERT INTO user_internship_registrations (user_id, internship_id, status) 
        VALUES (?, ?, 'registered')
    ");
    $insert_stmt->bind_param("ii", $user_id, $internship_id);
    
    if ($insert_stmt->execute()) {
        // Get internship details for confirmation
        $internship_stmt = $conn->prepare("
            SELECT title, company_name 
            FROM internships 
            WHERE internship_id = ?
        ");
        $internship_stmt->bind_param("i", $internship_id);
        $internship_stmt->execute();
        $internship_result = $internship_stmt->get_result();
        $internship = $internship_result->fetch_assoc();
        $internship_stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully registered for ' . $internship['title'] . ' at ' . $internship['company_name'],
            'internship_title' => $internship['title'],
            'company_name' => $internship['company_name']
        ]);
    } else {
        throw new Exception('Failed to register for internship');
    }
    
    $insert_stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
