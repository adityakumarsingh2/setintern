<?php
include('db_connect.php');
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $fullname = $_POST['fullname'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $check = $conn->prepare("SELECT * FROM users WHERE email=?");
  $check->bind_param("s", $email);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    $message = "Email already exists. Try logging in.";
  } else {
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $fullname, $email, $password);
    if ($stmt->execute()) {
      header("Location: login.php?signup=success");
      exit();
    } else {
      $message = "Error creating account!";
    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Sign Up - SmartMatch</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded-2xl shadow-xl w-96">
    <h2 class="text-2xl font-bold text-center text-cyan-600 mb-6">Create Account</h2>

    <?php if ($message) echo "<p class='text-red-500 text-center mb-4'>$message</p>"; ?>

    <form method="POST" action="">
      <input type="text" name="fullname" placeholder="Full Name" required
             class="w-full p-3 mb-3 border rounded-lg focus:ring-2 focus:ring-cyan-400">
      <input type="email" name="email" placeholder="Email" required
             class="w-full p-3 mb-3 border rounded-lg focus:ring-2 focus:ring-cyan-400">
      <input type="password" name="password" placeholder="Password" required
             class="w-full p-3 mb-4 border rounded-lg focus:ring-2 focus:ring-cyan-400">

      <button type="submit" class="w-full bg-cyan-500 text-white py-3 rounded-lg hover:bg-cyan-600 transition">
        Sign Up
      </button>
    </form>

    <p class="text-center text-sm mt-4">Already have an account?
      <a href="login.php" class="text-cyan-600 font-semibold">Login</a>
    </p>
  </div>
</body>
</html>
