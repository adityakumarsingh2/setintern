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

// --- NEW: Fetch existing user data ---
$user_data = [];
$stmt = $conn->prepare("SELECT * FROM user_details WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
}
$stmt->close();
// Note: We don't close $conn here because we used it at the top
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application - SmartMatch AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        /* Custom styles for file input */
        input[type="file"]::file-selector-button {
            background-color: #06B6D4;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: #0891B2;
        }
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
            <h1 class="text-4xl font-extrabold text-neutral-dark mb-2">My Application Details</h1>
            <p class="text-lg text-gray-600 mb-8">Update your profile to get matched with internships.</p>

            <form action="submit_application.php" method="POST" enctype="multipart/form-data" class="space-y-8">

                <fieldset class="space-y-2">
                    <label for="resume" class="text-xl font-semibold text-gray-800">1. Resume Upload</label>
                    <p class="text-sm text-gray-500">Upload a new PDF resume (max 5MB) to overwrite the old one.</p>
                    <?php if (!empty($user_data['resume_path'])): ?>
                        <p class="text-sm text-green-600 font-medium">
                            Current file: <?php echo htmlspecialchars(basename($user_data['resume_path'])); ?>
                        </p>
                    <?php endif; ?>
                    <input type="file" name="resume" id="resume" accept=".pdf"
                           class="w-full text-gray-700 border border-gray-300 rounded-lg p-3 file:mr-4 file:border-0 file:bg-primary-accent file:text-white file:rounded-lg file:px-4 file:py-2 file:cursor-pointer hover:file:bg-cyan-600 transition">
                </fieldset>

                <fieldset class="p-6 border rounded-lg">
                    <legend class="text-xl font-semibold text-gray-800 px-2">2. Education</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div class="space-y-2">
                            <label for="college" class="block text-sm font-medium text-gray-700">University / College Name</label>
                            <input type="text" name="college" id="college" required placeholder="e.g., Indian Institute of Technology, Delhi"
                                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400"
                                   value="<?php echo htmlspecialchars($user_data['college'] ?? ''); ?>">
                        </div>
                        <div class="space-y-2">
                            <label for="degree" class="block text-sm font-medium text-gray-700">Graduation Detail / Degree</label>
                            <input type="text" name="degree" id="degree" required placeholder="e.g., B.Tech Computer Science"
                                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400"
                                   value="<?php echo htmlspecialchars($user_data['degree'] ?? ''); ?>">
                        </div>
                        <div class="space-y-2">
                            <label for="grad_year" class="block text-sm font-medium text-gray-700">Student's Year</label>
                            <select name="grad_year" id="grad_year" required
                                    class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400 bg-white">
                                <option value="" disabled <?php echo empty($user_data['grad_year']) ? 'selected' : ''; ?>>Select your current year</option>
                                <?php
                                $years = ["1st Year", "2nd Year", "3rd Year", "4th Year", "5th Year (Dual Degree)", "Graduated"];
                                foreach ($years as $year) {
                                    $selected = ($user_data['grad_year'] ?? '') == $year ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                         <div class="space-y-2">
                            <label for="cgpa" class="block text-sm font-medium text-gray-700">CGPA (out of 10)</label>
                            <input type="number" name="cgpa" id="cgpa" step="0.01" min="0" max="10" required placeholder="e.g., 8.75"
                                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400"
                                   value="<?php echo htmlspecialchars($user_data['cgpa'] ?? ''); ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="p-6 border rounded-lg">
                    <legend class="text-xl font-semibold text-gray-800 px-2">3. Professional Profiles</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div class="space-y-2">
                            <label for="linkedin" class="block text-sm font-medium text-gray-700">LinkedIn Profile URL</label>
                            <input type="url" name="linkedin" id="linkedin" placeholder="https://linkedin.com/in/yourprofile"
                                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400"
                                   value="<?php echo htmlspecialchars($user_data['linkedin_url'] ?? ''); ?>">
                        </div>
                        <div class="space-y-2">
                            <label for="github" class="block text-sm font-medium text-gray-700">GitHub Profile URL</label>
                            <input type="url" name="github" id="github" placeholder="https://github.com/yourusername"
                                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400"
                                   value="<?php echo htmlspecialchars($user_data['github_url'] ?? ''); ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="p-6 border rounded-lg">
                    <legend class="text-xl font-semibold text-gray-800 px-2">4. Internship Details</legend>
                    <div class="space-y-6 mt-4">
                        <div class="space-y-2">
                            <label for="domain" class="block text-sm font-medium text-gray-700">Primary Domain of Interest</label>
                            <select name="domain" id="domain" required
                                    class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400 bg-white">
                                <option value="" disabled <?php echo empty($user_data['cse_domain']) ? 'selected' : ''; ?>>Select your primary domain</option>
                                <?php
                                $domains = ["Full Stack Development", "Data Science & ML", "Cyber Security", "Cloud Computing", "DevOps", "UI/UX Design", "Other"];
                                foreach ($domains as $domain) {
                                    $selected = ($user_data['cse_domain'] ?? '') == $domain ? 'selected' : '';
                                    echo "<option value=\"$domain\" $selected>$domain</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="skills" class="block text-sm font-medium text-gray-700">Key Skills</label>
                            <textarea name="skills" id="skills" rows="3" placeholder="List your top skills separated by commas (e.g., Python, React, SQL, Figma)"
                                      class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400"><?php echo htmlspecialchars($user_data['skills'] ?? ''); ?></textarea>
                        </div>
                        <div class="space-y-2">
                            <label for="cover_letter" class="block text-sm font-medium text-gray-700">Why are you a good fit for an internship? (Mini Cover Letter)</label>
                            <textarea name="cover_letter" id="cover_letter" rows="4" placeholder="Briefly describe your passion for your chosen domain and why you'd be a great intern."
                                      class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-cyan-400"><?php echo htmlspecialchars($user_data['cover_letter'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </fieldset>

                <div class="text-center">
                    <button type="submit"
                            class="bg-primary-accent text-white text-xl font-bold px-12 py-4 rounded-full shadow-2xl transform transition hover:scale-105 hover:bg-cyan-600">
                        Save Details
                    </button>
                </div>

            </form>
        </div>
    </main>

</body>
</html>