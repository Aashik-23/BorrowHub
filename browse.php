<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$base = '';
$page_title = 'Browse Items — BorrowHub';
$active = 'browse';

$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';

$stmt = $pdo->query("SELECT items.*, users.username AS owner_name
                      FROM items
                      JOIN users ON users.id = items.user_id
                      ORDER BY items.created_at DESC");
$items = $stmt->fetchAll();

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

<header class="hero py-5">
  <div class="container">
    <p class="eyebrow mb-2"><?= count($items) ?>+ items near you</p>
    <h1 class="mb-2" style="font-size:clamp(1.8rem,3vw,2.6rem);">Browse Items</h1>
    <p class="text-muted-custom mb-0">Filter by category, price and availability to find exactly what you need.</p>
  </div>
</header>

<section class="section-pad pt-4">
  <div class="container">
    <div class="row g-4">

      <!-- ============ FILTER SIDEBAR ============ -->
      <div class="col-lg-3">
        <div class="filter-panel">
          <h6>Search</h6>
          <input type="search" class="form-control form-control-sm" placeholder="Search items…" data-filter-search aria-label="Search items" value="<?= htmlspecialchars($search) ?>">

          <h6>Category</h6>
          <?php foreach ($categoryLabels as $slug => $label): ?>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" value="<?= $slug ?>" id="cat<?= ucfirst($slug) ?>" data-filter-category>
            <label class="form-check-label" for="cat<?= ucfirst($slug) ?>"><?= htmlspecialchars($label) ?></label>
          </div>
          <?php endforeach; ?>

          <h6>Price Range <span class="info-dot" title="Shows items at or below this daily rate">?</span></h6>
          <input type="range" class="form-range" min="100" max="1000" step="50" value="1000" data-filter-price>
          <div class="d-flex justify-content-between small text-muted-custom">
            <span>Rs. 100</span>
            <span data-filter-price-output class="fw-semibold">Rs. 1000</span>
          </div>

          <h6>Availability</h6>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="availToday" data-filter-available>
            <label class="form-check-label" for="availToday">Available Today</label>
          </div>

          <button type="button" class="btn btn-bh-outline btn-sm w-100 mt-3" data-clear-filters>
            <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Filters
          </button>
        </div>
      </div>

      <!-- ============ ITEM GRID ============ -->
      <div class="col-lg-9">
        <div class="results-bar">
          <div class="fw-semibold" data-result-count><?= count($items) ?> items found</div>
          <div class="d-flex align-items-center gap-2">
            <label for="sortSelect" class="small text-muted-custom mb-0">Sort by:</label>
            <select id="sortSelect" class="form-select form-select-sm" style="width:auto;" data-sort>
              <option value="newest" selected>Newest First</option>
              <option value="price-asc">Price: Low to High</option>
              <option value="price-desc">Price: High to Low</option>
              <option value="rating-desc">Highest Rated</option>
            </select>
          </div>
        </div>

        <div class="row g-4" data-item-grid>
          <?php if (empty($items)): ?>
            <p class="text-muted-custom">No items have been listed yet.</p>
          <?php endif; ?>
          <?php foreach ($items as $i => $item): ?>
          <div class="col-6 col-md-4 col-xl-3"
               data-item
               data-category="<?= htmlspecialchars($item['category']) ?>"
               data-price="<?= (int)$item['price_per_day'] ?>"
               data-available="<?= $item['available'] ? 'true' : 'false' ?>"
               data-name="<?= htmlspecialchars($item['title']) ?>"
               data-rating="4.5"
               data-newest="<?= count($items) - $i ?>"
               data-location="<?= htmlspecialchars($item['location']) ?>"
               data-icon="<?= htmlspecialchars($item['icon']) ?>"
               data-desc="<?= htmlspecialchars($item['description']) ?>">
            <div class="tag-card">
              <div class="tag-thumb"><i class="bi <?= htmlspecialchars($item['icon']) ?>"></i></div>
              <span class="tag-cat"><?= htmlspecialchars($categoryLabels[$item['category']] ?? $item['category']) ?></span>
              <h3><?= htmlspecialchars($item['title']) ?></h3>
              <div class="tag-price">Rs. <?= number_format($item['price_per_day'], 0) ?> <small>/ day</small></div>
              <div class="tag-meta"><span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($item['location']) ?></span><span class="tag-rating">by <?= htmlspecialchars($item['owner_name']) ?></span></div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-bh-outline btn-sm flex-grow-1 quick-view-btn" data-bs-toggle="modal" data-bs-target="#quickViewModal"><i class="bi bi-eye me-1"></i>Quick View</button>
                <a href="<?= is_logged_in() ? '#' : 'auth/login.php' ?>" class="btn btn-bh btn-sm">Borrow</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="empty-state" data-empty-state>
          <i class="bi bi-inbox"></i>
          <p class="mb-0">No items match your filters. Try clearing a few and searching again.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ QUICK VIEW MODAL ============ -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content tag-modal">
      <div class="modal-header">
        <div class="d-flex align-items-center gap-2">
          <span class="qv-icon-wrap"><i class="bi" id="qvIcon"></i></span>
          <div>
            <span class="tag-cat" id="qvCategory">Category</span>
            <h5 class="modal-title mb-0" id="qvName">Item name</h5>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted-custom" id="qvDesc">Item description.</p>
        <hr class="perforated my-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted-custom small"><i class="bi bi-geo-alt me-1"></i><span id="qvLocation">—</span></span>
          <span class="tag-rating" id="qvRating">★ —</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="tag-price" id="qvPrice">Rs. — <small>/ day</small></span>
          <span class="badge" id="qvAvailability">—</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-bh-outline btn-sm" data-bs-dismiss="modal">Close</button>
        <a href="<?= is_logged_in() ? '#' : 'auth/login.php' ?>" class="btn btn-bh-amber btn-sm">Send Rental Request</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
