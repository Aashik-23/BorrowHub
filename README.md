# BorrowHub — Smart Community Rental Platform

A standalone PHP/MySQL application for borrowing and lending items locally. This project does not depend on, modify, or share tables with Community Service Provider.

If this installation includes `.local-credentials.txt`, setup has already been completed locally. Open that file in your editor for the generated administrator and demo-member passwords, and visit http://localhost/borrowhub/. The credentials file is blocked by Apache and excluded from Git. Do not share it with a public deployment.

## Start with XAMPP

1. Put this folder at `C:\xampp\htdocs\borrowhub`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open PowerShell in this folder and run `powershell -ExecutionPolicy Bypass -File install.ps1`.
4. Enter an administrator email and password when prompted. The installer can add optional fictional demo members and six illustrated items.
5. Open **http://localhost/borrowhub/**. Use the administrator credentials you chose or register a normal member account.

The installer is safe to repeat: it preserves existing accounts, passwords, items, and rentals. It is intended for this schema, not for upgrading unrelated databases. Never import the schema into the service-provider database.

Requirements: PHP 8.1+ with PDO MySQL, mbstring, fileinfo, and sessions; MySQL 5.7+ or MariaDB 10.3+; a modern browser. XAMPP PHP 8.2/MariaDB 10.4 was used for development. No npm install, build step, CDN, API key, or external image service is needed to run the app.

## Database configuration

Edit `config.php` or set these environment variables for Apache and command-line PHP:

| Variable | Default |
| --- | --- |
| BH_DB_HOST | 127.0.0.1 |
| BH_DB_PORT | 3306 |
| BH_DB_NAME | borrowhub |
| BH_DB_USER | root |
| BH_DB_PASS | empty, as in a fresh local XAMPP installation |

Time zone: Asia/Colombo. Prices: Sri Lankan rupees (LKR).

For manual installation, create and select the `borrowhub` database in phpMyAdmin, then import `database/schema.sql`. Run `setup.php` afterward to add categories and optionally an administrator. The SQL file deliberately does not choose or drop a database.

```powershell
$env:BH_ADMIN_EMAIL = Read-Host 'Administrator email'
$secret = Read-Host 'Administrator password (10–72 bytes)' -AsSecureString
$env:BH_ADMIN_PASSWORD = ([System.Net.NetworkCredential]::new('', $secret)).Password
try {
    & C:\xampp\php\php.exe setup.php
} finally {
    Remove-Item Env:BH_ADMIN_EMAIL, Env:BH_ADMIN_PASSWORD -ErrorAction SilentlyContinue
    Remove-Variable secret
}
```

`php setup.php` without admin variables installs the schema and categories only. `php setup.php --demo` also requires `BH_DEMO_PASSWORD`; it creates `nimal@example.test` and `amaya@example.test` and six explicitly marked fictional listings. Existing demo accounts are not reset. All member accounts can both borrow and lend.

## Included pages and features

- Home: original vector illustrations, categories, recent items, search, and how-it-works overview.
- Browse: keyword search, category, town/city, maximum daily price, availability today, price/newest sort, and pagination.
- Item details: photo, condition, owner, daily rate, deposit, booked date ranges, reviews, and live booking cost calculation.
- Accounts: registration, login/logout, profile, password changes, administrator-assisted one-time password reset.
- Listings: create, edit, image upload, availability pause, and removal with rental history preserved.
- Dashboard: borrowing, lending, listings, rental status, due/overdue reminders, and completed rental value.
- Rentals: requests, approval/rejection, date conflict protection, cancellation, pickup confirmation, return request, and owner-confirmed completion.
- Private rental messages and one review per completed rental.
- Contact form persisted to the administration inbox.
- Administration: summary totals, listing moderation, member suspension/reactivation, rental overview, contact resolution, and category management.
- Responsive layouts, keyboard-accessible controls, labelled forms, native modal dialogs, empty/error/loading states, and reduced-motion support.

The frontend uses HTML, original responsive CSS, and vanilla JavaScript. It does not require Bootstrap. The backend is PHP with prepared PDO queries and a separate MySQL database.

## Rental rules

Both pickup and return dates are chargeable. A one-day rental has identical start and end dates. Rentals last 1–90 days, beginning today or up to one year ahead. The server calculates and snapshots the daily rate, number of days, total, and deposit; the browser cannot set the amount.

```text
pending → accepted → active → return_requested → completed
   │          │
   ├ rejected └ cancelled
   └ cancelled
```

The owner accepts or rejects a pending request. A borrower can cancel their pending request. Either party can cancel an accepted rental before pickup. The owner confirms pickup during the booked period. The borrower requests return confirmation, then the owner confirms completion. Completed rentals are final and reviewable once by their borrower.

Pending requests do not reserve an item. Approval locks the item in a transaction, checks date conflicts, and declines other overlapping pending requests. Confirmed rentals block listing deletion. Deleting a listing soft-deletes it, rejects pending requests, and preserves past rentals. Pausing a listing stops new requests without erasing confirmed agreements. Hiding a listing removes it from public browsing without blocking the return of existing rentals.

