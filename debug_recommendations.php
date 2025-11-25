<?php
session_start();
require_once 'db_connect.php';

echo "<h2>🔍 Recommendation System Debug</h2>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f4f4f4; padding: 15px; border: 1px solid #ddd; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
</style>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p class='error'>❌ User is NOT logged in</p>";
    echo "<p>Please <a href='login.php'>login</a> first</p>";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<p class='success'>✓ User is logged in (ID: $user_id)</p>";

echo "<hr><h3>1️⃣ Checking user_details Table Structure</h3>";

// Check table structure
$columns = $conn->query("DESCRIBE user_details");
echo "<table>";
echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Default</th></tr>";
$existing_columns = [];
while ($col = $columns->fetch_assoc()) {
    $existing_columns[] = $col['Field'];
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check required columns
$required = ['domain', 'cgpa', 'total_experience', 'certifications'];
$missing = array_diff($required, $existing_columns);

if (empty($missing)) {
    echo "<p class='success'>✓ All required columns exist</p>";
} else {
    echo "<p class='error'>❌ Missing columns: " . implode(', ', $missing) . "</p>";
    echo "<p>Run this SQL to fix:</p>";
    echo "<pre>ALTER TABLE user_details\n";
    echo "ADD COLUMN domain VARCHAR(100) DEFAULT 'General',\n";
    echo "ADD COLUMN cgpa DECIMAL(3,2) DEFAULT 0.00,\n";
    echo "ADD COLUMN total_experience DECIMAL(3,1) DEFAULT 0.0,\n";
    echo "ADD COLUMN certifications INT DEFAULT 0;</pre>";
}

echo "<hr><h3>2️⃣ Checking Your Profile Data</h3>";

// Fetch user profile
$stmt = $conn->prepare("
    SELECT 
        ud.user_id,
        ud.domain,
        ud.cgpa,
        ud.total_experience,
        ud.certifications
    FROM user_details ud
    WHERE ud.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='error'>❌ No profile data found in user_details table</p>";
    echo "<p>Please complete your profile through the application form</p>";
} else {
    $profile = $result->fetch_assoc();
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>";
    
    $fields = ['domain', 'cgpa', 'total_experience', 'certifications'];
    $all_filled = true;
    
    foreach ($fields as $field) {
        $value = $profile[$field] ?? 'NULL';
        $is_empty = (is_null($value) || $value === '' || $value === 0);
        
        if ($is_empty && in_array($field, ['domain', 'cgpa'])) {
            $all_filled = false;
        }
        
        echo "<tr>";
        echo "<td><strong>$field</strong></td>";
        echo "<td>" . ($value ?: '<em>Empty</em>') . "</td>";
        echo "<td>" . ($is_empty ? "⚠️ Empty" : "✓ OK") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($all_filled) {
        echo "<p class='success'>✓ Profile has all required data</p>";
        
        // Prepare data for Flask
        $student_data = [
            'domain' => $profile['domain'] ?? 'General',
            'cgpa' => floatval($profile['cgpa'] ?? 0),
            'experience_years' => floatval($profile['total_experience'] ?? 0),
            'certifications' => intval($profile['certifications'] ?? 0)
        ];
        
        echo "<p><strong>Data that will be sent to Flask API:</strong></p>";
        echo "<pre>" . json_encode($student_data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p class='error'>❌ Profile is incomplete. Please update domain and CGPA.</p>";
    }
}
$stmt->close();

echo "<hr><h3>3️⃣ Checking Internships Table</h3>";

// Check if internships table exists
$table_check = $conn->query("SHOW TABLES LIKE 'internships'");
if ($table_check->num_rows === 0) {
    echo "<p class='error'>❌ Internships table does NOT exist</p>";
    echo "<p>Please run the CREATE TABLE SQL provided earlier</p>";
} else {
    echo "<p class='success'>✓ Internships table exists</p>";
    
    // Count internships
    $count_result = $conn->query("SELECT COUNT(*) as total FROM internships WHERE is_active = 1");
    $count = $count_result->fetch_assoc()['total'];
    
    echo "<p><strong>Total active internships:</strong> $count</p>";
    
    if ($count == 0) {
        echo "<p class='error'>❌ No internships in the database</p>";
        echo "<p>Please run the INSERT SQL to add sample internships</p>";
    } else {
        echo "<p class='success'>✓ Internships are available</p>";
        
        // Show sample internships
        echo "<h4>Sample Internships (first 3):</h4>";
        $sample = $conn->query("SELECT title, company_name, required_domain, min_cgpa FROM internships LIMIT 3");
        echo "<table>";
        echo "<tr><th>Title</th><th>Company</th><th>Domain</th><th>Min CGPA</th></tr>";
        while ($row = $sample->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['title']}</td>";
            echo "<td>{$row['company_name']}</td>";
            echo "<td>{$row['required_domain']}</td>";
            echo "<td>{$row['min_cgpa']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<hr><h3>4️⃣ Testing Flask API Connection</h3>";

// Test Flask health endpoint
$flask_health = @file_get_contents('http://localhost:5000/health');
if ($flask_health === false) {
    echo "<p class='error'>❌ Cannot connect to Flask API at http://localhost:5000</p>";
    echo "<p>Make sure Flask server is running: <code>python recommendation_api.py</code></p>";
} else {
    echo "<p class='success'>✓ Flask API is running</p>";
    echo "<pre>" . $flask_health . "</pre>";
    
    $health_data = json_decode($flask_health, true);
    if (isset($health_data['internships_loaded'])) {
        $flask_internships = $health_data['internships_loaded'];
        echo "<p><strong>Internships loaded in Flask:</strong> $flask_internships</p>";
        
        if ($flask_internships == 0) {
            echo "<p class='error'>❌ Flask API has loaded 0 internships</p>";
            echo "<p><strong>Solution:</strong> Restart Flask API after adding internships to database</p>";
        } else {
            echo "<p class='success'>✓ Flask API has loaded $flask_internships internships</p>";
        }
    }
}

echo "<hr><h3>5️⃣ Testing Recommendation API</h3>";

if ($all_filled && isset($student_data)) {
    echo "<p>Calling Flask API with your profile data...</p>";
    
    $ch = curl_init('http://localhost:5000/recommend');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($student_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Status Code:</strong> $http_code</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . $response . "</pre>";
    
    $response_data = json_decode($response, true);
    
    if (isset($response_data['recommendations'])) {
        $rec_count = count($response_data['recommendations']);
        if ($rec_count > 0) {
            echo "<p class='success'>✓ Got $rec_count recommendations!</p>";
        } else {
            echo "<p class='error'>❌ No recommendations returned (empty array)</p>";
            echo "<p><strong>Possible reasons:</strong></p>";
            echo "<ul>";
            echo "<li>Your CGPA/Experience doesn't meet minimum requirements</li>";
            echo "<li>Your domain doesn't match any internships</li>";
            echo "<li>Flask API filter is too strict</li>";
            echo "</ul>";
        }
    }
}

echo "<hr><h3>✅ Summary & Next Steps</h3>";

$issues = [];
if (empty($missing) === false) $issues[] = "Add missing columns to user_details";
if (!isset($all_filled) || !$all_filled) $issues[] = "Complete your profile with domain and CGPA";
if (isset($count) && $count == 0) $issues[] = "Add internships to database";
if ($flask_health === false) $issues[] = "Start Flask API server";
if (isset($flask_internships) && $flask_internships == 0) $issues[] = "Restart Flask API after adding internships";

if (empty($issues)) {
    echo "<p class='success' style='font-size: 1.2em;'>🎉 Everything looks good! Recommendations should work now.</p>";
} else {
    echo "<p class='error' style='font-size: 1.2em;'>Found " . count($issues) . " issue(s) to fix:</p>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ol>";
}

$conn->close();
?>
