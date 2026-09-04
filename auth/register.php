<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

if (is_logged_in()) {
    redirect('../dashboard.php');
}

$base = '../';
$page_title = 'Register — BorrowHub';
$active = '';

$errors = [];
$old = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email']    = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm         = $_POST['confirm_password'] ?? '';

    // ---- Server-side validation ----
    if (mb_strlen($old['username']) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (mb_strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check username/email aren't already taken
        $check = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
        $check->execute([':username' => $old['username'], ':email' => $old['email']]);
        if ($check->fetch()) {
            $errors[] = 'That username or email is already registered.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)'
        );
        $stmt->execute([
            ':username' => $old['username'],
            ':email'    => $old['email'],
            ':password' => $hashed,
        ]);

        set_flash('success', 'Account created — please log in.');
        redirect('login.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>

<section class="section-pad">
  <div class="container auth-wrap">
    <div class="row g-4 align-items-stretch w-100">

      <div class="col-lg-5 reveal">
        <div class="auth-visual">
          <div class="ring r1"></div>
          <div class="ring r2"></div>
          <i class="bi bi-person-plus lock-icon"></i>
          <h3>Join BorrowHub</h3>
          <p class="mb-0 opacity-75">Create an account to borrow items from people near you, or list your own and start earning.</p>
        </div>
      </div>

      <div class="col-lg-6 offset-lg-1 reveal reveal-delay-2 d-flex align-items-center">
        <div class="form-card w-100">
          <h3 class="mb-1">Create Your Account</h3>
          <p class="text-muted-custom small mb-4">Already have an account? <a href="login.php" class="btn-link-amber">Login here</a>.</p>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error): ?>
                  <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form data-register-form novalidate method="post" action="register.php">
            <div class="mb-3">
              <label for="regUsername" class="form-label">Username</label>
              <input type="text" name="username" class="form-control" id="regUsername" placeholder="Choose a username" required minlength="3" value="<?= htmlspecialchars($old['username']) ?>">
              <div class="invalid-feedback">Username must be at least 3 characters.</div>
            </div>

            <div class="mb-3">
              <label for="regEmail" class="form-label">Email</label>
              <input type="email" name="email" class="form-control" id="regEmail" placeholder="Enter your email" required value="<?= htmlspecialchars($old['email']) ?>">
              <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <div class="mb-3">
              <label for="regPassword" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" name="password" class="form-control" id="regPassword" placeholder="Create a password" required minlength="6">
                <span class="input-group-text bg-white">
                  <i class="bi bi-eye password-toggle" data-password-toggle="#regPassword" role="button" aria-label="Show password"></i>
                </span>
              </div>
              <div class="invalid-feedback">Password must be at least 6 characters.</div>
            </div>

            <div class="mb-3">
              <label for="regConfirmPassword" class="form-label">Confirm Password</label>
              <div class="input-group">
                <input type="password" name="confirm_password" class="form-control" id="regConfirmPassword" placeholder="Re-enter your password" required minlength="6">
                <span class="input-group-text bg-white">
                  <i class="bi bi-eye password-toggle" data-password-toggle="#regConfirmPassword" role="button" aria-label="Show password"></i>
                </span>
              </div>
              <div class="invalid-feedback">Passwords must match.</div>
            </div>

            <button type="submit" class="btn btn-bh w-100">Register</button>

            <p class="text-center small text-muted-custom mt-3 mb-0">Already have an account? <a href="login.php" class="btn-link-amber">Login</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
