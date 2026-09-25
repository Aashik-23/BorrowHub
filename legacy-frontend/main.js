/* =========================================================
   BorrowHub — main.js
   Feature map (Phase 2 requirement: min. 3 JS features):
   1. Interactive image slider   -> initHeroSlider()
   2. Dynamic content updates    -> initBrowseFilters()
   3. Form validation            -> initContactForm(), initLoginForm()
   4. Smooth scrolling           -> initSmoothScroll()
   5. Event handling (hover/UI)  -> initPasswordToggle(), initBackToTop()
   6. Custom animations          -> initScrollReveal()
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
  initHeroSlider();
  initBrowseFilters();
  initQuickViewModal();
  initContactForm();
  initLoginForm();
  initSmoothScroll();
  initPasswordToggle();
  initBackToTop();
  initScrollReveal();
  initNavbarShadow();
});

/* ---------------------------------------------------------
   1. Interactive image slider (hero)
   Manual arrows + dot navigation + autoplay, pauses on hover.
--------------------------------------------------------- */
function initHeroSlider() {
  const slider = document.querySelector('[data-slider]');
  if (!slider) return;

  const slides = Array.from(slider.querySelectorAll('.bh-slide'));
  const dotsWrap = slider.querySelector('[data-slider-dots]');
  const prevBtn = slider.querySelector('.bh-slider-arrow.prev');
  const nextBtn = slider.querySelector('.bh-slider-arrow.next');
  let current = 0;
  let timer = null;
  const AUTOPLAY_MS = 4200;

  // build dots
  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
    if (i === 0) dot.classList.add('active');
    dot.addEventListener('click', () => goTo(i));
    dotsWrap.appendChild(dot);
  });
  const dots = Array.from(dotsWrap.children);

  function render() {
    slides.forEach((s, i) => s.classList.toggle('active', i === current));
    dots.forEach((d, i) => d.classList.toggle('active', i === current));
  }

  function goTo(i) {
    current = (i + slides.length) % slides.length;
    render();
  }

  function next() { goTo(current + 1); }
  function prev() { goTo(current - 1); }

  function play() {
    stop();
    timer = setInterval(next, AUTOPLAY_MS);
  }
  function stop() {
    if (timer) clearInterval(timer);
  }

  if (nextBtn) nextBtn.addEventListener('click', () => { next(); play(); });
  if (prevBtn) prevBtn.addEventListener('click', () => { prev(); play(); });

  slider.addEventListener('mouseenter', stop);
  slider.addEventListener('mouseleave', play);

  render();
  play();
}

