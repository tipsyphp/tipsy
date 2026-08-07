# Upgrading to Tipsy 2.0

Tipsy 2.0 is a hard break from the 0.11.x line. It targets **PHP 8.3+**, uses strict typing throughout, and drops PHP 5/7-era quirks. There are **no runtime Composer dependencies** — same thin-bootstrap identity as 0.x.

## Requirements

- PHP `>= 8.3`
- PHPUnit `^11` for development

## Breaking changes

| Change | Migration |
|--------|-----------|
| PHP floor raised to 8.3 | Upgrade the runtime before upgrading Tipsy |
| `Router::aliass()` renamed to `aliases()` | Rename call sites; the typo method is gone |
| Route `:param` matches one path segment (`[^/]+`), not `.*` | Use an explicit regex route if you need multi-segment captures |
| Unknown DI dependencies are `null` (was `false`) | Replace `=== false` checks with `=== null` / falsy checks |
| `App::run()` / `App::start()` return `void` | Do not use their return values |
| `Http` verifies SSL by default | Pass `skipSslVerify => true` for self-signed/local HTTPS |
| Curly-brace string offsets and bareword array keys | Use `$str[0]` and quoted keys (`['id' => 1]`) |
| Typed constructors / method signatures | Align subclasses (`Controller::init`, `Middleware::run`, etc.) |

## Intentional non-goals (2.0)

- No PSR-7 / PSR-11 / PSR-15 adoption in core
- No new ORM, template engine, or heavy middleware stack
- No compatibility shims for PHP &lt; 8.3

## Behavioral improvements worth knowing

- Route patterns are precompiled at construction time
- DI caches `ReflectionFunction` parameter lists per closure
- `files` autoload still loads `src/Tipsy.php` so the `t` class alias and `getallheaders()` polyfill remain available
- Boolean model properties stay PHP `bool` after MySQL save (DB still stores `0`/`1`)

## Examples

In-repo examples use a Composer **path** repository pointing at the monorepo root for local development. After Packagist publishes `tipsyphp/tipsy` `^2.0`, deployable apps should depend on the Packagist constraint instead of the path repo.
