<?php
/**
 * header.php — shared <head> + navbar.
 * Expects the including page to set, before requiring this file:
 *   $base       = ''  for pages in the project root, '../' for pages in /auth
 *   $page_title = '<Page Name> — BorrowHub'
 *   $active     = one of: home, browse, contact, dashboard   (for nav highlighting)
 */
if (!isset($base))       $base = '';
if (!isset($page_title)) $page_title = 'BorrowHub';
if (!isset($active))     $active = '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $base ?>css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg bh-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= $base ?>index.php">
      <span class="navbar-brand-mark">B</span> BorrowHub
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bhNav" aria-controls="bhNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="bhNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="<?= $base ?>index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'browse' ? 'active' : '' ?>" href="<?= $base ?>browse.php">Browse Items</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>index.php#how-it-works">How It Works</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'contact' ? 'active' : '' ?>" href="<?= $base ?>contact.php">Contact</a></li>
        <?php if (is_logged_in()): ?>
        <li class="nav-item"><a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= $base ?>dashboard.php">Dashboard</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex gap-2 mt-3 mt-lg-0">
        <?php if (is_logged_in()): ?>
          <span class="align-self-center small text-muted-custom me-1">
            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?>
          </span>
          <a href="<?= $base ?>auth/logout.php" class="btn btn-bh-outline">Logout</a>
        <?php else: ?>
          <a href="<?= $base ?>auth/login.php" class="btn btn-bh-outline">Login</a>
          <a href="<?= $base ?>auth/register.php" class="btn btn-bh-amber">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
