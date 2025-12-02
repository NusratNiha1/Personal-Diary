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
  if ($username === '' || $password === '') {
    $error = 'Please fill out all fields';
  } else {
    if (login_user($username, $password)) {
      flash('Welcome back, ' . $username . '!', 'success');
      redirect('dashboard.php');
    } else {
      $error = 'Invalid username or password';
    }
  }
}

$pageTitle = 'Login';
include __DIR__ . '/partials/head.php';
?>
<div class="max-w-md mx-auto">
  <div class="glass rounded-3xl shadow-2xl p-8 mt-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Welcome back</h1>
    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700"><?php echo e($error); ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm text-gray-700 mb-1">Username</label>
        <input type="text" name="username" required class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
      </div>
      <div>
        <label class="block text-sm text-gray-700 mb-1">Password</label>
        <input type="password" name="password" required class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
      </div>
      <button type="submit" class="w-full rounded-2xl bg-primary-600 hover:bg-primary-700 text-white py-3 shadow-lg transition">Log In</button>
    </form>
    <p class="text-sm text-gray-600 mt-4">Don't have an account? <a class="text-primary-700 hover:underline" href="signup.php">Sign up</a></p>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
