# Tipsy Examples

Working examples demonstrating core Tipsy features on modern PHP 8.3+.

## Examples

| Example | Description |
|---------|-------------|
| **[hello-world](hello-world/)** | Minimal app — one route, one view |
| **[rest-api](rest-api/)** | JSON REST API with GET/POST/PUT/DELETE |
| **[middleware](middleware/)** | Request logging and auth middleware |
| **[services](services/)** | Dependency injection with services |
| **[docker](docker/)** | Production Dockerfile + nginx config |
| **[vercel](vercel/)** | Serverless deploy with vercel-php community runtime |

## Running an Example (local path repo)

In-repo `composer.json` files use a **path** repository (`../../`) so examples track this working tree during development.

```bash
cd examples/hello-world
# Mirror (copy) the path repo — avoids symlink loops with coverage tools
COMPOSER_MIRROR_PATH_REPOS=1 composer install
php -S localhost:8000 -t web
```

Then open [http://localhost:8000](http://localhost:8000).

## Packagist / deployable apps

After `tipsyphp/tipsy` `^2.0` is on Packagist, production apps should depend on Packagist instead of a path repo:

```json
{
  "require": {
    "php": ">=8.3",
    "tipsyphp/tipsy": "^2.0"
  }
}
```

The Vercel example’s path repository will **not** work on Vercel’s build hosts — publish/require from Packagist first, or vendor the package another way.

## Running with Docker

```bash
# From the repo root:
docker build -f examples/docker/Dockerfile -t tipsy-app .
docker run -p 8080:80 tipsy-app
```

Then open [http://localhost:8080](http://localhost:8080).

## Deploying to Vercel

```bash
cd examples/vercel
# Requires tipsyphp/tipsy on Packagist (or adjust composer.json)
npx vercel
```
