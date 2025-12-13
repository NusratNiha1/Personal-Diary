<?php
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/lib/utils.php');
session_start();

$pdo = get_pdo();

// Check if user completed security question verification
if (!isset($_SESSION['reset_user_id'])) {
    flash('Please verify your identity first', 'warning');
    redirect('forgot_password.php');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');

    if ($password === '' || $confirm === '') {
        $error = 'Please fill out all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        
        try {
            $stmt->execute([$password, $_SESSION['reset_user_id']]);
            
            // Clear session variables
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_username']);
            unset($_SESSION['security_question']);
            
            flash('Password reset successful! Please log in.', 'success');
            redirect('index.php');
        } catch (Throwable $e) {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}

// PAGE TITLE
$pageTitle = "Reset Password";
include __DIR__ . '/partials/head.php';
?>

<div class="max-w-md mx-auto">
  <div class="glass rounded-3xl shadow-2xl p-8 mt-8">
    
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
      Reset Your Password
    </h1>

    <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/40 border border-green-300 dark:border-green-700 text-green-700 dark:text-green-300">
      Identity verified! Enter your new password for <strong><?= htmlspecialchars($_SESSION['reset_username']) ?></strong>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/40 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
          New Password
        </label>
        <div class="relative">
          <input 
            type="password" 
            id="password"
            name="password" 
            required 
            minlength="6"
            class="w-full rounded-2xl px-4 py-3 
            bg-white/70 dark:bg-gray-700/50 
            focus:bg-white dark:focus:bg-gray-700 
            outline-none border border-primary-100 
            focus:border-primary-400 shadow-sm transition" 
          />
          <button 
            type="button"
            onclick="togglePassword('password')" 
            class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
            <i class="fas fa-eye" id="password-toggle-icon"></i>
          </button>
        </div>
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
          Confirm New Password
        </label>
        <div class="relative">
          <input 
            type="password" 
            id="confirm"
            name="confirm" 
            required 
            minlength="6"
            class="w-full rounded-2xl px-4 py-3 
            bg-white/70 dark:bg-gray-700/50 
            focus:bg-white dark:focus:bg-gray-700 
            outline-none border border-primary-100 
            focus:border-primary-400 shadow-sm transition" 
          />
          <button 
            type="button"
            onclick="togglePassword('confirm')" 
            class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
            <i class="fas fa-eye" id="confirm-toggle-icon"></i>
          </button>
        </div>
      </div>

      <button 
        type="submit" 
        class="w-full rounded-2xl bg-primary-600 hover:bg-primary-700 text-white py-3 shadow-lg transition">
        Reset Password
      </button>
    </form>

  </div>
</div>

<script>
function togglePassword(id) {
  const input = document.getElementById(id);
  const icon = document.getElementById(id + '-toggle-icon');
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = "password";
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
