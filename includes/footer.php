<?php if (!isset($base)) $base = ''; ?>
<footer class="bh-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <a href="<?= $base ?>index.php" class="navbar-brand text-white d-flex align-items-center gap-2 mb-2">
          <span class="navbar-brand-mark">B</span> BorrowHub
        </a>
        <p class="small mb-3">Borrow what you need. Rent what you don't use. Save money, earn money.</p>
        <div>
          <a href="#" class="social-circle" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-circle" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" class="social-circle" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="social-circle" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h5>Quick Links</h5>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= $base ?>index.php">Home</a></li>
          <li class="mb-2"><a href="<?= $base ?>browse.php">Browse Items</a></li>
          <li class="mb-2"><a href="<?= $base ?>index.php#how-it-works">How It Works</a></li>
          <li class="mb-2"><a href="<?= $base ?><?= is_logged_in() ? 'dashboard.php' : 'auth/register.php' ?>">List Your Item</a></li>
          <li class="mb-2"><a href="<?= $base ?>contact.php">Contact</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-3">
        <h5>Categories</h5>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= $base ?>browse.php#electronics">Electronics</a></li>
          <li class="mb-2"><a href="<?= $base ?>browse.php#tools">Tools &amp; Equipment</a></li>
          <li class="mb-2"><a href="<?= $base ?>browse.php#sports">Sports &amp; Outdoor</a></li>
          <li class="mb-2"><a href="<?= $base ?>browse.php#photography">Photography</a></li>
          <li class="mb-2"><a href="<?= $base ?>browse.php#study">Study Essentials</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h5>Contact Us</h5>
        <ul class="list-unstyled small">
          <li class="mb-2"><i class="bi bi-envelope me-2"></i>info@borrowhub.lk</li>
          <li class="mb-2"><i class="bi bi-telephone me-2"></i>+94 70 123 4567</li>
          <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Colombo, Sri Lanka</li>
        </ul>
      </div>
    </div>
    <div class="foot-bottom text-center">© 2026 BorrowHub. All rights reserved.</div>
  </div>
</footer>

<button id="backToTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>js/main.js"></script>
</body>
</html>
