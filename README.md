# RISER — Streetwear Snapback E-Commerce Store
### Pakistan-based Cap Brand | LAMP Stack | PHP + MySQL + HTML/CSS/JS

---

## Project Structure

```
riser/
├── index.php              ← Homepage (animated hero, featured, new arrivals)
├── shop.php               ← Shop with filters (category, price, search) + sort
├── product.php            ← Product detail page (variant swatches, AJAX add-to-cart)
├── featured.php           ← Featured caps
├── new-arrivals.php       ← New arrivals
├── cart.php               ← Cart page (update quantities, remove items)
├── checkout.php           ← COD checkout form with full server-side validation
├── order-success.php      ← Order confirmation page
├── about.php              ← Brand story
├── contact.php            ← Contact form
├── cart-actions.php       ← AJAX endpoint for cart operations
├── setup.php              ← One-time admin account setup (delete after use)
├── database.sql           ← MySQL schema + sample data (8 products)
│
├── includes/
│   ├── db.php             ← DB connection, site constants (edit credentials here)
│   ├── functions.php      ← Cart, formatting, helper functions
│   ├── header.php         ← Shared site header + nav
│   ├── footer.php         ← Shared footer
│   └── product-card.php   ← Reusable product card partial
│
├── css/
│   └── style.css          ← Full design system: tokens, layout, animations
│
├── js/
│   └── main.js            ← Nav, scroll-reveal, AJAX cart, PDP variant logic
│
├── images/
│   ├── placeholder.svg    ← Fallback SVG for missing product images
│   ├── products/          ← Upload product images here (see filenames in DB)
│   └── categories/        ← Upload category images here (slugs as filenames)
│
└── admin/
    ├── login.php           ← Admin login
    ├── logout.php          ← Admin logout
    ├── dashboard.php       ← Stats, recent orders, low-stock alerts
    ├── products.php        ← Product list
    ├── product-edit.php    ← Add / edit product + variants
    ├── orders.php          ← Orders list with status filter
    ├── order-detail.php    ← Order detail + status update
    ├── auth.php            ← Session guard
    ├── admin.css           ← Admin panel styles
    └── includes/
        ├── header.php      ← Admin sidebar/nav header
        └── footer.php      ← Admin footer
```

---

## Setup Instructions

### 1. Requirements
- PHP 8.1+ (with PDO and pdo_mysql extension)
- MySQL 5.7+ or MariaDB 10.6+
- Apache or Nginx web server
- Apache: mod_rewrite enabled (or use the index.php entry points as-is)

### 2. Database
```sql
-- In your MySQL client or phpMyAdmin:
source /path/to/riser/database.sql
```
This creates the `riser_store` database, all tables, and 8 sample products with variants.

### 3. Database credentials
Copy `includes/config.sample.php` to `includes/config.php` and fill in your real values:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'riser_store');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DEBUG_MODE', false); // true is fine locally, set false once live
```
`includes/config.php` is in `.gitignore` — it never gets committed, so your real password stays off GitHub. If `config.php` doesn't exist, the site falls back to `root` / no password / `localhost`, which works out of the box on local XAMPP/MAMP.

### 4. Admin account
Visit `http://yourdomain.com/setup.php` in your browser.
- Enter a username (default: `admin`) and a password (min 6 chars).
- Click "Create Admin Account".
- **Delete `setup.php` immediately after** — it has no authentication.

### 5. Product images
Upload product images to `/images/products/`. Filenames must match the `image` column in the `products` table (e.g. `classic-black.jpg`).

Upload category images to `/images/categories/` using the category slug as filename (e.g. `snapbacks.jpg`, `trucker-caps.jpg`).

If an image is missing, the site falls back to `/images/placeholder.svg` automatically.

### 6. Shipping fee
Flat COD shipping fee is set in `includes/db.php`:
```php
defined('SHIPPING_FEE') || define('SHIPPING_FEE', 200.00); // PKR
```

---

## Admin Panel

URL: `/admin/login.php`

| Page | URL | What it does |
|---|---|---|
| Dashboard | `/admin/dashboard.php` | Stats, recent orders, low-stock alerts |
| Products | `/admin/products.php` | List, delete products |
| Add Product | `/admin/product-edit.php` | Create new product with variants |
| Edit Product | `/admin/product-edit.php?id=N` | Edit existing product + variants |
| Orders | `/admin/orders.php` | All orders, filterable by status |
| Order Detail | `/admin/order-detail.php?id=N` | Full order info + update status |

### Order statuses
`pending` → `confirmed` → `shipped` → `delivered` (or `cancelled`)

---

## Features

