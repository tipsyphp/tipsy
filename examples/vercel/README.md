# Tipsy on Vercel

Deploy Tipsy as a serverless PHP function using the [vercel-php](https://github.com/vercel-community/php) community runtime.

## Packagist requirement

The example `composer.json` uses a **path** repository for local monorepo development. Vercel builds **cannot** resolve that path. Before deploying:

1. Publish `tipsyphp/tipsy` `^2.0` to Packagist (or a VCS repo Vercel can reach), then
2. Replace the path repository with a normal constraint:

```json
{
  "require": {
    "php": ">=8.3",
    "tipsyphp/tipsy": "^2.0"
  }
}
```

## Deploy

```bash
# Install Vercel CLI
npm i -g vercel

# Deploy (after Packagist require is in place)
cd examples/vercel
vercel
```

## Local Development

```bash
cd examples/vercel
composer install
php -S localhost:8000 -t api
```

## How It Works

```
vercel.json          → routes all requests to api/index.php
api/index.php        → Tipsy entry point (handles all routing)
composer.json        → vercel-php installs deps automatically during build
```

Vercel spins up a serverless function per request. Tipsy's low overhead
(sub-millisecond bootstrap) makes it a good fit for cold starts.

## Endpoints

| Route | Method | Description |
|-------|--------|-------------|
| `/` | GET | API info + available routes |
| `/hello/:name` | GET | Greeting with route param |
| `/time` | GET | Current UTC time |
| `/echo` | POST | Echoes back your JSON body |
| `/health` | GET | Health check |
