<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$base = '';
$page_title = "BorrowHub — Borrow What You Need, Rent What You Don't Use";
$active = 'home';

// Pull the 4 newest available items straight from the database for "Featured Items".
$stmt = $pdo->query("SELECT * FROM items WHERE available = 1 ORDER BY created_at DESC LIMIT 4");
$featured = $stmt->fetchAll();

$categoryLabels = [
    'electronics'  => 'Electronics',
    'tools'        => 'Tools & Equipment',
    'sports'       => 'Sports & Outdoor',
    'photography'  => 'Photography',
    'study'        => 'Study Essentials',
    'home'         => 'Home & Events',
];

require __DIR__ . '/includes/header.php';
?>

<header class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 reveal">
        <p class="eyebrow mb-3">Community Rental Platform</p>
        <h1 class="mb-3">Borrow What You Need,<br>Rent What You Don't Use</h1>
        <p class="text-muted-custom mb-4 fs-5">Borrow everyday items from people near you, or list your own items and start earning — cameras, tools, study gear and more, by the day.</p>

        <form class="hero-search d-flex align-items-center mb-4" role="search" action="browse.php" method="get">
          <i class="bi bi-search text-muted-custom"></i>
          <input type="search" name="q" class="ms-2" placeholder="Search for items e.g., laptop, camera, drill…" aria-label="Search items">
          <button class="btn btn-bh" type="submit">Search</button>
        </form>

        <div class="d-flex gap-4">
          <div>
            <div class="fs-4 fw-bold display-font">1,200+</div>
            <div class="text-muted-custom small">Items listed</div>
          </div>
          <div>
            <div class="fs-4 fw-bold display-font">4.7★</div>
            <div class="text-muted-custom small">Average rating</div>
          </div>
          <div>
            <div class="fs-4 fw-bold display-font">6</div>
            <div class="text-muted-custom small">Categories</div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 reveal reveal-delay-2">
        <div class="bh-slider" data-slider aria-roledescription="carousel" aria-label="Featured rental categories">
          <div class="bh-slide active">
            <i class="bi bi-laptop"></i>
            <span class="slide-cat">Electronics</span>
            <span class="slide-title">Laptops &amp; Projectors</span>
          </div>
          <div class="bh-slide">
            <i class="bi bi-camera"></i>
            <span class="slide-cat">Photography</span>
            <span class="slide-title">Cameras &amp; Lenses</span>
          </div>
          <div class="bh-slide">
            <i class="bi bi-tools"></i>
            <span class="slide-cat">Tools &amp; Equipment</span>
            <span class="slide-title">Drills &amp; Hand Tools</span>
          </div>
          <div class="bh-slide">
            <i class="bi bi-trophy"></i>
            <span class="slide-cat">Sports &amp; Outdoor</span>
            <span class="slide-title">Tents &amp; Helmets</span>
          </div>
          <button class="bh-slider-arrow prev" aria-label="Previous slide"><i class="bi bi-chevron-left"></i></button>
          <button class="bh-slider-arrow next" aria-label="Next slide"><i class="bi bi-chevron-right"></i></button>
          <div class="bh-slider-dots" data-slider-dots></div>
        </div>
      </div>
    </div>
  </div>
</header>

