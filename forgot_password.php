<?php
require_once(__DIR__ . '/config/db.php');
session_start();

$pdo = get_pdo();
$message = '';
$step = 'username'; // username, security_question, or success

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username'])) {
        // Step 1: Check if username exists and has security question
        $username = trim($_POST['username']);
        
        $stmt = $pdo->prepare("SELECT user_id, security_question FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['security_question']) {
            $_SESSION['reset_user_id'] = $user['user_id'];
            $_SESSION['reset_username'] = $username;
            $_SESSION['security_question'] = $user['security_question'];
            $step = 'security_question';
        } else {
            $message = $user ? 'No security question set for this account. Please contact admin.' : 'Username not found.';
        }
    } elseif (isset($_POST['security_answer'])) {
        // Step 2: Verify security answer
        if (!isset($_SESSION['reset_user_id'])) {
            $message = 'Session expired. Please start over.';
            $step = 'username';
        } else {
            $answer = strtolower(trim($_POST['security_answer']));
            
            $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['reset_user_id']]);
            $user = $stmt->fetch();

            if ($user && $answer === $user['security_answer']) {
                // Answer is correct, redirect to reset password page
                header('Location: reset_password.php');
                exit;
            } else {
                $message = 'Incorrect answer. Please try again.';
                $step = 'security_question';
            }
        }
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
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/40 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <?php if ($step === 'username'): ?>
      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
            Enter your username
          </label>
          <input 
            type="text" 
            name="username" 
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
          Continue
        </button>
      </form>
    <?php elseif ($step === 'security_question'): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-blue-50 dark:bg-blue-900/40 border border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-300">
        <strong>Security Question:</strong><br/>
        <?= htmlspecialchars($_SESSION['security_question']) ?>
      </div>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
            Your Answer
          </label>
          <input 
            type="text" 
            name="security_answer" 
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
          Verify Answer
        </button>
      </form>

      <form method="POST" class="mt-3">
        <button 
          type="submit" 
          name="username" 
          value="" 
          class="text-sm text-primary-700 dark:text-primary-300 hover:underline">
          ← Start over
        </button>
      </form>
    <?php endif; ?>

    <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
      Remember your password?  
      <a class="text-primary-700 dark:text-primary-300 hover:underline" href="index.php">
        Log in
      </a>
    </p>

  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
