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
  $security_question = trim($_POST['security_question'] ?? '');
  $security_answer = trim($_POST['security_answer'] ?? '');

  if ($username === '' || $password === '' || $confirm === '' || $security_question === '' || $security_answer === '') {
    $error = 'Please fill out all fields';
  } elseif (strlen($username) < 3) {
    $error = 'Username must be at least 3 characters';
  } elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match';
  } else {
    [$ok, $msg] = register_user($username, $password, $security_question, $security_answer);
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
        <div class="relative">
          <input 
            type="password" 
            id="password"
            name="password" 
            required 
            minlength="6"
            class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition"
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
        <label class="block text-sm text-gray-700 mb-1">Confirm Password</label>
        <div class="relative">
          <input 
            type="password" 
            id="confirm"
            name="confirm" 
            required 
            minlength="6"
            class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition"
          />
          <button 
            type="button"
            onclick="togglePassword('confirm')" 
            class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
            <i class="fas fa-eye" id="confirm-toggle-icon"></i>
          </button>
        </div>
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Security Question</label>
        <select 
          name="security_question" 
          required 
          class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 shadow-sm transition">
          <option value="">Select a question...</option>
          <option value="What is your favorite color?">What is your favorite color?</option>
          <option value="What city were you born in?">What city were you born in?</option>
          <option value="What is your pet's name?">What is your pet's name?</option>
          <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
          <option value="What was your first school's name?">What was your first school's name?</option>
        </select>
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-1">Security Answer</label>
        <input 
          type="text" 
          name="security_answer" 
          required 
          class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition"
        />
      </div>

      <button type="submit" class="w-full rounded-2xl bg-primary-600 hover:bg-primary-700 text-white py-3 shadow-lg transition">Sign Up</button>
    </form>
    <p class="text-sm text-gray-600 mt-4">Already have an account? <a class="text-primary-700 hover:underline" href="index.php">Log in</a></p>
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