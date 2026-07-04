# RISER — Go Live Checklist

Follow top to bottom. Each step says exactly what to click or type.

---

## PART 1 — Push to GitHub

- [ ] **1.1** Go to https://github.com/new
- [ ] **1.2** Repository name: `riser-store` (or whatever you want)
- [ ] **1.3** Leave "Add a README" and "Add .gitignore" **unchecked** (you already have both)
- [ ] **1.4** Click **Create repository**
- [ ] **1.5** GitHub will show you a page with commands. Ignore it — use these instead. Open a terminal, `cd` into the unzipped `riser` folder, and run:

```bash
git remote add origin https://github.com/YOUR_USERNAME/riser-store.git
git push -u origin main
```

Replace `YOUR_USERNAME` with your actual GitHub username. It'll ask you to log in (browser popup or token) — follow the prompt.

- [ ] **1.6** Refresh the GitHub page — you should see all your files there.

---

## PART 2 — Create your free hosting account

- [ ] **2.1** Go to https://infinityfree.net and click **Sign Up** (or **Client Area** → Register)
- [ ] **2.2** Verify your email (check inbox/spam)
- [ ] **2.3** Once logged in, click **Create Account** and pick a free subdomain, e.g. `riserstore.infinityfreeapp.com`
- [ ] **2.4** Wait for the account status to say **Active** (can take a few minutes)

---

## PART 3 — Create the database

- [ ] **3.1** In the InfinityFree control panel (vPanel), click **MySQL Databases**
- [ ] **3.2** Click **Create Database**, name it e.g. `riser_store` — it'll actually be created as something like `if0_12345678_riser_store`
- [ ] **3.3** Write down these 4 things from the panel (you'll need them twice, in Parts 4 and 6):

```
DB Host:     ___________________________  (looks like sqlXXX.infinityfree.com)
DB Name:     ___________________________  (looks like if0_12345678_riser_store)
DB Username: ___________________________  (looks like if0_12345678)
DB Password: ___________________________  (the one you set when creating it)
```

- [ ] **3.4** Click **phpMyAdmin** (also in the vPanel)
- [ ] **3.5** Select your new database in the left sidebar
- [ ] **3.6** Click the **Import** tab at the top
- [ ] **3.7** Click **Choose File**, select `database.sql` from your unzipped `riser` folder
- [ ] **3.8** Scroll down, click **Go**
- [ ] **3.9** Confirm it says success and you now see tables like `products`, `orders`, `categories` in the sidebar

---

## PART 4 — Get FTP credentials

- [ ] **4.1** Back in the vPanel, click **FTP Accounts**
- [ ] **4.2** Write down these 3 things:

```
FTP Server:   ___________________________  (looks like ftpupload.net)
FTP Username: ___________________________  (looks like if0_12345678)
FTP Password: ___________________________  (same as your account password, usually)
```

---

## PART 5 — Connect GitHub to auto-deploy

- [ ] **5.1** On your GitHub repo page, click **Settings** (top right of the repo, not your account settings)
- [ ] **5.2** In the left sidebar: **Secrets and variables → Actions**
- [ ] **5.3** Click **New repository secret** — add all three, one at a time:

| Name | Value |
|---|---|
| `FTP_SERVER` | the FTP Server from Part 4 |
| `FTP_USERNAME` | the FTP Username from Part 4 |
| `FTP_PASSWORD` | the FTP Password from Part 4 |

- [ ] **5.4** Go to the **Actions** tab of your repo
- [ ] **5.5** You should see a workflow run already in progress (or click **Re-run all jobs** on the most recent one if not) — this is it uploading your files
- [ ] **5.6** Wait for the green checkmark ✅ (takes 1-3 minutes)

---

## PART 6 — Finish setup on the live server

- [ ] **6.1** In InfinityFree vPanel, open **File Manager**
- [ ] **6.2** Navigate into `htdocs/includes/`
- [ ] **6.3** Find `config.sample.php`, right-click → **Copy**, paste it in the same folder, rename the copy to `config.php`
- [ ] **6.4** Right-click `config.php` → **Edit**, and fill in the real values from Part 3.3:

```php
define('DB_HOST', 'sqlXXX.infinityfree.com');        // from Part 3.3
define('DB_NAME', 'if0_12345678_riser_store');       // from Part 3.3
define('DB_USER', 'if0_12345678');                   // from Part 3.3
define('DB_PASS', 'your_real_password');             // from Part 3.3
define('DEBUG_MODE', false);
```

- [ ] **6.5** Save the file
- [ ] **6.6** Visit `https://your-subdomain.infinityfreeapp.com/setup.php` in your browser
- [ ] **6.7** Enter a username and password for your admin account, click **Create Admin Account**
- [ ] **6.8** In File Manager, **delete** `setup.php` from `htdocs/` (it has no login protection — don't leave it there)
- [ ] **6.9** Visit `https://your-subdomain.infinityfreeapp.com/admin/login.php` and confirm you can log in

---

## PART 7 — Confirm the store actually works

- [ ] **7.1** Visit your homepage — do products show up?
- [ ] **7.2** Add a cap to your cart, go through checkout with a test order
- [ ] **7.3** Log into `/admin/dashboard.php` and confirm the test order appears
- [ ] **7.4** Delete the test order from the admin panel (Orders → find it → you can leave status as-is or just ignore it, it's your call)

---

## You're done. From now on:

Any time you want to change something — edit a file, ask me for a new
feature, whatever — just `git push` when it's ready, and the GitHub Action
in Part 5 re-deploys it automatically. No more manual FTP uploads needed
after this point.

## If something breaks

- **Blank white page:** set `DEBUG_MODE` to `true` temporarily in
  `config.php`, reload, read the error, then set it back to `false`.
- **"Database connection failed":** double check the 4 values in Part 3.3
  are typed exactly right in `config.php` — this is the #1 cause.
- **GitHub Action shows a red ✗:** click into it and read the log — almost
  always a wrong FTP secret from Part 5.3.
