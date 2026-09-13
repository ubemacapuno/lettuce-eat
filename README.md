# Lettuce Eat

A self-hosted **food journal** for recipes I cook at home, and restaurants and dishes I've
tried and want to log. It runs as a single container on a Raspberry Pi in my house, reachable only
from my [Tailscale](https://tailscale.com/) network. Nothing is exposed to the public
internet and no ports are forwarded on my router.

## Why I built this

First, I wanted to get more practice building with **Laravel**.

Second, I wanted to learn **how to self-host something I built**.
Deploying to a managed platform hides all the interesting parts. Putting it on a Pi
meant I had to deal with the things a platform normally does for me: building an image
that runs on ARM, picking a database that survives a reboot, getting TLS without owning
a domain, and reaching the thing from my phone without opening my home network to the
internet.

The app itself is deliberately small.

## What it does

- **Recipes**: ingredients, instructions, a rating, a link to the original, timing,
  servings, and whether you'd make it again.
- **Restaurants**: name, address, rating, and notes.
- **Dishes**: logged under the restaurant you ate them at, with their own rating, notes,
  and an "order again" flag.
- **Half-star ratings** on all three.
- **Private by default**: everything stays on the Pi.

<!-- TODO: screenshots of the recipe and restaurant pages -->

## Tech stack

| Layer             | Tech                                                                                          |
| ----------------- | --------------------------------------------------------------------------------------------- |
| **Backend**       | Laravel 13, PHP 8.3+                                                                          |
| **Frontend**      | Blade + Tailwind CSS v3, with Vue 3 "islands" mounted only where interactivity is needed      |
| **Auth**          | Laravel Breeze                                                                                |
| **Database**      | SQLite in WAL mode, with sessions, cache, and queue all living in the same file                |
| **Build**         | Vite                                                                                          |
| **Container**     | [FrankenPHP](https://frankenphp.dev/), which is Caddy with PHP embedded, so no nginx + php-fpm |
| **Host**          | Raspberry Pi 4B running Docker Compose                                                        |
| **Remote access** | Tailscale, a private VPN (tailnet), so the Pi is reachable from anywhere without port forwarding |

The frontend is server-rendered Blade, not a SPA. Vue is loaded as small islands
(`data-vue="StarRating"`) for the three things that genuinely need client-side state,
which are the star picker, delete confirmation, and auto-hiding flash messages.
Everything else is plain HTML, which keeps the whole app one build step and one process.

## Flow Overview

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

One container, one file of state. No nginx, no php-fpm, no database server, no queue
worker, no Redis.

Tailscale is what makes this comfortable to run at home. My devices and the Pi all join
the same private tailnet, and I reach the app over that encrypted tunnel instead of
exposing anything publicly. `tailscale serve` publishes to the tailnet only.
`tailscale funnel` publishes to the internet, and is the one command to avoid here.

## Getting started

Locally:

```bash
git clone git@github.com:ubemacapuno/lettuce-eat.git
cd lettuce-eat

composer setup                 # install, .env, app key, migrate, npm install, build
php artisan migrate --seed     # optional: a few restaurants, dishes, and recipes
composer run dev               # http://127.0.0.1:8000
```

Tests:

```bash
php artisan test --compact
```
