<?php
require_once(__DIR__ . '/config/db.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Create token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour

        // Save token
        $stmt = $pdo->prepare("UPDATE users SET password_reset_token=?, password_reset_expires=? WHERE id=?");
        $stmt->execute([$token, $expires, $user['id']]);

        // Build reset link
        $resetLink = "http://yourdomain.com/reset_password.php?token=$token";

        // Send email
        mail($email, "Password Reset", "Click to reset: $resetLink");

        $message = "A reset link has been sent to your email.";
    } else {
        $message = "Email not found.";
    }
}

// PAGE TITLE
$pageTitle = "Forgot Password";
include __DIR__ . '/partials/head.php';
?>

<div class="max-w-md mx-auto">
  <div class="glass rounded-3xl shadow-2xl p-8 mt-8">
    
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
      Forgot Password
    </h1>

    <?php if ($message): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/40 border border-green-300 dark:border-green-700 text-green-700 dark:text-green-300">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
          Enter your email address
        </label>
        <input 
          type="email" 
          name="email" 
          required 
          class="w-full rounded-2xl px-4 py-3 
          bg-white/70 dark:bg-gray-700/50 
          focus:bg-white dark:focus:bg-gray-700 
          outline-none border border-primary-100 
          focus:border-primary-400 shadow-sm transition" 
        />
      </div>

      <button 
        type="submit" 
        class="w-full rounded-2xl bg-primary-600 hover:bg-primary-700 text-white py-3 shadow-lg transition">
        Send Reset Link
      </button>

    </form>

    <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
      Remember your password?  
      <a class="text-primary-700 dark:text-primary-300 hover:underline" href="index.php">
        Log in
      </a>
    </p>

  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