### Customer store
- Animated hero with floating cap, rotating ring, scroll-in animations
- Scrolling ticker strip (woven-label signature design element)
- Shop page: category filter (multi-select), price range filter, search, sort
- Product detail: live size/color variant swatches with stock check
- AJAX "Add to Cart" with toast notifications and cart badge update
- Session-based cart (no login needed) — quantity update, item remove
- COD checkout with full server-side validation (Pakistani phone number, province dropdown)
- Stock re-verification at order placement, transactional order write
- Order confirmation page with full summary
- About page, contact form with validation
- Fully responsive down to mobile
- Respects `prefers-reduced-motion`

### Admin panel
- Secure session-based login (bcrypt passwords)
- Dashboard with key metrics + low-stock alerts
- Add/edit products with dynamic variant rows (add/remove via JS)
- Variant management: size, color, color hex, stock quantity
- Featured + New Arrival toggles
- Delete product with confirmation
- Order list with status filter pills
- Order detail with dropdown status update
- All sensitive pages protected by `requireAdminLogin()`

---

## Design System

**Colors:** Near-black `#0A0A0A`, white `#FFFFFF` / off-white `#F4F3F0`, warm leather-brown accent `#8B6A4F`, grey canvas backdrop `#E9E7E2`
**Fonts:** Archivo Black (display) + Inter (body) + JetBrains Mono (labels, prices, UI)
**Signature:** Whole site sits in one rounded "framed canvas" card, pill buttons with a circular arrow badge, numbered filmstrip product gallery, sliding-highlight pill navbar

---

## Deploying to GitHub + a Live Site

GitHub itself only hosts static files — it can't run PHP or MySQL. So there
are two separate steps: (1) push the code to GitHub for version control,
and (2) deploy it somewhere that actually runs PHP, and connect the two so
new pushes go live automatically.

### 1. Push to GitHub

```bash
cd riser
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/riser-store.git
git push -u origin main
```

`includes/config.php` and anything else in `.gitignore` will **not** be
pushed — that's intentional, it keeps your DB password out of the repo.

### 2. Pick PHP + MySQL hosting

Any of these work; pick based on budget and how hands-on you want to be:

| Option | Good for | Notes |
|---|---|---|
| **InfinityFree** (or Byet.host, same infrastructure) | **Free**, real PHP 8 + unlimited MySQL, no credit card | FTP-only deploy natively — see the GitHub Actions auto-deploy setup below to bridge that gap |
| **Shared hosting** (Hostinger, Namecheap, cPanel hosts) | Cheapest paid option, simplest, common in Pakistan | Usually has a "Git" deploy feature in cPanel, or use FTP/File Manager |
| **Koyeb** | Modern, native GitHub-native deploys | Free instance available but requires a credit card for verification (not charged); free database is Postgres, not MySQL, so you'd need to swap `PDO` DSN or run MySQL as a separate one-click app |
| **DigitalOcean / a VPS** | Full control | You manage Apache/Nginx + MySQL yourself |

### 3a. Free path: InfinityFree + GitHub Actions auto-deploy (recommended if budget is $0)

This repo already includes `.github/workflows/deploy.yml`, which FTP-uploads
your site automatically every time you push to `main`.

1. Sign up free at [infinityfree.net](https://infinityfree.net) (no credit
   card) and create a hosting account — you'll get a free subdomain like
   `yoursite.infinityfreeapp.com`, or you can point your own domain at it.
2. In your InfinityFree control panel, open **MySQL Databases** and create
   one — note the database name, username, password, and hostname it gives
   you (usually something like `sqlXXX.infinityfree.com`).
3. Open **phpMyAdmin** from the control panel, select your new database,
   and use **Import** to run `database.sql`.
4. Open **FTP Accounts** in the control panel and note the FTP hostname,
   username, and password.
5. In your GitHub repo: **Settings → Secrets and variables → Actions → New
   repository secret**. Add three secrets: `FTP_SERVER`, `FTP_USERNAME`,
   `FTP_PASSWORD` using the values from step 4.
6. Push to `main`. Check the **Actions** tab on GitHub — you'll see the
   deploy run and upload your files into InfinityFree's `htdocs/` folder.
7. On the server, create `includes/config.php` (there's no way around this
   step — it's intentionally excluded from the auto-deploy and from git,
   since it holds your real DB password). Use InfinityFree's File Manager
   to create it directly, copying from `config.sample.php`, filled in with
   the database details from step 2. Set `DEBUG_MODE` to `false`.
8. Visit `https://yoursite.infinityfreeapp.com/setup.php` once to create
   your admin login, then delete `setup.php` via File Manager.

From then on: every `git push` to `main` re-deploys automatically. Free
tier limits to know about: ~50,000 hits/day fair-use cap, 10-second max PHP
execution time, no cron jobs. Fine for a store getting started; if you
outgrow it, Byet.host (same underlying infrastructure) is a natural
fallback, or move up to paid shared hosting below.

