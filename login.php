<?php
session_start();
include("db_connect.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname']; // Matches your signup.php

            // Redirect to index.php
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "No account found with that email!";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - SmartMatch AI</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
    <h2 class="text-3xl font-bold mb-6 text-center text-cyan-600">Log In</h2>
    
    <?php if (!empty($error)) echo "<p class='text-red-500 text-center mb-4'>$error</p>"; ?>
    
    <form method="POST" action="">
      <div class="mb-4">
        <label class="block text-gray-700 mb-2">Email</label>
        <input type="email" name="email" class="w-full p-3 border rounded-lg focus:ring focus:ring-cyan-300" required>
      </div>
      
      <div class="mb-6">
        <label class="block text-gray-700 mb-2">Password</label>
        <input type="password" name="password" class="w-full p-3 border rounded-lg focus:ring focus:ring-cyan-300" required>
      </div>
      
      <button type="submit" class="w-full bg-cyan-600 text-white py-3 rounded-lg font-semibold hover:bg-cyan-700 transition">
        Log In
      </button>

      <p class="mt-4 text-center text-gray-600">
        Don’t have an account? <a href="signup.php" class="text-cyan-600 font-semibold hover:underline">Sign Up</a>
      </p>
    </form>
  </div>
</body>
</html>