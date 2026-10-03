# RAVION Pharma Admin - No SSH Deployment Guide

This guide is for hosting accounts where you only have **File Manager / Upload** access.

## 1) What to upload
Upload the full project folder contents, including:
- `app/`
- `public/`
- `system/` (from CodeIgniter install)
- `vendor/` (important, because server cannot run Composer)
- `writable/`
- `.env`
- `spark` (not required for runtime, but safe to keep)

## 2) Pre-upload checks (local)
Confirm these exist locally before upload:
- `vendor/` directory exists
- `.env` is present and configured

Database config now uses MySQL by default:
- Host: `127.0.0.1`
- User: `root`
- Password: ``
- Database: `ravion_pharma`
- Driver: `MySQLi`

Update these values in `app/Config/Database.php` for your hosting MySQL credentials.

Current login seed:
- Email: `admin@ravionpharma.com`
- Password: `Admin@123`

## 3) Hosting setup (Control Panel)
In your hosting panel:
1. Set PHP version to **8.1+** (8.2/8.3 preferred)
2. Ensure PHP extensions are enabled:
   - `mysqli`
   - `pdo_mysql`
   - `mbstring`
   - `intl`
   - `curl`
   - `xml`
   - `json`
3. Set write permissions for `writable/` (usually 775; if strict shared hosting, use panel options accordingly)
4. Create MySQL database/user from hosting panel (for example via cPanel MySQL Databases) and grant all privileges.

## 4) URL to open
If project is uploaded inside a folder `ravion-pharma-admin` under public web root:
- `https://your-domain.com/ravion-pharma-admin/public/index.php/login`

If your hosting lets you point domain/subdomain document root directly to this project's `public/` folder:
- `https://your-domain.com/index.php/login`

## 5) First login test
1. Open login URL
2. Sign in with seeded admin credentials
3. Verify pages load:
   - Dashboard
   - Products
   - Doctors
   - Chemists
   - Visit Log
   - TP & POB Workflow
   - Reports

## 6) Troubleshooting
### Blank page / 500 error
- Check PHP version is 8.1+
- Verify `vendor/` uploaded fully
- Verify `.env` uploaded

### Database connection errors
- Confirm MySQL DB name/user/password/host in `app/Config/Database.php`
- Ensure MySQL user has full privileges on your database
- If host blocks `127.0.0.1`, try `localhost`

### CSS/links or route issues
- Use `index.php` URL form first (`.../index.php/login`)
- If clean URLs needed, enable rewrite in hosting panel and use proper document root to `public/`

## 7) Web DB Sync API (no console)
If you cannot run migration commands on server, use secure API endpoint:

1. Set a strong token in `.env`:
   - `db.sync.token = your-strong-random-token`
2. Trigger sync endpoint (POST request):
   - URL: `https://your-domain.com/ravion-pharma-admin/public/index.php/api/db/sync`
   - Header: `X-DB-SYNC-TOKEN: your-strong-random-token`

Optional: run seed data in same call:
- Add query param `?seed=1`

Example cURL (from local machine):
`curl -X POST "https://your-domain.com/ravion-pharma-admin/public/index.php/api/db/sync?seed=1" -H "X-DB-SYNC-TOKEN: your-strong-random-token"`

What this API does:
- Creates MySQL database if missing
- Runs migrations
- Ensures required tables/columns exist (self-heal)
