# Self-hosting Lettuce Eat on a Raspberry Pi

Runs the app on a Pi 4B, reachable only from your Tailscale tailnet. Nothing is
exposed to the internet and no ports are forwarded on your router.

---

## What you end up with

```
your laptop / phone                    Raspberry Pi 4B
─────────────────────                  ─────────────────────────────────────
  Tailscale client                       tailscale serve  (TLS terminates here)
         │                                        │
         │  encrypted over your tailnet           │  plain HTTP, loopback only
         └───────────────────────────────────────►│
                                                  ▼
                                          127.0.0.1:8080
                                                  │
                                                  ▼
                                       Docker: lettuce-eat
                                       FrankenPHP (Caddy + PHP)
                                                  │
                                                  ▼
                                       SQLite file on a bind mount
```

One container. No nginx, no php-fpm, no database server, no queue worker.

### Why these choices

**SQLite.** The app already defaults to it, and sessions, cache, and queue all
live in the database. For a single user that removes an entire container from
the stack. Set to WAL mode so reads don't block behind writes.

**One container, FrankenPHP.** FrankenPHP is Caddy with PHP embedded, so the
usual nginx + php-fpm pair collapses into a single process with no socket
wiring between them.

**No queue worker or scheduler.** There is no `app/Jobs` directory and nothing
dispatches jobs or schedules tasks. If that changes, this doc needs a worker.

**`tailscale serve`, never `tailscale funnel`.** `serve` publishes to your
tailnet only. `funnel` publishes to the public internet — that is the one
command to avoid here.

---

## Prerequisites

**1. Confirm the Pi is running 64-bit OS.**

```bash
uname -m
```

Must print `aarch64`. If it prints `armv7l` you are on 32-bit Raspberry Pi OS
and the images here will not run — reflash with the 64-bit build. A 4B handles
64-bit fine.

**2. Install Docker.**

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker "$USER"
```

Log out and back in for the group change to apply.

Use this installer rather than Debian's own packages. It registers Compose as the
`docker compose` subcommand, which is what every command in this document assumes.

If you install from apt instead (`apt-get install docker.io docker-compose`), you get
the standalone `docker-compose` binary and no plugin subcommand, so every
`docker compose ...` below has to become `docker-compose ...`. Check which one you have:

```bash
docker compose version || docker-compose version
```

Note that the apt package names differ by release. Debian trixie calls it
`docker-compose` (v2), while other releases and Ubuntu use `docker-compose-v2`, so a
script that hardcodes one name will fail on the other.

**3. Install Tailscale and join your tailnet.**

```bash
curl -fsSL https://tailscale.com/install.sh | sh
sudo tailscale up
```

**4. In the Tailscale admin console**, enable **MagicDNS** and **HTTPS
Certificates** under DNS settings. `tailscale serve` cannot issue a certificate
without both.

**5. Give the Pi read access to the repo** (skip if the repo is public).

The repo does not need to be public. Use a **deploy key** — scoped to this one
repo, read-only, and it never expires, so `git pull` keeps working unattended.
Prefer it over `gh auth login` (which grants the Pi access to your whole GitHub
account) and over a personal access token (stored in plaintext, and expires).

```bash
ssh-keygen -t ed25519 -C "raspberrypi-lettuce-eat" -f ~/.ssh/id_ed25519_lettuce -N ""
cat ~/.ssh/id_ed25519_lettuce.pub
```

Add that public key at **repo → Settings → Deploy keys → Add deploy key**, and
leave *Allow write access* unchecked — the Pi only ever pulls.

Then tell git which key to use:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_ed25519_lettuce
    IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config

ssh -T git@github.com
```

That last command should answer `Hi <you>/<repo>! You've successfully
authenticated, but GitHub does not provide shell access.` — that message is
success, not an error.

---

## Deploy

```bash
# SSH URL, not HTTPS — HTTPS ignores the deploy key and prompts for a password.
git clone git@github.com:<you>/<repo>.git lettuce-eat
cd lettuce-eat

mkdir -p data/database data/storage

cp .env.production.example .env.production
```

Generate an app key and put it in `.env.production`:

```bash
echo "APP_KEY=base64:$(openssl rand -base64 32)"
```

Then edit `.env.production` and set:

- `APP_KEY` — the line you just generated
- `APP_URL` — your Tailscale hostname, e.g. `https://raspberrypi.tail1234.ts.net`
  (get it from `tailscale status --json | grep DNSName`, minus the trailing dot)

Build and start:

```bash
docker compose up -d --build
```

First build takes roughly 5–10 minutes on a Pi 4B — it compiles the frontend
and installs PHP dependencies. Subsequent builds are much faster thanks to
layer caching.

