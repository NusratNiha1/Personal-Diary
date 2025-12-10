<?php
require_once(__DIR__ . '/config/db.php');

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT id, password_reset_expires FROM users WHERE password_reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || strtotime($user['password_reset_expires']) < time()) {
    die("<div style='font-family: sans-serif; color: red; text-align:center; margin-top:50px;'>Invalid or expired token.</div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users 
        SET password = ?, password_reset_token = NULL, password_reset_expires = NULL 
        WHERE id = ?");
    $stmt->execute([$newPassword, $user['id']]);

    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>Password updated.<br><a href='index.php' style='color:blue;'>Log in</a></div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>

    <!-- Your global CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Tailwind (if you use it) -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black text-white min-h-screen flex items-center justify-center">

    <!-- Reset Password Card -->
    <div class="max-w-md w-full glass p-8 rounded-3xl shadow-2xl border border-white/10 backdrop-blur-xl">

        <h2 class="text-2xl font-bold mb-4 text-center">Choose New Password</h2>

        <form method="POST">

            <label class="text-gray-300 text-sm">New Password</label>
            <input type="password" name="password" required
                class="w-full p-3 rounded-xl mt-2 bg-gray-700/50 border border-gray-600 focus:ring focus:ring-blue-500">

            <button
                class="w-full mt-6 rounded-xl bg-blue-600 hover:bg-blue-700 transition py-3 font-semibold shadow-lg">
                Reset Password
            </button>
        </form>

        <p class="text-gray-400 text-sm text-center mt-6">
            Remembered your password?
            <a href="index.php" class="text-blue-400 hover:underline">Log in</a>
        </p>

    </div>
</body>

</html>