## Payments, reminders, and recovery

**Payments are offline:** members arrange payment and any refundable deposit directly at pickup. No money is collected, charged, transferred, or held by BorrowHub. Completed rental value is a record of agreed prices, not verified earnings or platform revenue.

**Reminders are in-app:** the dashboard identifies returns due today and overdue rentals when loaded/refreshed. Messaging uses explicit send and refresh; no live socket server, email, SMS, or scheduled background worker is required.

**Password recovery is administrator-assisted:** after independently verifying the account owner's identity, run:

```powershell
& C:\xampp\php\php.exe reset-password.php member@example.com
```

Give the printed token privately to that member. They enter it through Log in → Forgot password. Tokens expire after 30 minutes, are stored only as hashes, and are single-use. Resetting a password invalidates existing sessions. There is no pretend email-delivery flow.

## Project layout

```text
borrowhub/
  index.php                 Application shell and security headers
  api.php                   JSON API / authorization / rental workflow
  lib.php                   Database, validation, uploads, sessions helpers
  config.php                Database connection settings
  setup.php                 CLI-only database/category/admin/demo installer
  reset-password.php        CLI-only one-time reset token issuance
  router.php                Protected PHP development-server router
  install.ps1               Interactive Windows setup
  assets/app.js             Client routing, pages, forms and interactions
  assets/art.js             Original SVG icons and item illustrations
  assets/style.css          Responsive interface styles
  database/schema.sql       Tables and relational constraints
  database/ERD.md           Data model and relationships
  uploads/                  Validated item photos, random file names
  tests/integration.cjs     Real HTTP/MySQL integration tests
```

## Database entities

`users`, `categories`, `items`, `rentals`, `reviews`, `messages`, `contacts`, and `password_resets`. Primary and foreign keys retain ownership and history. Rentals and messages are accessible only to their actual participants; the admin overview omits private conversation contents.

## Security and operations

Passwords use `password_hash`/`password_verify`. Session IDs rotate on login and registration. Cookies are HTTP-only and SameSite=Lax, with Secure on HTTPS. All write requests require a session CSRF token. Login, registration, bookings, messages, contact submissions, and password-reset attempts have local per-IP rate limits.

PDO prepared queries protect user input. Frontend HTML escaping prevents stored text from becoming markup. Authorization is checked server-side for each private action. Uploaded files must have a permitted MIME type, valid image dimensions, and a size no larger than 5 MB; PHP execution is denied in uploads. Set PHP `upload_max_filesize` to at least `5M` and `post_max_size` to at least `6M` to allow the full application upload limit (XAMPP may default lower).

Apache `.htaccess` files protect configuration, SQL, test files, and directory listings. The development router provides a static asset allowlist. Configure equivalent rules if using Nginx or another server. PHP's configured session directory and temporary directory must be writable. Server errors are logged rather than returned with database details.

For public deployment, use HTTPS, a dedicated database user with only the required permissions, non-demo accounts, backups, server-level logging, and a shared rate-limit store if running multiple servers. Local rate-limit files are temporary. Original/replaced photos are not automatically purged. Administrator tables show the latest 200 rows; rental messages and a member's rental history are loaded as a whole, appropriate for this coursework-sized application.

## Run without Apache (optional)

With MySQL running, from this directory:

```powershell
& C:\xampp\php\php.exe -d upload_max_filesize=5M -d post_max_size=6M -S 127.0.0.1:8080 router.php
```

Open http://127.0.0.1:8080/. Use `router.php` so private files are not served. Do not run the site using `file:///` or Live Server.

## Verification

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & C:\xampp\php\php.exe -l $_.FullName }
node --check assets/app.js
node --check assets/art.js
node tests/integration.cjs
```

Tests require Node 20+, PHP, and a running MySQL server. Override `PHP_BINARY` if PHP is elsewhere. They honor the `BH_DB_*` connection variables but create a randomly named `bh_test_*` database instead of using your configured application's database. Tests start a temporary HTTP server, create test accounts, check the complete rental lifecycle, permissions, uploads, moderation, messages, reviews, recovery, and then remove their own database and uploaded image.

## Coursework walkthrough

1. Run the installer with optional demo data.
2. Browse the home page, search “camera,” and open the camera details.
3. Register a borrower and request today’s date.
4. Sign in as `nimal@example.test` using the demo password you chose.
5. Open My rentals → I’m lending, accept the request, and confirm pickup.
6. Sign in as the borrower, send a private message, then request return confirmation.
7. Sign in as the owner and confirm the return.
8. Sign in as the borrower and leave a review. View it on the item page.
9. Sign in as administrator to review rentals, moderate listings, and read contact messages.

Project authors in the proposal: K. M. Aashik and M. R. M. Rislam.

## Repository history

The original static prototype is preserved in legacy-frontend/. The runnable PHP/MySQL application is at the repository root; follow the XAMPP setup above. Database exports and local account credentials are supplied privately, not committed to this public repository.

