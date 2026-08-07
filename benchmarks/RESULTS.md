# Benchmark results

Host: macOS arm64, PHP 8.3.33 NTS, ApacheBench (`ab -n 5000 -c 50`), PHP built-in server on `127.0.0.1:8088`

## Tipsy 2.0 Hello World

| Revision | RPS (mean of runs) | Peak memory |
|----------|--------------------|-------------|
| Pre-optimization (`b726b75`) | **13929** | 4 MB |
| Hot-path optimizations (this) | **14800–15215** (~6–9% ↑) | 4 MB |

Sample after optimizations (3 consecutive runs):

```
Requests per second:    14882.37 [#/sec]
Requests per second:    15215.42 [#/sec]
Requests per second:    14434.60 [#/sec]
```

```bash
ab -n 5000 -c 50 http://127.0.0.1:8088/
php benchmarks/hello-world/memory.php
```

## What changed

- Exact/static routes use string compare instead of `preg_match`
- HTTP methods precomputed uppercase; request method cached
- Router match iterates backwards (no `array_reverse` allocation)
- DI skips rebuilding an `$avail` list (`_getDependency` already resolves unknowns)
- `Request::path` avoids `preg_replace` for script/dir stripping
- Removed unused `App` id/`random_bytes` on construct

## vs origin/master (0.11.x)

`origin/master` **cannot run on PHP 8.3+** (fatal curly-brace string offsets). Same-runtime comparison is not possible on this toolchain.

Historical reference (external, PHP 7 era): kenjis Hello World had tipsy-0.10 around ~1377 req/s on that harness — different hardware/runtime.
