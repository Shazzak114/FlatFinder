# InfinityFree restrictions + security constraints

This document captures the main practical constraints when running this project on InfinityFree free hosting, and how the codebase is structured to stay compatible.

## Runtime / hosting constraints

### No system command execution
InfinityFree does not allow executing system commands (e.g. `exec()`, `shell_exec()`) and does not allow changing hosting execution limits (e.g. `set_time_limit()`).

Source: InfinityFree forum response by the service owner.
- https://forum.infinityfree.com/t/subject-clarification-on-php-function-restrictions-exec-shell-exec-set-time-limit-on-infinityfr/94615

Implications:
- No FFmpeg binaries, no headless browsers, no server-side map tile generation, no background workers.
- Keep requests fast and avoid long-running operations.

### Cron jobs are not available (and alternatives may be blocked)
Cron jobs are not available on free hosting.

Source:
- https://forum.infinityfree.com/t/cron-jobs/108641

Implications:
- Don’t rely on scheduled jobs for cache warming, cleanup, notifications, etc.
- If you need periodic work later, move to paid hosting or an external job runner.

### Database feature limitations
InfinityFree free MySQL is intended as a “dumb data store”:
- Limited privileges (ALTER/CREATE/DELETE/DROP/INDEX/INSERT/SELECT/UPDATE/LOCK TABLES).
- Foreign keys, stored procedures, and triggers are disabled.
- MyISAM is used, so foreign key declarations are parsed but ignored.

Source:
- https://forum.infinityfree.com/t/complete-list-of-all-mysql-limitations/17174

Implications:
- Enforce constraints (FK integrity, cascades, validation) in PHP.
- Keep migrations simple (no triggers, no stored procedures).

### PHP configuration notes
- `allow_url_fopen` is enabled; `allow_url_include` is disabled.

Source:
- https://forum.infinityfree.com/t/allow-url-fopen-issue/91295

## Security constraints and how this scaffold addresses them

### Keep secrets out of the web root
- `config/config.php` is intended to live outside `public_html/`.
- `public_html/includes/config.php` (via `public_html/includes/bootstrap.php`) loads config from `../config/config.php`.

### Don’t expose user uploads directly
InfinityFree accounts are shared hosting environments. This scaffold blocks direct HTTP access to `public_html/storage/`.

- Web access to `/storage/...` is denied in `public_html/storage/.htaccess`.
- PHP scripts can still read/write those files from disk.

If you need public image delivery, implement a dedicated PHP endpoint that:
- validates access rules,
- sets correct `Content-Type`/caching headers,
- streams the image file.

### Use prepared statements everywhere
All database access should go through `PDO::prepare()` with bound parameters.

### CSRF protection
POST forms should include CSRF tokens and validate them server-side.

## Future migration plan (to VPS / managed hosting)

This project is structured to migrate cleanly:

- **Domain-neutral URLs**: prefer root-relative links (e.g. `/api/ping`) and avoid hardcoding full domains.
- **Environment config**: keep a single config file with constants; migrate later to environment variables (`.env`) when you have better control.
- **Storage**:
  - On a VPS, move `storage/` outside the web root and serve public images via a dedicated static directory or object storage.
  - Add proper image transformation (thumbnails) with background workers.
- **Jobs**:
  - Replace “run-on-request” workarounds with real cron/queue workers.
- **Database**:
  - Move to InnoDB and add foreign keys.
  - Add real migrations tooling if desired.