<section class="section-pad">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <p class="eyebrow">Browse by</p>
      <h2>Categories</h2>
    </div>
    <div class="row g-3">
      <div class="col-6 col-md-4 col-lg-2 reveal reveal-delay-1">
        <a href="browse.php#electronics" class="cat-chip"><i class="bi bi-laptop"></i><span>Electronics</span></a>
      </div>
      <div class="col-6 col-md-4 col-lg-2 reveal reveal-delay-1">
        <a href="browse.php#tools" class="cat-chip"><i class="bi bi-tools"></i><span>Tools &amp; Equipment</span></a>
      </div>
      <div class="col-6 col-md-4 col-lg-2 reveal reveal-delay-2">
        <a href="browse.php#sports" class="cat-chip"><i class="bi bi-trophy"></i><span>Sports &amp; Outdoor</span></a>
      </div>
      <div class="col-6 col-md-4 col-lg-2 reveal reveal-delay-2">
        <a href="browse.php#photography" class="cat-chip"><i class="bi bi-camera"></i><span>Photography</span></a>
      </div>
      <div class="col-6 col-md-4 col-lg-2 reveal reveal-delay-3">
        <a href="browse.php#study" class="cat-chip"><i class="bi bi-calculator"></i><span>Study Essentials</span></a>
      </div>
      <div class="col-6 col-md-4 col-lg-2 reveal reveal-delay-3">
        <a href="browse.php#home" class="cat-chip"><i class="bi bi-house-heart"></i><span>Home &amp; Events</span></a>
      </div>
    </div>
  </div>
</section>

<section class="section-pad bg-forest-tint">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-5 flex-wrap gap-2 reveal">
      <div>
        <p class="eyebrow">Fresh listings</p>
        <h2 class="mb-0">Featured Items</h2>
      </div>
      <a href="browse.php" class="btn-link-amber">Browse all items &rarr;</a>
    </div>

    <div class="row g-4 g-md-4">
      <?php if (empty($featured)): ?>
        <p class="text-muted-custom">No items have been listed yet — be the first to <a href="<?= is_logged_in() ? 'dashboard.php' : 'auth/register.php' ?>">list an item</a>.</p>
      <?php else: ?>
        <?php foreach ($featured as $item): ?>
        <div class="col-6 col-md-4 col-lg-3 reveal">
          <div class="tag-card">
            <div class="tag-thumb"><i class="bi <?= htmlspecialchars($item['icon']) ?>"></i></div>
            <span class="tag-cat"><?= htmlspecialchars($categoryLabels[$item['category']] ?? $item['category']) ?></span>
            <h3><?= htmlspecialchars($item['title']) ?></h3>
            <div class="tag-price">Rs. <?= number_format($item['price_per_day'], 0) ?> <small>/ day</small></div>
            <div class="tag-meta"><span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($item['location']) ?></span></div>
            <a href="browse.php" class="btn btn-bh-outline btn-sm">Borrow Now</a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section id="how-it-works" class="section-pad">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <p class="eyebrow">The process</p>
      <h2>How BorrowHub Works</h2>
    </div>
    <div class="row g-4 text-center">
      <div class="col-md-3 reveal reveal-delay-1">
        <div class="cat-chip h-100">
          <i class="bi bi-search"></i>
          <span>1. Search &amp; find the item you need nearby</span>
        </div>
      </div>
      <div class="col-md-3 reveal reveal-delay-2">
        <div class="cat-chip h-100">
          <i class="bi bi-chat-dots"></i>
          <span>2. Send a rental request to the owner</span>
        </div>
      </div>
      <div class="col-md-3 reveal reveal-delay-3">
        <div class="cat-chip h-100">
          <i class="bi bi-box-seam"></i>
          <span>3. Pick up the item and enjoy your rental</span>
        </div>
      </div>
      <div class="col-md-3 reveal reveal-delay-4">
        <div class="cat-chip h-100">
          <i class="bi bi-star"></i>
          <span>4. Return it and leave a review</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-pad pt-0">
  <div class="container">
    <div class="cta-band d-flex flex-wrap align-items-center justify-content-between gap-3 reveal">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-circle"><i class="bi bi-person-plus"></i></div>
        <div>
          <h3 class="mb-1">Have items you don't use?</h3>
          <p class="mb-0 opacity-75">List your items on BorrowHub and start earning money by helping others.</p>
        </div>
      </div>
      <a href="<?= is_logged_in() ? 'dashboard.php' : 'auth/register.php' ?>" class="btn btn-bh-amber">List Your Item</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
