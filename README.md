# BorrowHub — Phase 3: PHP & MySQL Integration

ICT1209 – Web Technologies | Rajarata University of Sri Lanka
K.M. AASHIK – ITT/2024/001 · M.R.M. RISLAM – ITT/2024/090

A community rental platform. Phase 2 built the static frontend; Phase 3 adds a
PHP + MySQL backend: user accounts, a contact-form database, and full
CRUD-style item listings, all wired into the existing Bootstrap/JS frontend.

## Tech stack
- PHP 8 (plain PHP, no framework), PDO + MySQLi-compatible prepared statements
- MySQL / MariaDB (via XAMPP or WAMP)
- Bootstrap 5.3, Bootstrap Icons (CDN)
- Vanilla JavaScript (`js/main.js`) — client-side validation kept alongside server-side validation

## Folder structure
```
project/
├── css/
│   └── style.css
├── js/
│   └── main.js
├── images/
├── includes/
│   ├── db.php          — PDO connection (edit credentials here if needed)
│   ├── functions.php   — session bootstrap, sanitize(), auth helpers, flash messages
│   ├── header.php       — shared <head> + navbar (login-state aware)
│   └── footer.php       — shared footer + script tags
├── auth/
│   ├── register.php     — sign up (password_hash / PASSWORD_BCRYPT)
│   ├── login.php        — sign in (password_verify, session_regenerate_id)
│   └── logout.php       — session_destroy()
├── contact.php           — contact form → messages table
├── index.php             — homepage, featured items pulled from MySQL
├── browse.php             — full item catalogue pulled from MySQL (filters/sort still run client-side via main.js)
├── dashboard.php           — protected page: list/remove your own items, view recent contact messages
├── database.sql            — schema + seed data, exported for phpMyAdmin
└── README.md
```

## Setup (XAMPP / WAMP)

1. **Start Apache and MySQL** in the XAMPP/WAMP control panel.
2. **Create the database.** Open phpMyAdmin (`http://localhost/phpmyadmin`) →
   Import → choose `database.sql` → Go. This creates the `borrowhub`
   database with three tables (`users`, `messages`, `items`) and a demo
   owner account with two sample items.
3. **Copy the project folder** into your server's document root:
   - XAMPP: `C:/xampp/htdocs/borrowhub/`
   - WAMP: `C:/wamp64/www/borrowhub/`
4. **Check the DB credentials** in `includes/db.php` — the defaults
   (`root` / empty password / `localhost`) match a stock XAMPP/WAMP install.
   Change them if your MySQL user/password differ.
5. **Visit** `http://localhost/borrowhub/index.php` in your browser.

### Demo login
```
Email:    demo@borrowhub.lk
Password: Demo@1234
```

## Database schema

**users** — `id, username, email, password (bcrypt hash), created_at`

**messages** — `id, name, email, subject, message, created_at` (Contact form submissions)

**items** *(theme-specific table)* — `id, user_id (FK → users.id), title, category,
description, price_per_day, location, available, icon, created_at`

## Feature checklist (Phase 3 requirements)

| Requirement | Implementation |
|---|---|
| Local MySQL database | `database.sql`, database named `borrowhub` |
| `users` table | see schema above |
| `messages` table | see schema above |
| Theme-specific table | `items` — rental listings, linked to `users` via `user_id` |
| Registration (`register.php`) | `auth/register.php` — username, email, password |
| Passwords hashed | `password_hash($password, PASSWORD_BCRYPT)` |
| Login (`login.php`) | `auth/login.php` — validates credentials, starts session, redirects to dashboard |
| `session_regenerate_id()` after login | called immediately after successful `password_verify()` |
| Logout (`logout.php`) | `auth/logout.php` — clears `$_SESSION`, deletes the session cookie, `session_destroy()` |
| Contact form (`contact.php`) | `contact.php` — stores submissions in `messages`, shows a confirmation via a flash message (Post/Redirect/Get) |
| Forms submit & process via PHP + MySQL | contact form, register form, login form, add-item form, delete-item form all POST to PHP and hit the database |
| JS validation stays intact alongside server-side validation | `js/main.js` still does real-time/on-submit validation; forms only reach the server once client-side checks pass, and every field is re-validated server-side before touching the database |
| Prepared statements (PDO) | every query in `includes/db.php`-connected pages uses `$pdo->prepare()` with bound parameters — no raw string interpolation of user input |

## Notes on how the frontend and backend connect
- `index.php`, `browse.php`, `contact.php`, `dashboard.php`, `auth/*.php` replace
  the Phase 2 static `.html` files with the same markup/CSS classes, now
  rendered by PHP so they can read/write MySQL.
- `browse.php` renders the exact same `data-item` / `data-category` /
  `data-price` attributes that `main.js`'s `initBrowseFilters()` expects, so
  the live filter/sort/search UI keeps working — it now filters real
  database rows instead of hard-coded HTML.
- `includes/header.php` shows **Login/Register** or **Dashboard/Logout** +
  the current username depending on `is_logged_in()`, so the whole site
  reacts to the session state.
- The **Quick View** modal and hero **image slider** are unchanged from
  Phase 2 (pure JS/Bootstrap, no server data needed).

## Repository
https://github.com/Aashik-23/BorrowHub
