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
| Default DB driver is **pgsql** (was mysql) | Set `driver=mysql` (or a `mysql://` URL) explicitly for MySQL |
| Resource SQL is dialect-aware | No need for `MysqlToPgsql` on normal Resource usage; opt in only for raw MySQL SQL on Postgres |

## Database

Tipsy 2.0 defaults to **PostgreSQL**. Supported drivers: `pgsql` (aliases: `postgres`, `postgresql`), `mysql`, `sqlite`.

```ini
[db]
host=127.0.0.1
user=postgres
pass=secret
database=app
driver=pgsql
```

MySQL:

```ini
[db]
host=127.0.0.1
user=root
pass=
database=app
driver=mysql
```

Optional: if you still write raw MySQL-flavored SQL against Postgres, register the compatibility layer:

```php
$app->service('Db', Tipsy\Db\MysqlToPgsql::class);
```

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
