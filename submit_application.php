<?php
session_start();
include('db_connect.php');

// 1. CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = "";
$message_type = "success"; // "success" or "error"

// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. DEFINE UPLOAD DIRECTORY
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $final_resume_path = null;

    // 3. FETCH EXISTING RESUME PATH (if user is just updating)
    $stmt_select = $conn->prepare("SELECT resume_path FROM user_details WHERE user_id = ?");
    $stmt_select->bind_param("i", $user_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    if ($result->num_rows > 0) {
        $existing_data = $result->fetch_assoc();
        $final_resume_path = $existing_data['resume_path']; // Keep old path by default
    }
    $stmt_select->close();

    // 4. HANDLE NEW FILE UPLOAD
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['resume'];
        $file_type = mime_content_type($file['tmp_name']);
        $file_size = $file['size'];

        // Validation: 5MB limit and must be PDF
        if ($file_size > 5 * 1024 * 1024) { // 5 MB
            $message = "Error: File is larger than 5MB.";
            $message_type = "error";
        } elseif ($file_type != "application/pdf") {
            $message = "Error: File must be a PDF.";
            $message_type = "error";
        } else {
            // Create a unique, safe filename
            $safe_filename = $user_id . '_' . uniqid() . '.pdf';
            $target_file = $target_dir . $safe_filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $final_resume_path = $target_file; // Set to new path
            } else {
                $message = "Error: There was an issue uploading your file.";
                $message_type = "error";
            }
        }
    }

    // --- NEW: RUN PYTHON EXTRACTOR ---
    $extracted_data = [];
    $python_error = null;
    
    if ($final_resume_path && $message_type == "success") {
        // Use "python" or "python3". You might need the full path if your server can't find it.
        // e.g., "C:/Users/Aditya/AppData/Local/Programs/Python/Python39/python.exe"
        // Use escapeshellarg to prevent command injection
        $command = "python extractor_cli.py " . escapeshellarg($final_resume_path);
        
        $json_output = shell_exec($command . " 2>&1"); // "2>&1" captures errors
        
        if ($json_output) {
            $extracted_data = json_decode($json_output, true);
            if (isset($extracted_data['error'])) {
                $python_error = "Resume Parsing Warning: " . $extracted_data['error'];
                $message = "Details saved, but " . $python_error;
            }
        } else {
            $python_error = "Resume Parsing Failed: No output from script. Check Python path & file permissions.";
            $message = "Details saved, but " . $python_error;
        }
    }
    // --- END: RUN PYTHON EXTRACTOR ---


    // 5. GET ALL DATA FROM FORM
    $college = $_POST['college'] ?? null;
    $degree = $_POST['degree'] ?? null;
    $grad_year = $_POST['grad_year'] ?? null;
    $cgpa = $_POST['cgpa'] ?? null;
    $linkedin = $_POST['linkedin'] ?? null;
    $github = $_POST['github'] ?? null;
    $domain = $_POST['domain'] ?? null;
    $skills = $_POST['skills'] ?? null; // Manual skills
    $cover_letter = $_POST['cover_letter'] ?? null;

    // --- NEW: GET EXTRACTED DATA ---
    $ext_name = $extracted_data['name'] ?? null;
    $ext_email = $extracted_data['email'] ?? null;
    $ext_phone = $extracted_data['phone'] ?? null;
    $ext_education = $extracted_data['education'] ?? null;
    $ext_experience = $extracted_data['experience'] ?? null;
    $ext_projects = $extracted_data['projects'] ?? null;
    $ext_skills = $extracted_data['skills'] ?? null; // Extracted skills
    $ext_certifications = $extracted_data['certifications'] ?? null;

    // 6. PREPARE AND EXECUTE DATABASE QUERY (NOW INCLUDES EXTRACTED DATA)
    $sql = "INSERT INTO user_details 
                (user_id, resume_path, college, degree, grad_year, cgpa, linkedin_url, github_url, cse_domain, skills, cover_letter,
                 extracted_name, extracted_email, extracted_phone, extracted_education, extracted_experience, extracted_projects, extracted_skills, extracted_certifications) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                resume_path = ?, college = ?, degree = ?, grad_year = ?, cgpa = ?, 
                linkedin_url = ?, github_url = ?, cse_domain = ?, skills = ?, cover_letter = ?,
                extracted_name = ?, extracted_email = ?, extracted_phone = ?, extracted_education = ?,
                extracted_experience = ?, extracted_projects = ?, extracted_skills = ?, extracted_certifications = ?";
    
    $stmt = $conn->prepare($sql);
    
    // CORRECTED: 37-character bind_param string (19 INSERT + 18 UPDATE)
    // i = integer (user_id), d = double (cgpa twice), s = string (all others)
    $stmt->bind_param(
        "issssdsssssssssssssssssdsssssssssssss",
        // --- INSERT values (19) ---
        $user_id, $final_resume_path, $college, $degree, $grad_year, $cgpa, $linkedin, $github, $domain, $skills, $cover_letter,
        $ext_name, $ext_email, $ext_phone, $ext_education, $ext_experience, $ext_projects, $ext_skills, $ext_certifications,
        // --- UPDATE values (18) ---
        $final_resume_path, $college, $degree, $grad_year, $cgpa, $linkedin, $github, $domain, $skills, $cover_letter,
        $ext_name, $ext_email, $ext_phone, $ext_education, $ext_experience, $ext_projects, $ext_skills, $ext_certifications
    );

    if ($stmt->execute()) {
        if (empty($message)) { // If no file error or parsing warning occurred
            $message = "Your details and resume data have been saved successfully!";
            $message_type = "success";
        }
    } else {
        $message = "Error: Could not save details to database. " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
    $conn->close();

} else {
    header("Location: apply.php");
    exit();
}

// 7. DISPLAY FINAL MESSAGE
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5;url=profile.php">
    <title>Application Submitted - SmartMatch AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow-md w-full z-50">
        <div class="container mx-auto flex justify-between items-center max-w-7xl p-4">
            <div class="logo text-neutral-dark text-3xl font-extrabold tracking-tight">
                <a href="index.php">SmartMatch <span class="text-primary-accent">AI</span></a>
            </div>
            <nav class="flex items-center space-x-6">
                <span class="text-gray-700 font-medium">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
                <a href="logout.php" class="bg-red-500 text-white px-6 py-2 rounded-full font-bold shadow-md hover:bg-red-600 transition duration-150 text-base">
                    Log Out
                </a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto max-w-2xl p-8 mt-20">
        <div class="bg-white p-8 md:p-12 rounded-2xl shadow-xl text-center">

            <?php if ($message_type == "success"): ?>
                <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h1 class="text-3xl font-bold text-green-600 mb-4">Success!</h1>
            <?php else: ?>
                <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h1 class="text-3xl font-bold text-red-600 mb-4">Oops!</h1>
            <?php endif; ?>
            
            <p class="text-lg text-gray-700 mb-8">
                <?php echo htmlspecialchars($message); ?>
            </p>

            <p class="text-sm text-gray-500">You will be redirected to your profile in 5 seconds.</p>

            <div class="mt-8">
                <a href="profile.php" class="bg-primary-accent text-white text-lg font-bold px-10 py-3 rounded-full shadow-lg hover:bg-cyan-600 transition">
                    View My Profile Now
                </a>
            </div>
        </div>
    </main>
</body>
</html>
