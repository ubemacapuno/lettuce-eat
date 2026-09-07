---
paths:
  - compose.yaml
---

# General

## Never bind-mount over /app/database in the container
`/app/database` holds migrations, factories, and seeders. It is code, not just data. Mounting a volume over it hides them, and the container boots with "No migrations found" while silently creating an empty database with only a `migrations` table.

The SQLite file therefore lives at `/var/lib/lettuce-eat/database.sqlite` (`DB_DATABASE`), with `./data/database` mounted there. Avoid `/data` too — Caddy uses it for its own storage inside the FrankenPHP image.

Also keep `bootstrap/cache/*.php` in `.dockerignore`: those manifests are generated where require-dev packages exist, and the image installs `--no-dev`, so shipping them makes it boot referencing `Laravel\Boost\BoostServiceProvider`, which isn't there.
