# Tipsy Framework - Baseline Environment

This directory contains a reproducible baseline environment for the Tipsy PHP framework, recreating the exact conditions from 2014-2015.

## Overview

- **PHP Version**: 5.6 (supporting the minimum requirement of >=5.5.0)
- **Web Server**: Apache 2.4 with mod_rewrite
- **Tipsy Version**: 0.11.* (latest from the era)
- **Environment**: Docker containerized for consistency

## Quick Start

### Prerequisites
- Docker and Docker Compose
- Git

### 1. Build and Start the Environment

```bash
# Build the Docker image
docker-compose build

# Install dependencies using Composer
docker-compose run --rm composer

# Start the development server
docker-compose up -d tipsy-baseline

# View logs
docker-compose logs -f tipsy-baseline
```

### 2. Access the Application

- **Main Application**: http://localhost:8080
- **PHP Info**: http://localhost:8080/info  
- **Test Route**: http://localhost:8080/test

### 3. Verify the Setup

The home page should display:
```
Hello world! Tipsy is running on PHP 5.6.x
```

## File Structure

```
baseline/
├── docker-compose.yml    # Docker orchestration
├── Dockerfile           # PHP 5.6 + Apache container
├── app/                 # Application code
│   ├── web/            # Web root (like Heroku setup)
│   │   ├── index.php   # Entry point
│   │   └── .htaccess   # URL rewriting
│   ├── app.php         # Main application file
│   ├── composer.json   # Dependencies
│   └── Procfile        # Heroku configuration (reference)
└── README.md           # This file
```

## Purpose

This baseline serves as:

1. **Historical Reference**: Exact environment Tipsy ran on in 2014-2015
2. **Performance Baseline**: Measure original performance characteristics  
3. **Compatibility Testing**: Ensure modernization doesn't break core functionality
4. **Migration Starting Point**: Base for incremental PHP version upgrades

## Development Commands

```bash
# Rebuild after changes
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Access container shell
docker-compose exec tipsy-baseline bash

# Install new Composer dependencies
docker-compose run --rm composer install

# View Apache error logs
docker-compose exec tipsy-baseline tail -f /var/log/apache2/error.log

# Stop everything
docker-compose down
```

## Expected Issues

Since this recreates a 2014-2015 environment, you may encounter:

- **Security warnings** - PHP 5.6 is end-of-life
- **Deprecated features** - Some functions may show deprecation notices
- **Missing extensions** - Some modern PHP extensions won't be available
- **Performance differences** - Modern PHP is significantly faster

These are expected and part of establishing the baseline for comparison.

## Next Steps

1. **Verify baseline works** - Ensure all routes respond correctly
2. **Performance benchmarking** - Measure request/response times
3. **Feature testing** - Test all Tipsy features (DI, routing, views, etc.)
4. **Create PHP upgrade path** - Incrementally upgrade PHP versions