<?php
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data: blob:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Borrow what you need. Share what you love. Discover everyday rentals from your local community with BorrowHub.">
  <meta name="theme-color" content="#163b30"><title>BorrowHub · A little less buying. A lot more living.</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="assets/style.css">
  <script defer src="assets/art.js"></script><script defer src="assets/app.js"></script>
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <div class="announcement">Good things are better shared. <span>Your neighbourhood, a little closer.</span></div>
  <header class="header"><div class="nav-wrap"><a class="brand" href="#home" aria-label="BorrowHub home"><span class="brand-icon">b<span>↗</span></span>borrow<span class="brand-light">hub</span><span class="brand-dot">.</span></a><button class="menu-button" aria-label="Toggle navigation" aria-expanded="false" id="menu-button">☰</button><nav id="nav" aria-label="Main navigation"></nav></div></header>
  <main id="main" tabindex="-1"><div class="loading">Getting your neighbourhood ready…</div></main>
  <footer><div class="footer-inner"><div><a class="brand" href="#home">borrow<span class="brand-light">hub</span><span class="brand-dot">.</span></a><p>Less buying. More possibilities.<br>Made for a community that shares.</p></div><div><h3>Explore</h3><a href="#browse">Browse items</a><a href="#how">How it works</a><a href="#list">List an item</a></div><div><h3>Your BorrowHub</h3><a href="#dashboard">My rentals</a><a href="#profile">My account</a><a href="#contact">Contact & support</a></div><div class="footer-note"><span class="eyebrow">ROOTED IN COMMUNITY</span><p>A small idea with a lighter footprint.<br>Built in Sri Lanka. Shared by you.</p></div></div><div class="footer-bottom"><span>© <?php echo date('Y'); ?> BorrowHub. All rights reserved.</span><span>Rent locally. Return thoughtfully.</span></div></footer>
  <div id="toast" role="status" aria-live="polite"></div>
  <dialog id="modal"><button class="dialog-close" aria-label="Close dialog">×</button><div id="modal-content"></div></dialog>
  <noscript><p class="notice">Please enable JavaScript to use BorrowHub.</p></noscript>
</body></html>
