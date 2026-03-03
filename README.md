# FlatFinder (InfinityFree-compatible PHP 8 scaffold)

This is a minimal PHP 8 scaffold for a map-based house rental marketplace (Leaflet map UI + simple admin panel).

## Deployment (InfinityFree)

InfinityFree’s web root is typically `htdocs/` (sometimes shown as `public_html/` depending on the tool).

1. **Create an InfinityFree account + website**
2. **Create the MySQL database** in the InfinityFree control panel.
3. **Import the schema**
   - Import `database/schema.sql` into your database.
4. **Upload files**
   - Upload everything inside `public_html/` from this repo into your hosting web root (`htdocs/`).
   - Upload the `config/` folder to the directory *above* `htdocs/` (same level as `htdocs/`).
     - This keeps `config/config.php` out of the public web root.
5. **Set your secrets**
   - Copy `config/config.example.php` to `config/config.php`.
   - Edit `config/config.php`:
     - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
     - `APP_SECRET` (change this)
   - (Optional) Bootstrap first admin login via config:
     - Generate a password hash locally:
       - `php -r "echo password_hash('your-strong-password', PASSWORD_DEFAULT), PHP_EOL;"`
     - Set `ADMIN_USERNAME` + `ADMIN_PASSWORD_HASH`.
     - This only auto-creates the first admin if the `admin_users` table is empty.
6. **Verify**
   - Visit your site root.
   - Visit `/api/ping` (or `/api/ping.php`) for a basic JSON health check.
   - Visit `/admin/login.php` to test admin auth.

## Local development

You can run this locally with PHP’s built-in server:

```bash
php -S 127.0.0.1:8000 -t public_html
```

Note: for local dev, keep `config/` as a sibling of `public_html/`.

## Security notes

- `public_html/storage/` is blocked from direct web access by `.htaccess`.
- Use prepared statements everywhere (`PDO::prepare`).

See `docs/RESTRICTIONS.md` for InfinityFree limitations and a migration plan.
