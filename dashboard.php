<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

require_login('auth/login.php'); // bounce guests to login

$base = '';
$page_title = 'Dashboard — BorrowHub';
$active = 'dashboard';

$userId = $_SESSION['user_id'];
$errors = [];

$categoryLabels = [
    'electronics'  => 'Electronics',
    'tools'        => 'Tools & Equipment',
    'sports'       => 'Sports & Outdoor',
    'photography'  => 'Photography',
    'study'        => 'Study Essentials',
    'home'         => 'Home & Events',
];
$categoryIcons = [
    'electronics'  => 'bi-laptop',
    'tools'        => 'bi-tools',
    'sports'       => 'bi-trophy',
    'photography'  => 'bi-camera',
    'study'        => 'bi-calculator',
    'home'         => 'bi-house-heart',
];

// ---- Handle "delete item" ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item_id'])) {
    $deleteId = (int)$_POST['delete_item_id'];
    $stmt = $pdo->prepare('DELETE FROM items WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $deleteId, ':user_id' => $userId]);
    set_flash('success', 'Item removed.');
    redirect('dashboard.php');
}

// ---- Handle "add new item" ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price_per_day'] ?? '';
    $location    = trim($_POST['location'] ?? '');
    $available   = isset($_POST['available']) ? 1 : 0;

    if (mb_strlen($title) < 2) {
        $errors[] = 'Please enter an item title (at least 2 characters).';
    }
    if (!array_key_exists($category, $categoryLabels)) {
        $errors[] = 'Please choose a valid category.';
    }
    if (!is_numeric($price) || (float)$price <= 0) {
        $errors[] = 'Please enter a valid daily price.';
    }
    if (mb_strlen($location) < 2) {
        $errors[] = 'Please enter a location.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO items (user_id, title, category, description, price_per_day, location, available, icon)
             VALUES (:user_id, :title, :category, :description, :price, :location, :available, :icon)'
        );
        $stmt->execute([
            ':user_id'     => $userId,
            ':title'       => $title,
            ':category'    => $category,
            ':description' => $description,
            ':price'       => $price,
            ':location'    => $location,
            ':available'   => $available,
            ':icon'        => $categoryIcons[$category],
        ]);
        set_flash('success', 'Item listed successfully.');
        redirect('dashboard.php');
    }
}

// ---- Fetch this user's listed items ----
$stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute([':user_id' => $userId]);
$myItems = $stmt->fetchAll();

// ---- Fetch messages sent through the Contact form (admin-style overview) ----
$recentMessages = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<header class="hero py-5">
  <div class="container">
    <p class="eyebrow mb-2">Welcome back</p>
    <h1 class="mb-2" style="font-size:clamp(1.8rem,3vw,2.6rem);"><?= htmlspecialchars($_SESSION['username']) ?>'s Dashboard</h1>
    <p class="text-muted-custom mb-0">List a new item to rent out, or manage the items you've already listed.</p>
  </div>
</header>

<section class="section-pad pt-4">
  <div class="container">
    <?php render_flash(); ?>

    <div class="row g-5">

      <!-- ============ ADD ITEM FORM ============ -->
      <div class="col-lg-5 reveal">
        <div class="form-card">
          <h3 class="mb-1">List a New Item</h3>
          <p class="text-muted-custom small mb-4">Fill in the details below to add it to Browse Items.</p>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error): ?>
                  <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" action="dashboard.php" novalidate>
            <input type="hidden" name="add_item" value="1">

            <div class="mb-3">
              <label for="itemTitle" class="form-label">Item Title</label>
              <input type="text" name="title" class="form-control" id="itemTitle" placeholder="e.g. DSLR Camera" required minlength="2">
            </div>

            <div class="mb-3">
              <label for="itemCategory" class="form-label">Category</label>
              <select name="category" class="form-select" id="itemCategory" required>
                <option value="" selected disabled>Select a category</option>
                <?php foreach ($categoryLabels as $slug => $label): ?>
                  <option value="<?= $slug ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="row g-2">
              <div class="col-6 mb-3">
                <label for="itemPrice" class="form-label">Price / Day (Rs.)</label>
                <input type="number" name="price_per_day" class="form-control" id="itemPrice" min="1" step="1" required>
              </div>
              <div class="col-6 mb-3">
                <label for="itemLocation" class="form-label">Location</label>
                <input type="text" name="location" class="form-control" id="itemLocation" placeholder="e.g. Colombo" required minlength="2">
              </div>
            </div>

            <div class="mb-3">
              <label for="itemDescription" class="form-label">Description</label>
              <textarea name="description" class="form-control" id="itemDescription" rows="3" placeholder="Describe the item's condition, what's included, etc."></textarea>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="itemAvailable" name="available" checked>
              <label class="form-check-label" for="itemAvailable">Available for rent right now</label>
            </div>

            <button type="submit" class="btn btn-bh w-100">
              <i class="bi bi-plus-circle me-1"></i> List Item
            </button>
          </form>
        </div>
      </div>

      <!-- ============ MY ITEMS ============ -->
      <div class="col-lg-7 reveal reveal-delay-2">
        <h3 class="mb-3">My Listed Items (<?= count($myItems) ?>)</h3>

        <?php if (empty($myItems)): ?>
          <div class="empty-state show">
            <i class="bi bi-inbox"></i>
            <p class="mb-0">You haven't listed any items yet — use the form to add your first one.</p>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($myItems as $item): ?>
            <div class="col-6 col-md-4">
              <div class="tag-card">
                <div class="tag-thumb"><i class="bi <?= htmlspecialchars($item['icon']) ?>"></i></div>
                <span class="tag-cat"><?= htmlspecialchars($categoryLabels[$item['category']] ?? $item['category']) ?></span>
                <h3><?= htmlspecialchars($item['title']) ?></h3>
                <div class="tag-price">Rs. <?= number_format($item['price_per_day'], 0) ?> <small>/ day</small></div>
                <div class="tag-meta">
                  <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($item['location']) ?></span>
                  <span class="badge <?= $item['available'] ? 'badge-available' : 'badge-unavailable' ?>">
                    <?= $item['available'] ? 'Available' : 'Rented' ?>
                  </span>
                </div>
                <form method="post" action="dashboard.php" onsubmit="return confirm('Remove this item?');">
                  <input type="hidden" name="delete_item_id" value="<?= (int)$item['id'] ?>">
                  <button type="submit" class="btn btn-bh-outline btn-sm w-100">
                    <i class="bi bi-trash me-1"></i>Remove
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <h3 class="mt-5 mb-3">Recent Contact Messages</h3>
        <?php if (empty($recentMessages)): ?>
          <p class="text-muted-custom small">No messages have been submitted through the Contact form yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>From</th>
                  <th>Subject</th>
                  <th>Message</th>
                  <th>Received</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentMessages as $msg): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($msg['name']) ?></div>
                    <div class="small text-muted-custom"><?= htmlspecialchars($msg['email']) ?></div>
                  </td>
                  <td><?= htmlspecialchars(ucfirst($msg['subject'])) ?></td>
                  <td class="small"><?= htmlspecialchars(mb_strimwidth($msg['message'], 0, 60, '…')) ?></td>
                  <td class="small text-muted-custom"><?= htmlspecialchars($msg['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