/* ---------------------------------------------------------
   2. Dynamic content updates — browse page live filtering
   Filters by category checkbox, max price range, availability,
   and a text search — all without a page reload.
--------------------------------------------------------- */
function initBrowseFilters() {
  const grid = document.querySelector('[data-item-grid]');
  if (!grid) return;

  const cards = Array.from(grid.querySelectorAll('[data-item]'));
  const catBoxes = Array.from(document.querySelectorAll('[data-filter-category]'));
  const priceRange = document.querySelector('[data-filter-price]');
  const priceOutput = document.querySelector('[data-filter-price-output]');
  const availBox = document.querySelector('[data-filter-available]');
  const searchInput = document.querySelector('[data-filter-search]');
  const sortSelect = document.querySelector('[data-sort]');
  const countOutput = document.querySelector('[data-result-count]');
  const emptyState = document.querySelector('[data-empty-state]');
  const clearBtn = document.querySelector('[data-clear-filters]');

  function activeCategories() {
    const checked = catBoxes.filter(b => b.checked).map(b => b.value);
    return checked; // empty array = "all"
  }

  function applyFilters() {
    const cats = activeCategories();
    const maxPrice = priceRange ? Number(priceRange.value) : Infinity;
    const onlyAvailable = availBox ? availBox.checked : false;
    const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

    let visibleCount = 0;

    cards.forEach(card => {
      const cat = card.dataset.category;
      const price = Number(card.dataset.price);
      const available = card.dataset.available === 'true';
      const name = card.dataset.name.toLowerCase();

      const matchesCat = cats.length === 0 || cats.includes(cat);
      const matchesPrice = price <= maxPrice;
      const matchesAvailability = !onlyAvailable || available;
      const matchesSearch = query === '' || name.includes(query);

      const visible = matchesCat && matchesPrice && matchesAvailability && matchesSearch;
      card.style.display = visible ? '' : 'none';
      if (visible) visibleCount++;
    });

    if (countOutput) {
      countOutput.textContent = visibleCount + (visibleCount === 1 ? ' item found' : ' items found');
    }
    if (emptyState) emptyState.classList.toggle('show', visibleCount === 0);
  }

  function applySort() {
    if (!sortSelect) return;
    const val = sortSelect.value;
    const sorted = cards.slice().sort((a, b) => {
      if (val === 'price-asc') return Number(a.dataset.price) - Number(b.dataset.price);
      if (val === 'price-desc') return Number(b.dataset.price) - Number(a.dataset.price);
      if (val === 'rating-desc') return Number(b.dataset.rating) - Number(a.dataset.rating);
      return Number(a.dataset.newest) - Number(b.dataset.newest);
    });
    sorted.forEach(card => grid.appendChild(card));
  }

  catBoxes.forEach(box => box.addEventListener('change', applyFilters));
  if (availBox) availBox.addEventListener('change', applyFilters);
  if (searchInput) searchInput.addEventListener('input', debounce(applyFilters, 150));
  if (priceRange) {
    priceRange.addEventListener('input', () => {
      if (priceOutput) priceOutput.textContent = 'Rs. ' + priceRange.value;
      applyFilters();
    });
  }
  if (sortSelect) sortSelect.addEventListener('change', () => { applySort(); });
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      catBoxes.forEach(b => b.checked = false);
      if (availBox) availBox.checked = false;
      if (searchInput) searchInput.value = '';
      if (priceRange) {
        priceRange.value = priceRange.max;
        if (priceOutput) priceOutput.textContent = 'Rs. ' + priceRange.value;
      }
      applyFilters();
    });
  }

  // pre-select a category when arriving via a #category link (e.g. browse.html#electronics)
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    const match = catBoxes.find(b => b.value === hash);
    if (match) match.checked = true;
  }

  applyFilters();
}

function debounce(fn, delay) {
  let t;
  return function (...args) {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, args), delay);
  };
}

/* ---------------------------------------------------------
   2b. Quick View modal — populates a single Bootstrap modal
   with the clicked item's data (event delegation).
--------------------------------------------------------- */
function initQuickViewModal() {
  const modalEl = document.getElementById('quickViewModal');
  if (!modalEl) return;

  const els = {
    icon: modalEl.querySelector('#qvIcon'),
    category: modalEl.querySelector('#qvCategory'),
    name: modalEl.querySelector('#qvName'),
    desc: modalEl.querySelector('#qvDesc'),
    location: modalEl.querySelector('#qvLocation'),
    rating: modalEl.querySelector('#qvRating'),
    price: modalEl.querySelector('#qvPrice'),
    availability: modalEl.querySelector('#qvAvailability'),
  };

  const categoryLabels = {
    electronics: 'Electronics',
    tools: 'Tools & Equipment',
    sports: 'Sports & Outdoor',
    photography: 'Photography',
    study: 'Study Essentials',
    home: 'Home & Events',
  };

  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.quick-view-btn');
    if (!trigger) return;

    const item = trigger.closest('[data-item]');
    if (!item) return;

    const d = item.dataset;
    els.icon.className = 'bi ' + (d.icon || 'bi-box');
    els.category.textContent = categoryLabels[d.category] || d.category;
    els.name.textContent = d.name;
    els.desc.textContent = d.desc || '';
    els.location.textContent = d.location || '—';
    els.rating.textContent = '★ ' + d.rating;
    els.price.innerHTML = 'Rs. ' + d.price + ' <small>/ day</small>';

    const available = d.available === 'true';
    els.availability.textContent = available ? 'Available Today' : 'Currently Rented';
    els.availability.className = 'badge ' + (available ? 'badge-available' : 'badge-unavailable');
  });
}

