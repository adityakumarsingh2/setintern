<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - SmartMatch AI</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex flex-col items-center justify-center h-screen">
  <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md text-center">
    <h2 class="text-3xl font-bold text-cyan-600 mb-4">Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
    <p class="text-gray-600 mb-6">You have successfully logged in to SmartMatch AI Dashboard 🎉</p>
    <a href="logout.php" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition">Log Out</a>
  </div>
</body>
</html>
