# Tipsy Hello World benchmark notes

Tipsy’s historic “fast” claim came from [kenjis/php-framework-benchmark](https://github.com/kenjis/php-framework-benchmark) Hello World measurements (thin bootstrap, low memory) — not a formal award.

## Goal for 2.0

Success is **no material regression** on modern PHP versus a typed Tipsy 2.0 baseline — not a claimed 2–5× gain from micro-optimizations. Platform wins (PHP 8.3/8.4 + OPcache) dominate.

## App under test

[`hello-world/index.php`](hello-world/index.php) — one route, JSON response, zero views/DB.

## How to run

```bash
# From repo root (PHP 8.3+ with OPcache recommended)
composer install

# Terminal A
php -d opcache.enable_cli=1 -S 127.0.0.1:8088 -t benchmarks/hello-world

# Terminal B — wrk (preferred) or ab
wrk -t2 -c50 -d10s http://127.0.0.1:8088/
# or
ab -n 5000 -c 50 http://127.0.0.1:8088/
```

Peak memory for a single request:

```bash
php -d memory_limit=64M benchmarks/hello-world/memory.php
```

## Captured local sample (2026-08-07)

Environment: PHP 8.3.33 NTS, macOS arm64, built-in server, no `wrk` installed in CI image — used `ab` when available.

See [`RESULTS.md`](RESULTS.md) for the latest numbers checked into the branch. Re-run after significant router/DI changes.
