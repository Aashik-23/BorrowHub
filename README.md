# BorrowHub — Phase 2: Frontend Layout & Design

ICT1209 – Web Technologies | Rajarata University of Sri Lanka
K.M. AASHIK – ITT/2024/001 · M.R.M. RISLAM – ITT/2024/090

## Live pages
| Page | File | Purpose |
|---|---|---|
| Home | `index.html` | Hero with search + custom slider, categories, featured items, how it works, CTA |
| Browse Items | `browse.html` | Filterable/sortable item grid (category, price, availability, search) |
| Contact | `contact.html` | Validated contact form, business hours, address |
| Login | `login.html` | Split-panel login form with validation and password visibility toggle |

## Tech used
- HTML5, semantic structure, three+ pages, shared navbar/footer
- CSS3 — custom design system in `css/style.css` (color tokens, typography, the signature "rental tag" card component)
- Bootstrap 5.3 (navbar, grid, forms, buttons) via CDN
- Bootstrap Icons via CDN
- Vanilla JavaScript in `js/main.js` — no frameworks

## Bootstrap 5 components used
- **Navbar** — sticky, collapsible on mobile
- **Cards** — the signature "rental tag" card (built on Bootstrap's grid/utility classes)
- **Carousel** — custom-built hero image slider on Home (JS feature #1 below), styled with the same look a `.carousel` would give
- **Modal** — a "Quick View" modal on Browse Items, opened with Bootstrap's native `data-bs-toggle="modal"`/`data-bs-target`, content filled dynamically per item

## JavaScript features implemented (5 of 6 required minimum 3)
1. **Interactive image slider** — custom-built hero carousel (`initHeroSlider`) with autoplay, manual arrows, dot navigation, pause-on-hover.
2. **Dynamic content updates** — live filtering and sorting on the Browse Items page (`initBrowseFilters`) by category, price range, availability and search text, with a results counter and empty state — no page reload.
3. **Form validation** — real-time + on-submit validation for the Contact form and Login form (`initContactForm`, `initLoginForm`), including email pattern matching and inline valid/invalid feedback.
4. **Smooth scrolling** — in-page anchor links (e.g. "How It Works") scroll smoothly with a fixed-navbar offset (`initSmoothScroll`).
5. **Event handling** — password show/hide toggle, back-to-top button, navbar shadow on scroll (`initPasswordToggle`, `initBackToTop`, `initNavbarShadow`).
6. **Custom animation** — scroll-triggered fade/slide-in reveal using `IntersectionObserver`, respects `prefers-reduced-motion` (`initScrollReveal`).

## Responsive design
Built mobile-first with Bootstrap's grid; verified at mobile (~375px), tablet (~768px) and desktop (~1200px+) breakpoints. Navbar collapses to an off-canvas menu on small screens.

## Folder structure
```
BorrowHub/
├── index.html
├── browse.html
├── contact.html
├── login.html
├── css/
│   └── style.css
├── js/
│   └── main.js
└── README.md
```

## Repository
https://github.com/Aashik-23/BorrowHub
