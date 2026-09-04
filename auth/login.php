<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

if (is_logged_in()) {
    redirect('../dashboard.php');
}

$base = '../';
$page_title = 'Login — BorrowHub';
$active = '';

$errors = [];
$old = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['email'] = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (mb_strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE email = :email');
        $stmt->execute([':email' => $old['email']]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session id on every successful login to prevent session fixation.
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            redirect('../dashboard.php');
        } else {
            $errors[] = 'Incorrect email or password.';
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<section class="section-pad">
  <div class="container auth-wrap">
    <div class="row g-4 align-items-stretch w-100">

      <!-- ============ VISUAL PANEL ============ -->
      <div class="col-lg-5 reveal">
        <div class="auth-visual">
          <div class="ring r1"></div>
          <div class="ring r2"></div>
          <i class="bi bi-shield-lock lock-icon"></i>
          <h3>Welcome back!</h3>
          <p class="mb-0 opacity-75">Login to your BorrowHub account to continue borrowing or manage the items you've listed.</p>
        </div>
      </div>

      <!-- ============ LOGIN FORM ============ -->
      <div class="col-lg-6 offset-lg-1 reveal reveal-delay-2 d-flex align-items-center">
        <div class="form-card w-100">
          <h3 class="mb-1">Login to Your Account</h3>
          <p class="text-muted-custom small mb-4">New to BorrowHub? <a href="register.php" class="btn-link-amber">Register here</a>.</p>

          <?php render_flash(); ?>
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error): ?>
                  <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form data-login-form novalidate method="post" action="login.php">
            <div class="mb-3">
              <label for="loginEmail" class="form-label">Email</label>
              <input type="email" name="email" class="form-control" id="loginEmail" placeholder="Enter your email" required value="<?= htmlspecialchars($old['email']) ?>">
              <div class="invalid-feedback">Please enter a valid email address.</div>
              <div class="valid-feedback">Looks good!</div>
            </div>

            <div class="mb-3">
              <label for="loginPassword" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" name="password" class="form-control" id="loginPassword" placeholder="Enter your password" required minlength="6">
                <span class="input-group-text bg-white">
                  <i class="bi bi-eye password-toggle" data-password-toggle="#loginPassword" role="button" aria-label="Show password"></i>
                </span>
                <div class="invalid-feedback">Password must be at least 6 characters.</div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                <label class="form-check-label small" for="rememberMe">Remember Me</label>
              </div>
              <a href="#" class="small btn-link-amber">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-bh w-100">Login</button>

            <div class="form-success" data-form-success>
              <i class="bi bi-check-circle-fill"></i> Login successful — redirecting you to your dashboard.
            </div>

            <p class="text-center small text-muted-custom mt-3 mb-0">Don't have an account? <a href="register.php" class="btn-link-amber">Register</a></p>
          </form>

          <p class="text-center small text-muted-custom mt-3 mb-0">
            Demo account — email: <code>demo@borrowhub.lk</code>, password: <code>Demo@1234</code>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
