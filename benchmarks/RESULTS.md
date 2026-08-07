# Benchmark results

Date: 2026-08-07  
Host: macOS arm64, PHP 8.3.33 NTS, ApacheBench (`ab`), PHP built-in server on `127.0.0.1:8088`

## Tipsy 2.0 (this branch)

| Metric | Value |
|--------|-------|
| Hello World RPS (`ab -n 3000 -c 20`) | **11578 req/s** |
| Mean latency | 1.73 ms |
| Single-request peak memory (`memory.php`) | **4 MB** (`4194304` bytes) |

Command:

```bash
ab -n 3000 -c 20 http://127.0.0.1:8088/
php benchmarks/hello-world/memory.php
```

## vs origin/master (0.11.x)

`origin/master` **cannot run on PHP 8.3+** (fatal curly-brace string offsets, e.g. `$url{0}`). A same-runtime comparison is therefore impossible on this toolchain.

Historical reference (external, PHP 7 era): kenjis Hello World had tipsy-0.10 around ~1377 req/s on that harness — different hardware/runtime; do not treat as a head-to-head with the numbers above.

**2.0 success criterion for this pass:** establish a typed PHP 8.3 baseline (above). Future changes should not regress RPS/memory materially on the same machine/`ab` settings.
