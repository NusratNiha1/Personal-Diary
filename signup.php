<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';

if (is_logged_in()) {
  redirect('dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $confirm  = trim($_POST['confirm'] ?? '');

  if ($username === '' || $password === '' || $confirm === '') {
    $error = 'Please fill out all fields';
  } elseif (strlen($username) < 3) {
    $error = 'Username must be at least 3 characters';
  } elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match';
  } else {
    [$ok, $msg] = register_user($username, $password);
    if ($ok) {
      flash('Account created! Please log in.', 'success');
      redirect('index.php');
    } else {
      $error = $msg ?? 'Failed to sign up';
    }
  }
}

$pageTitle = 'Sign Up';
include __DIR__ . '/partials/head.php';
?>
<div class="max-w-md mx-auto">
  <div class="glass rounded-3xl shadow-2xl p-8 mt-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Create your account</h1>
    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700"><?php echo e($error); ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm text-gray-700 mb-1">Username</label>
        <input type="text" name="username" required minlength="3" class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
      </div>
      <div>
        <label class="block text-sm text-gray-700 mb-1">Password</label>
        <input type="password" name="password" required minlength="6" class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
      </div>
      <div>
        <label class="block text-sm text-gray-700 mb-1">Confirm Password</label>
        <input type="password" name="confirm" required minlength="6" class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
      </div>
      <button type="submit" class="w-full rounded-2xl bg-primary-600 hover:bg-primary-700 text-white py-3 shadow-lg transition">Sign Up</button>
    </form>
    <p class="text-sm text-gray-600 mt-4">Already have an account? <a class="text-primary-700 hover:underline" href="index.php">Log in</a></p>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