/* ---------------------------------------------------------
   3a. Contact form validation (real-time + on submit)
--------------------------------------------------------- */
function initContactForm() {
  const form = document.querySelector('[data-contact-form]');
  if (!form) return;

  const name = form.querySelector('#contactName');
  const email = form.querySelector('#contactEmail');
  const subject = form.querySelector('#contactSubject');
  const message = form.querySelector('#contactMessage');
  const success = form.querySelector('[data-form-success]');

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function validateField(field) {
    let valid = true;
    if (field === name) {
      valid = name.value.trim().length >= 2;
    } else if (field === email) {
      valid = emailPattern.test(email.value.trim());
    } else if (field === subject) {
      valid = subject.value !== '';
    } else if (field === message) {
      valid = message.value.trim().length >= 10;
    }
    field.classList.toggle('is-invalid', !valid);
    field.classList.toggle('is-valid', valid);
    return valid;
  }

  [name, email, subject, message].forEach(field => {
    if (!field) return;
    field.addEventListener('input', () => validateField(field));
    field.addEventListener('blur', () => validateField(field));
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const results = [name, email, subject, message].map(validateField);
    const allValid = results.every(Boolean);

    if (allValid) {
      success.classList.add('show');
      form.reset();
      [name, email, subject, message].forEach(f => f && f.classList.remove('is-valid'));
      setTimeout(() => success.classList.remove('show'), 5000);
    } else {
      success.classList.remove('show');
      const firstInvalid = form.querySelector('.is-invalid');
      if (firstInvalid) firstInvalid.focus();
    }
  });
}

/* ---------------------------------------------------------
   3b. Login form validation
--------------------------------------------------------- */
function initLoginForm() {
  const form = document.querySelector('[data-login-form]');
  if (!form) return;

  const email = form.querySelector('#loginEmail');
  const password = form.querySelector('#loginPassword');
  const success = form.querySelector('[data-form-success]');
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function validateField(field) {
    let valid = true;
    if (field === email) valid = emailPattern.test(email.value.trim());
    if (field === password) valid = password.value.length >= 6;
    field.classList.toggle('is-invalid', !valid);
    field.classList.toggle('is-valid', valid);
    return valid;
  }

  [email, password].forEach(field => {
    if (!field) return;
    field.addEventListener('input', () => validateField(field));
    field.addEventListener('blur', () => validateField(field));
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const valid = [email, password].map(validateField).every(Boolean);
    if (valid && success) {
      success.classList.add('show');
      setTimeout(() => success.classList.remove('show'), 4000);
    }
  });
}

/* ---------------------------------------------------------
   4. Smooth scrolling for in-page nav links
--------------------------------------------------------- */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(link => {
    link.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href').slice(1);
      const target = document.getElementById(targetId);
      if (!target) return;
      e.preventDefault();
      const offset = 76; // fixed navbar height
      const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
      window.scrollTo({ top, behavior: 'smooth' });

      // collapse mobile navbar if open
      const nav = document.querySelector('.navbar-collapse.show');
      if (nav && window.bootstrap) {
        window.bootstrap.Collapse.getOrCreateInstance(nav).hide();
      }
    });
  });
}

/* ---------------------------------------------------------
   5. Event handling — password visibility toggle, back-to-top
--------------------------------------------------------- */
function initPasswordToggle() {
  document.querySelectorAll('[data-password-toggle]').forEach(icon => {
    icon.addEventListener('click', function () {
      const input = document.querySelector(this.dataset.passwordToggle);
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      this.classList.toggle('bi-eye');
      this.classList.toggle('bi-eye-slash');
    });
  });
}

function initBackToTop() {
  const btn = document.getElementById('backToTop');
  if (!btn) return;
  window.addEventListener('scroll', () => {
    btn.classList.toggle('show', window.scrollY > 420);
  });
  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

function initNavbarShadow() {
  const nav = document.querySelector('.bh-navbar');
  if (!nav) return;
  window.addEventListener('scroll', () => {
    nav.style.boxShadow = window.scrollY > 8 ? '0 4px 14px -8px rgba(20,50,41,.25)' : 'none';
  });
}

/* ---------------------------------------------------------
   6. Custom animation — fade/slide-in on scroll
--------------------------------------------------------- */
function initScrollReveal() {
  const items = document.querySelectorAll('.reveal');
  if (!items.length) return;

  if (!('IntersectionObserver' in window)) {
    items.forEach(el => el.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  items.forEach(el => observer.observe(el));
}
