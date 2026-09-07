---
paths:
  - 'bootstrap/**'
---

# Bootstrap

## trustProxies is load-bearing for the self-hosted deployment
`bootstrap/app.php` calls `$middleware->trustProxies(at: '*')`. Do not remove it.

In production the app runs behind `tailscale serve`, which terminates TLS and forwards plain HTTP to 127.0.0.1. Without trusted proxies Laravel sees an insecure request, generates `http://` URLs, and login redirects break. `'*'` is safe because the container only listens on loopback.

Guarded by tests/Feature/TrustedProxyTest.php. See DEPLOYMENT.md.
