<?php
session_start();
include('db_connect.php');

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user data from both tables
$user_data = null;
$stmt = $conn->prepare("
    SELECT u.fullname, u.email, d.* FROM users u
    LEFT JOIN user_details d ON u.id = d.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
}
$stmt->close();
$conn->close();

// Helper function to display data
function show_data($label, $data, $placeholder = "Not provided") {
    $value = !empty($data) ? htmlspecialchars($data) : "<span class='text-gray-400 italic'>{$placeholder}</span>";
    echo "
    <div class='py-3 sm:grid sm:grid-cols-3 sm:gap-4'>
        <dt class='text-sm font-medium text-gray-500'>{$label}</dt>
        <dd class='mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2'>{$value}</dd>
    </div>
    ";
}

function show_multiline_data($label, $data, $placeholder = "Not provided") {
    $value = !empty($data) ? nl2br(htmlspecialchars($data)) : "<span class='text-gray-400 italic'>{$placeholder}</span>";
    echo "
    <div class='py-3 sm:grid sm:grid-cols-3 sm:gap-4'>
        <dt class='text-sm font-medium text-gray-500'>{$label}</dt>
        <dd class='mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2'>{$value}</dd>
    </div>
    ";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SmartMatch AI</title>
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

    <main class="container mx-auto max-w-4xl p-8 mt-10">
        <div class="bg-white p-8 md:p-12 rounded-2xl shadow-xl">
            
            <div class="flex justify-between items-center mb-8 border-b pb-4">
                <div>
                    <h1 class="text-4xl font-extrabold text-neutral-dark">My Profile</h1>
                    <p class="text-lg text-gray-600">Review your application details below.</p>
                </div>
                <div class="flex gap-3">
                    <a href="index.php" class="bg-gray-600 text-white font-bold px-6 py-3 rounded-full shadow-lg hover:bg-gray-700 transition transform hover:scale-105">
                        Home
                    </a>
                    <a href="apply.php" class="bg-cyan-500 text-white font-bold px-6 py-3 rounded-full shadow-lg hover:bg-cyan-600 transition transform hover:scale-105">
                        Edit Details
                    </a>
                </div>
            </div>

            <?php if ($user_data): ?>
            <div class="mb-10">
                <h2 class="text-2xl font-bold text-primary-accent mb-4">Application Details</h2>
                <dl class="divide-y divide-gray-200">
                    <?php show_data("Full Name", $user_data['fullname']); ?>
                    <?php show_data("Email", $user_data['email']); ?>
                    <?php show_data("College", $user_data['college']); ?>
                    <?php show_data("Degree", $user_data['degree']); ?>
                    <?php show_data("Year", $user_data['grad_year']); ?>
                    <?php show_data("CGPA", $user_data['cgpa']); ?>
                    <?php show_data("LinkedIn", $user_data['linkedin_url']); ?>
                    <?php show_data("GitHub", $user_data['github_url']); ?>
                    <?php show_data("Domain", $user_data['cse_domain']); ?>
                    <?php show_multiline_data("Manual Skills", $user_data['skills']); ?>
                    <?php show_multiline_data("Cover Letter", $user_data['cover_letter']); ?>
                    <?php show_data("Resume File", $user_data['resume_path'] ? basename($user_data['resume_path']) : "Not Uploaded"); ?>
                </dl>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-primary-accent mb-4">Data Extracted from Resume (AI)</h2>
                <dl class="divide-y divide-gray-200">
                    <?php show_data("Name", $user_data['extracted_name'], "Not found"); ?>
                    <?php show_data("Email", $user_data['extracted_email'], "Not found"); ?>
                    <?php show_data("Phone", $user_data['extracted_phone'], "Not found"); ?>
                    <?php show_multiline_data("Education", str_replace(' | ', "\n", $user_data['extracted_education']), "Not found"); ?>
                    <?php show_multiline_data("Experience", str_replace(' | ', "\n", $user_data['extracted_experience']), "Not found"); ?>
                    <?php show_multiline_data("Projects", str_replace(' | ', "\n", $user_data['extracted_projects']), "Not found"); ?>
                    <?php show_data("Skills", $user_data['extracted_skills'], "Not found"); ?>
                    <?php show_multiline_data("Certifications", str_replace(' | ', "\n", $user_data['extracted_certifications']), "Not found"); ?>
                </dl>
            </div>
            <?php else: ?>
                <p class="text-center text-gray-500">Could not load user data. Please try again.</p>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>
