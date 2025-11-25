<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // First, check if the required columns exist
    $columns_check = $conn->query("SHOW COLUMNS FROM user_details LIKE 'domain'");
    if ($columns_check->num_rows === 0) {
        echo json_encode([
            'error' => 'Database structure issue',
            'message' => 'Please contact administrator. Missing required profile fields.',
            'technical_details' => 'user_details table is missing required columns: domain, cgpa, total_experience, certifications'
        ]);
        exit;
    }
    
    // Fetch user profile data from database
    $stmt = $conn->prepare("
        SELECT 
            ud.domain,
            ud.cgpa,
            ud.total_experience as experience_years,
            ud.certifications
        FROM user_details ud
        WHERE ud.user_id = ?
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'error' => 'Profile not completed',
            'message' => 'Please complete your profile first with domain, CGPA, experience, and certifications'
        ]);
        exit;
    }
    
    $user_profile = $result->fetch_assoc();
    $stmt->close();
    
    // Validate that required fields have values
    if (empty($user_profile['domain']) || is_null($user_profile['cgpa'])) {
        echo json_encode([
            'error' => 'Incomplete profile data',
            'message' => 'Please update your profile with domain and CGPA information'
        ]);
        exit;
    }
    
    // Prepare data for Flask API
    $student_data = [
        'domain' => $user_profile['domain'] ?? 'General',
        'cgpa' => floatval($user_profile['cgpa'] ?? 0),
        'experience_years' => floatval($user_profile['experience_years'] ?? 0),
        'certifications' => intval($user_profile['certifications'] ?? 0)
    ];
    
    // Call Flask API
    $flask_url = 'http://localhost:5000/recommend';
    
    $ch = curl_init($flask_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($student_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($student_data))
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        echo json_encode([
            'error' => 'Cannot connect to AI recommendation service',
            'message' => 'Please make sure the Flask API server is running on http://localhost:5000',
            'technical_details' => $curl_error
        ]);
        exit;
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        echo json_encode([
            'error' => 'AI service error',
            'message' => 'Recommendation service returned an error',
            'http_code' => $http_code,
            'response' => $response
        ]);
        exit;
    }
    
    // Return the recommendations
    echo $response;
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'System error',
        'message' => $e->getMessage(),
        'recommendations' => []
    ]);
}

$conn->close();
?>
