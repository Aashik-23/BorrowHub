<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$base = '';
$page_title = 'Contact Us — BorrowHub';
$active = 'contact';

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']    = trim($_POST['name'] ?? '');
    $old['email']   = trim($_POST['email'] ?? '');
    $old['subject'] = trim($_POST['subject'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    // ---- Server-side validation (mirrors the client-side rules in main.js) ----
    if (mb_strlen($old['name']) < 2) {
        $errors[] = 'Please enter your name (at least 2 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    $allowedSubjects = ['general', 'support', 'listing', 'partnership'];
    if (!in_array($old['subject'], $allowedSubjects, true)) {
        $errors[] = 'Please select a valid subject.';
    }
    if (mb_strlen($old['message']) < 10) {
        $errors[] = 'Message should be at least 10 characters.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)'
        );
        $stmt->execute([
            ':name'    => $old['name'],
            ':email'   => $old['email'],
            ':subject' => $old['subject'],
            ':message' => $old['message'],
        ]);

        set_flash('success', "Thanks — your message has been sent. We'll reply soon.");
        redirect('contact.php'); // Post/Redirect/Get avoids duplicate submissions on refresh
    }
}

require __DIR__ . '/includes/header.php';
?>

<header class="hero py-5">
  <div class="container">
    <p class="eyebrow mb-2">We'd love to hear from you</p>
    <h1 class="mb-2" style="font-size:clamp(1.8rem,3vw,2.6rem);">Get in Touch</h1>
    <p class="text-muted-custom mb-0">Questions, feedback, or a partnership idea — send us a message and we'll get back to you.</p>
  </div>
</header>

<section class="section-pad pt-4">
  <div class="container">
    <div class="row g-5">

      <div class="col-lg-7 reveal">
        <div class="form-card">
          <h3 class="mb-1">Send us a message</h3>
          <p class="text-muted-custom small mb-4">Fields marked are validated as you type.</p>

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

          <form data-contact-form novalidate method="post" action="contact.php">
            <div class="mb-3">
              <label for="contactName" class="form-label">Your Name</label>
              <input type="text" name="name" class="form-control" id="contactName" placeholder="Enter your name" required minlength="2" value="<?= htmlspecialchars($old['name']) ?>">
              <div class="invalid-feedback">Please enter your name (at least 2 characters).</div>
              <div class="valid-feedback">Looks good!</div>
            </div>

            <div class="mb-3">
              <label for="contactEmail" class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" id="contactEmail" placeholder="Enter your email" required value="<?= htmlspecialchars($old['email']) ?>">
              <div class="invalid-feedback">Please enter a valid email address.</div>
              <div class="valid-feedback">Looks good!</div>
            </div>

            <div class="mb-3">
              <label for="contactSubject" class="form-label">Subject</label>
              <select name="subject" class="form-select" id="contactSubject" required>
                <option value="" <?= $old['subject'] === '' ? 'selected' : '' ?> disabled>Select a subject</option>
                <option value="general" <?= $old['subject'] === 'general' ? 'selected' : '' ?>>General Enquiry</option>
                <option value="support" <?= $old['subject'] === 'support' ? 'selected' : '' ?>>Account Support</option>
                <option value="listing" <?= $old['subject'] === 'listing' ? 'selected' : '' ?>>Listing an Item</option>
                <option value="partnership" <?= $old['subject'] === 'partnership' ? 'selected' : '' ?>>Partnership</option>
              </select>
              <div class="invalid-feedback">Please select a subject.</div>
              <div class="valid-feedback">Looks good!</div>
            </div>

            <div class="mb-3">
              <label for="contactMessage" class="form-label">Message</label>
              <textarea name="message" class="form-control" id="contactMessage" rows="5" placeholder="Write your message…" required minlength="10"><?= htmlspecialchars($old['message']) ?></textarea>
              <div class="field-hint">Minimum 10 characters.</div>
              <div class="invalid-feedback">Message should be at least 10 characters.</div>
              <div class="valid-feedback">Looks good!</div>
            </div>

            <button type="submit" class="btn btn-bh w-100 w-sm-auto">
              <i class="bi bi-send me-1"></i> Send Message
            </button>

            <div class="form-success" data-form-success>
              <i class="bi bi-check-circle-fill"></i> Thanks — your message has been sent. We'll reply soon.
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-5 reveal reveal-delay-2">
        <div class="contact-info-row">
          <i class="bi bi-envelope"></i>
          <div>
            <div class="fw-semibold">Email</div>
            <div class="text-muted-custom small">info@borrowhub.lk</div>
          </div>
        </div>
        <div class="contact-info-row">
          <i class="bi bi-telephone"></i>
          <div>
            <div class="fw-semibold">Phone</div>
            <div class="text-muted-custom small">+94 70 123 4567</div>
          </div>
        </div>
        <div class="contact-info-row">
          <i class="bi bi-geo-alt"></i>
          <div>
            <div class="fw-semibold">Address</div>
            <div class="text-muted-custom small">123, Galle Road, Colombo 03, Sri Lanka</div>
          </div>
        </div>

        <h6 class="eyebrow mt-4 mb-2">Business Hours</h6>
        <ul class="list-unstyled small text-muted-custom mb-4">
          <li class="d-flex justify-content-between border-bottom py-1"><span>Monday – Friday</span><span>9.00 AM – 6.00 PM</span></li>
          <li class="d-flex justify-content-between border-bottom py-1"><span>Saturday</span><span>9.00 AM – 1.00 PM</span></li>
          <li class="d-flex justify-content-between py-1"><span>Sunday</span><span>Closed</span></li>
        </ul>

        <div class="map-placeholder">
          <i class="bi bi-geo-alt-fill pin"></i>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