### 3b. Paid path: connect GitHub to shared hosting / Koyeb

- **Shared hosting with Git support (most cPanel hosts):** in cPanel, use
  "Git Version Control" → paste your GitHub repo URL → it clones and can
  auto-pull on each push via a webhook.
- **Shared hosting without Git support:** simplest path — download the repo
  as a zip from GitHub (or `git pull` locally then upload via FTP/File
  Manager) each time you deploy. Not automatic, but reliable.
- **Koyeb:** connect the GitHub repo directly in their dashboard; every push
  to `main` triggers a redeploy automatically. Requires adapting the DB
  layer to Postgres (Koyeb's native free database) or running MySQL as a
  separate one-click app service.

### 4. On the server, after the code is there

1. Import `database.sql` into your host's MySQL database (phpMyAdmin →
   Import, or `mysql -u user -p dbname < database.sql`).
2. Create `includes/config.php` **directly on the server** (copy from
   `config.sample.php`) with your live DB credentials. Since it's
   gitignored, this file has to be created manually on each environment —
   it won't arrive via git.
3. Set `DEBUG_MODE` to `false` in that same `config.php`.
4. Visit `https://yourdomain.com/setup.php` once to create your admin
   login, then delete `setup.php` (or leave the `.htaccess` block on it —
   see the note inside `.htaccess`).
5. Point your domain's DNS at the host if it isn't already, and confirm
   HTTPS is active (most hosts provision a free SSL cert automatically).

### Notes
- If your host uses a different MySQL socket/port, adjust the PDO DSN in
  `includes/db.php`.
- To change the COD shipping amount or embroidery fee, edit
  `includes/config.php` (or `includes/db.php` for the fallback defaults).
- `.htaccess` already blocks direct access to `setup.php` and `database.sql`
  at the server level as a second layer of protection — see the comment
  in that file if you need to temporarily re-enable `setup.php`.

---

## Changelog — July 2026 Update

### 🔒 Security fixes
- Added CSRF protection (session token) to checkout, contact, admin login, cart actions, product delete, and order status update.
- Product delete moved from an unauthenticated `GET` link to a confirmed `POST` form (was vulnerable to CSRF / accidental crawler deletion).
- Added session-based login throttling (5 attempts / 15 min) on `/admin/login.php`.
- Admin/auth pages now send `X-Robots: noindex`; transactional pages (`order-success.php`) are excluded from search indexing.
- `.htaccess` blocks direct access to `setup.php` and `.sql` files and adds standard security headers.
- Cart is now keyed by a collision-safe string `cart_key` instead of raw variant IDs (the old structure could silently collide once custom items were introduced).

### 🚀 Performance / SEO
- Per-page meta description, meta keywords, Open Graph, Twitter Card, and canonical tags (`includes/header.php`), targeting: *caps in Karachi, caps in Pakistan, caps store in Pakistan, streetwear caps, embroidered caps, embroidered clothes*.
- JSON-LD structured data: `ClothingStore` schema sitewide, `Product` schema on every PDP.
- `robots.txt` + dynamic `sitemap.php` (auto-includes every active product and category).
- `loading="lazy"` + `decoding="async"` on below-the-fold images; descriptive, keyword-rich `alt` text.
- `.htaccess` gzip compression + browser caching for static assets.
- `main.js` now loads with `defer`.
- New DB indexes (`idx_products_active_featured`, `idx_orders_status`, etc.) + a `FULLTEXT` index on product name/description for faster search.

### ✨ New: Live Embroidery Customizer
A genuinely new feature (not just a re-skin) — on every product page, customers can type up to 12 characters of custom embroidery text, pick a thread color, and see a live canvas preview stitched onto the cap before adding it to their cart. Each custom order is tracked as its own production line in `order_items` (`custom_text`, `thread_color`, `custom_fee` columns) so the admin team can fulfil it correctly. Adds a flat `CUSTOM_EMBROIDERY_FEE` (configurable in `includes/db.php`).

### 📊 New: Redesigned Admin Dashboard
`admin/dashboard.php` now shows:
- Orders / Revenue / Avg Order Value with trend badges (last 30 days vs. previous 30 days)
- "Total Profit" panel with an SVG revenue sparkline
- Best Selling Products (by units sold)
- Orders-by-Region segmented bar
- "Most Day Active" weekday bar chart
- Repeat Customer Rate donut
- Store Insights panel — including a live **Embroidery Customizer adoption %**, a metric unique to this build
- One-click "Export Orders CSV" (`admin/export-orders.php`)

### 🗄️ Database
Run `database.sql` for a fresh install. If you already have a RISER database, run `migrations/2026_07_update.sql` once instead.