Check it came up:

```bash
docker compose ps          # should show "healthy" after ~40s
curl -fsS localhost:8080/up
```

Migrations run automatically on every container start, so there is no separate
migrate step.

---

## Publish it to your tailnet

```bash
sudo tailscale serve --bg 8080
sudo tailscale serve status
```

That's it. The app is now at `https://<pi-hostname>.<your-tailnet>.ts.net` with
a real Let's Encrypt certificate, reachable from any device signed into your
tailnet and from nowhere else.

The container is bound to `127.0.0.1:8080` in `compose.yaml`, so it is not
reachable from your LAN either — only through Tailscale.

To stop publishing:

```bash
sudo tailscale serve --https=443 off
```

---

## Create your account, then close the door

Visit `https://<pi-hostname>.<tailnet>.ts.net/register` and create your user.

Once that's done, delete the two registration routes in `routes/auth.php`
(the `register` GET and POST), commit, and redeploy. Only your tailnet can
reach the app, but there's no reason to leave signup open.

---

## Updating

```bash
cd lettuce-eat
git pull
docker compose up -d --build
```

Migrations run on boot. Your database and storage survive because they live in
`./data`, outside the image.

---

## Backups

The entire application state is one SQLite file. Back it up with SQLite's own
command — **never `cp`**, which can capture a torn file while a write is in
flight:

```bash
mkdir -p ~/backups
docker compose exec app sqlite3 /var/lib/lettuce-eat/database.sqlite \
    ".backup '/var/lib/lettuce-eat/backup.sqlite'"
mv data/database/backup.sqlite ~/backups/lettuce-$(date +%F).sqlite
```

As a nightly cron (`crontab -e`):

```cron
0 3 * * * cd /home/pi/lettuce-eat && docker compose exec -T app sqlite3 /var/lib/lettuce-eat/database.sqlite ".backup '/var/lib/lettuce-eat/backup.sqlite'" && mv data/database/backup.sqlite /home/pi/backups/lettuce-$(date +\%F).sqlite
```

Copy those off the Pi periodically. A backup that only exists on the machine
that might die is not a backup.

**On SD cards:** SQLite plus database-backed sessions means a steady trickle of
small writes, which wears flash. Booting the Pi from a USB SSD is a meaningful
lifespan upgrade.

---

## Alternative: build on your Mac instead of the Pi

Optional. Do this only if Pi build times bother you, or if your Pi has 2GB RAM
and the frontend build runs out of memory.

Your Mac is Apple Silicon (arm64) and 64-bit Pi OS is also arm64 — the *same*
architecture. Normally building ARM images on a laptop means slow emulation;
here it's native, so you can build where it's fast and ship the result:

```bash
# on your Mac, in the repo
docker buildx build --platform linux/arm64 -t lettuce-eat:latest .
docker save lettuce-eat:latest | ssh pi@raspberrypi 'docker load'

# on the Pi
docker compose up -d          # note: no --build
```

The Pi then only ever runs the image. It never needs Node, Composer, or the
source tree — just `compose.yaml` and `.env.production`.

If you'd rather not pipe images over SSH, push to a private registry (GitHub
Container Registry is free for private images) and `docker compose pull` on the
Pi instead.

---

## Troubleshooting

**Redirect loop on login, or CSS loading over http on an https page.**
`APP_URL` in `.env.production` doesn't match your Tailscale hostname, or it's
missing the `https://` scheme. Fix it and `docker compose restart app`.

Laravel sits behind a proxy here, so `bootstrap/app.php` trusts forwarded
headers. Removing that `trustProxies` call reintroduces this bug — there are
tests in `tests/Feature/TrustedProxyTest.php` that fail if you do.

**500 error on first request.** Almost always a missing or malformed `APP_KEY`.
Check `docker compose logs app`.

**`attempt to write a readonly database`.** The `data/` directories are owned by
the wrong user:

```bash
sudo chown -R "$USER:$USER" data
docker compose restart app
```

**Build killed during `npm run build`.** Out of memory, typical on a 2GB Pi. Add
swap, or build on your Mac using the section above.

```bash
sudo dphys-swapfile swapoff
sudo sed -i 's/^CONF_SWAPSIZE=.*/CONF_SWAPSIZE=2048/' /etc/dphys-swapfile
sudo dphys-swapfile setup && sudo dphys-swapfile swapon
```

**`tailscale serve` says it can't get a certificate.** MagicDNS and HTTPS
Certificates both need to be enabled in the admin console DNS settings.

**Logs.**

```bash
docker compose logs -f app
```
