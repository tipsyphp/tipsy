# Tipsy 2.0: Implementation Guide & Migration Tools

## Table of Contents
1. [Quick Start Guide](#quick-start-guide)
2. [Migration from v0.11.x](#migration-from-v011x)
3. [Modern Development Patterns](#modern-development-patterns)
4. [Performance Optimization Guide](#performance-optimization-guide)
5. [Deployment Strategies](#deployment-strategies)

## Quick Start Guide

### Installation

#### New Project
```bash
# Create new Tipsy 2.0 project
composer create-project tipsy/framework my-app
cd my-app

# Start development server
php tipsy serve
```

#### Existing Project Migration
```bash
# Add Tipsy 2.0 to existing project
composer require tipsy/framework:^2.0

# Run migration tool
vendor/bin/tipsy migrate:from-v0
```

### Basic Application Structure

#### Modern Application Bootstrap
```php
<?php
// public/index.php

use Tipsy\Core\Application;
use Tipsy\Core\Container\Container;
use Tipsy\Http\Request;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Create container and application
$container = new Container();
$app = new Application($container);

// Load configuration
$app->loadConfiguration(__DIR__ . '/../config');

// Register service providers
$app->register([
    \Tipsy\Database\DatabaseServiceProvider::class,
    \Tipsy\View\ViewServiceProvider::class,
    \Tipsy\Cache\CacheServiceProvider::class,
]);

// Load routes
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

// Handle request
$request = Request::createFromGlobals();
$response = $app->handle($request);
$response->send();
```

#### Configuration Structure
```php
<?php
// config/app.php

return [
    'name' => env('APP_NAME', 'Tipsy Application'),
    'debug' => env('APP_DEBUG', false),
    'environment' => env('APP_ENV', 'production'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    
    'providers' => [
        \Tipsy\Database\DatabaseServiceProvider::class,
        \Tipsy\View\ViewServiceProvider::class,
        \Tipsy\Cache\CacheServiceProvider::class,
        \App\Providers\AppServiceProvider::class,
    ],
];
```

```php
<?php
// config/database.php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'foreign_key_constraints' => true,
        ],
    ],
];
```

## Migration from v0.11.x

### Automated Migration Tool

#### Migration Command
```php
<?php
// src/Console/Commands/MigrateCommand.php

use Tipsy\Console\Command;

class MigrateCommand extends Command {
    protected string $signature = 'migrate:from-v0 {--dry-run}';
    protected string $description = 'Migrate from Tipsy v0.11.x to v2.0';
    
    public function handle(): int {
        $this->info('Starting migration from Tipsy v0.11.x...');
        
        $migrator = new TipsyMigrator(
            $this->option('dry-run'),
            $this->output
        );
        
        // Migrate routes
        $this->task('Migrating routes', function() use ($migrator) {
            return $migrator->migrateRoutes();
        });
        
        // Migrate services
        $this->task('Migrating services', function() use ($migrator) {
            return $migrator->migrateServices();
        });
        
        // Migrate views
        $this->task('Migrating views', function() use ($migrator) {
            return $migrator->migrateViews();
        });
        
        // Migrate database models
        $this->task('Migrating models', function() use ($migrator) {
            return $migrator->migrateModels();
        });
        
        $this->info('Migration completed successfully!');
        
        return 0;
    }
}
```

#### Migration Implementation
```php
<?php
// src/Migration/TipsyMigrator.php

class TipsyMigrator {
    private bool $dryRun;
    private OutputInterface $output;
    
    public function __construct(bool $dryRun, OutputInterface $output) {
        $this->dryRun = $dryRun;
        $this->output = $output;
    }
    
    public function migrateRoutes(): bool {
        $files = $this->findRoutingFiles();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $migrated = $this->transformRouteDefinitions($content);
            
            if (!$this->dryRun) {
                file_put_contents($file, $migrated);
            }
            
            $this->output->writeln("Migrated: {$file}");
        }
        
        return true;
    }
    
    private function transformRouteDefinitions(string $content): string {
        $patterns = [
            // Static method calls to router instance
            '/Tipsy::get\(/' => '$router->get(',
            '/Tipsy::post\(/' => '$router->post(',
            '/Tipsy::put\(/' => '$router->put(',
            '/Tipsy::delete\(/' => '$router->delete(',
            '/Tipsy::patch\(/' => '$router->patch(',
            '/Tipsy::options\(/' => '$router->options(',
            '/Tipsy::when\(/' => '$router->when(',
            '/Tipsy::home\(/' => '$router->get(\'\', ',
            
            // Service injection parameter names
            '/function\s*\(\s*\$View\s*\)/' => 'function(ViewInterface $view)',
            '/function\s*\(\s*\$Request\s*\)/' => 'function(ServerRequestInterface $request)',
            '/function\s*\(\s*\$Params\s*\)/' => 'function(RouteParams $params)',
            '/function\s*\(\s*\$Db\s*\)/' => 'function(DatabaseManager $db)',
        ];
        
        return preg_replace(array_keys($patterns), array_values($patterns), $content);
    }
    
    public function migrateServices(): bool {
        // Find service definitions
        $content = $this->findServiceDefinitions();
        
        foreach ($content as $file => $services) {
            $provider = $this->generateServiceProvider($services);
            
            if (!$this->dryRun) {
                $this->writeServiceProvider($file, $provider);
            }
        }
        
        return true;
    }
    
    private function generateServiceProvider(array $services): string {
        $template = <<<'PHP'
<?php

namespace App\Providers;

use Tipsy\Core\ServiceProvider;
use Tipsy\Container\ContainerInterface;

class MigratedServiceProvider extends ServiceProvider {
    public function register(ContainerInterface $container): void {
{{SERVICES}}
    }
}
PHP;
        
        $serviceRegistrations = [];
        foreach ($services as $name => $definition) {
            $serviceRegistrations[] = $this->convertServiceDefinition($name, $definition);
        }
        
        return str_replace('{{SERVICES}}', implode("\n\n", $serviceRegistrations), $template);
    }
}
```

### Manual Migration Steps

#### 1. Route Migration

**Before (v0.11.x):**
```php
<?php
// Old routing syntax

use Tipsy\Tipsy;

Tipsy::get('/users/:id', function($Params, $View, $Db) {
    $user = $Db->query('SELECT * FROM users WHERE id = ?', [$Params->id]);
    $View->display('user', ['user' => $user]);
});

Tipsy::post('/users', function($Request, $Db) {
    $data = json_decode($Request->body(), true);
    $id = $Db->insert('users', $data);
    header('Content-Type: application/json');
    echo json_encode(['id' => $id]);
});
```

**After (v2.0):**
```php
<?php
// routes/web.php

use Tipsy\Http\Response\JsonResponse;
use Tipsy\Http\Response\ViewResponse;
use Tipsy\Database\DatabaseManager;
use Tipsy\Http\ServerRequestInterface;
use App\Models\User;

$router->get('/users/{id}', function(string $id, DatabaseManager $db): ViewResponse {
    $user = User::find($id);
    return new ViewResponse('user', ['user' => $user]);
});

$router->post('/users', function(ServerRequestInterface $request): JsonResponse {
    $data = $request->getParsedBody();
    $user = User::create($data);
    return new JsonResponse(['id' => $user->id], 201);
});
```

#### 2. Service Migration

**Before (v0.11.x):**
```php
// Service definition
$app->service('UserService', function($Db) {
    return new UserService($Db);
});

// Service usage
$app->get('/users', function($UserService) {
    return $UserService->getAllUsers();
});
```

**After (v2.0):**
```php
<?php
// app/Providers/AppServiceProvider.php

class AppServiceProvider extends ServiceProvider {
    public function register(ContainerInterface $container): void {
        $container->bind(UserService::class, function($container) {
            return new UserService(
                $container->get(DatabaseManager::class)
            );
        });
    }
}

// routes/web.php
$router->get('/users', function(UserService $userService): JsonResponse {
    return new JsonResponse($userService->getAllUsers());
});
```

#### 3. Model Migration

**Before (v0.11.x):**
```php
// Old Resource/Model usage
$app->service('Tipsy\Resource/User', [
    '_id' => 'id',
    '_table' => 'users'
]);

$app->get('/users/:id', function($Params) {
    $user = new User();
    $user->load($Params->id);
    return $user->json();
});
```

**After (v2.0):**
```php
<?php
// app/Models/User.php

use Tipsy\Database\Model;

class User extends Model {
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    public function posts(): HasMany {
        return $this->hasMany(Post::class);
    }
}

// routes/web.php
$router->get('/users/{id}', function(string $id): JsonResponse {
    $user = User::findOrFail($id);
    return new JsonResponse($user->toArray());
});
```

#### 4. View Migration

**Before (v0.11.x):**
```php
$app->get('/dashboard', function($View, $User) {
    $View->display('dashboard', [
        'user' => $User,
        'title' => 'Dashboard'
    ]);
});
```

**After (v2.0):**
```php
$router->get('/dashboard', function(ViewInterface $view, User $user): ViewResponse {
    return new ViewResponse('dashboard', [
        'user' => $user,
        'title' => 'Dashboard'
    ]);
});
```

## Modern Development Patterns

### 1. Attribute-Based Routing

```php
<?php
// app/Controllers/UserController.php

use Tipsy\Routing\Attributes\Route;
use Tipsy\Routing\Attributes\Middleware;
use Tipsy\Http\Response\JsonResponse;

class UserController {
    public function __construct(
        private UserService $userService
    ) {}
    
    #[Route('GET', '/users')]
    #[Middleware([AuthMiddleware::class])]
    public function index(): JsonResponse {
        $users = $this->userService->getAllUsers();
        return new JsonResponse($users);
    }
    
    #[Route('GET', '/users/{id}')]
    #[Middleware([AuthMiddleware::class])]
    public function show(int $id): JsonResponse {
        $user = $this->userService->getUserById($id);
        return new JsonResponse($user);
    }
    
    #[Route('POST', '/users')]
    #[Middleware([AuthMiddleware::class, ValidateUserRequest::class])]
    public function store(ServerRequestInterface $request): JsonResponse {
        $data = $request->getParsedBody();
        $user = $this->userService->createUser($data);
        return new JsonResponse($user, 201);
    }
}
```

### 2. Dependency Injection with Constructor Injection

```php
<?php
// app/Services/UserService.php

class UserService {
    public function __construct(
        private DatabaseManager $db,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}
    
    public function getUserById(int $id): ?User {
        $cacheKey = "user.{$id}";
        
        return $this->cache->remember($cacheKey, 3600, function() use ($id) {
            $this->logger->info("Fetching user {$id} from database");
            return User::find($id);
        });
    }
    
    public function createUser(array $data): User {
        $user = User::create($data);
        
        $this->cache->forget("users.all");
        $this->logger->info("Created user {$user->id}");
        
        return $user;
    }
}
```

### 3. Middleware Implementation

```php
<?php
// app/Middleware/AuthMiddleware.php

use Tipsy\Http\Middleware\MiddlewareInterface;
use Tipsy\Http\ServerRequestInterface;
use Tipsy\Http\Response\JsonResponse;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface {
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $token = $request->getHeaderLine('Authorization');
        
        if (!$token || !$this->validateToken($token)) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Add user to request attributes
        $user = $this->getUserFromToken($token);
        $request = $request->withAttribute('user', $user);
        
        return $handler->handle($request);
    }
    
    private function validateToken(string $token): bool {
        // Token validation logic
        return !empty($token) && str_starts_with($token, 'Bearer ');
    }
}
```

### 4. Modern Model with Relationships

```php
<?php
// app/Models/User.php

use Tipsy\Database\Model;
use Tipsy\Database\Relations\HasMany;
use Tipsy\Database\Relations\BelongsToMany;

class User extends Model {
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password', 'remember_token'];
    protected array $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_admin' => 'boolean',
    ];
    
    public function posts(): HasMany {
        return $this->hasMany(Post::class);
    }
    
    public function roles(): BelongsToMany {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }
    
    public function isAdmin(): bool {
        return $this->is_admin || $this->roles()->where('name', 'admin')->exists();
    }
    
    protected static function booted(): void {
        static::creating(function($user) {
            $user->password = password_hash($user->password, PASSWORD_DEFAULT);
        });
    }
}
```

### 5. API Resource Transformations

```php
<?php
// app/Resources/UserResource.php

use Tipsy\Http\Resources\JsonResource;

class UserResource extends JsonResource {
    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar_url,
            'posts_count' => $this->posts()->count(),
            'is_admin' => $this->isAdmin(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

// Usage in controller
$router->get('/users/{id}', function(int $id): JsonResponse {
    $user = User::with('posts')->findOrFail($id);
    return new JsonResponse(new UserResource($user));
});
```

## Performance Optimization Guide

### 1. Container Compilation

#### Development Container
```php
<?php
// For development - reflection-based

class DevelopmentContainer implements ContainerInterface {
    public function get(string $id): mixed {
        // Use reflection for flexibility
        return $this->resolve($id);
    }
}
```

#### Production Container
```php
<?php
// For production - pre-compiled

class CompiledContainer implements ContainerInterface {
    private array $factories;
    private array $instances = [];
    
    public function __construct() {
        $this->factories = require __DIR__ . '/compiled/container.php';
    }
    
    public function get(string $id): mixed {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        
        if (!isset($this->factories[$id])) {
            throw new NotFoundException("Service {$id} not found");
        }
        
        return $this->instances[$id] = ($this->factories[$id])();
    }
}
```

#### Container Compilation
```php
<?php
// Build script: build/compile-container.php

$compiler = new ContainerCompiler();
$container = new Container();

// Register all services
$container->bind(UserService::class, function($c) {
    return new UserService($c->get(DatabaseManager::class));
});

// Compile to PHP file
$compiled = $compiler->compile($container);
file_put_contents(__DIR__ . '/../storage/compiled/container.php', $compiled);
```

### 2. Route Caching

```php
<?php
// Build script: build/compile-routes.php

$router = new Router();
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$compiler = new RouteCompiler();
$compiled = $compiler->compile($router);

file_put_contents(__DIR__ . '/../storage/compiled/routes.php', $compiled);
```

Generated route cache:
```php
<?php
// storage/compiled/routes.php

return [
    'static' => [
        'GET' => [
            '/users' => ['UserController', 'index'],
            '/health' => ['HealthController', 'check'],
        ],
        'POST' => [
            '/users' => ['UserController', 'store'],
        ],
    ],
    'patterns' => [
        'GET' => [
            '#^/users/(\d+)$#' => ['UserController', 'show', ['id']],
            '#^/posts/([^/]+)/comments/(\d+)$#' => ['CommentController', 'show', ['slug', 'id']],
        ],
    ],
];
```

### 3. Database Query Optimization

```php
<?php
// app/Services/OptimizedUserService.php

class OptimizedUserService {
    public function __construct(
        private DatabaseManager $db,
        private CacheInterface $cache
    ) {}
    
    public function getUsersWithPosts(): Collection {
        return $this->cache->remember('users.with.posts', 1800, function() {
            return User::with('posts')
                ->select(['id', 'name', 'email'])
                ->get();
        });
    }
    
    public function searchUsers(string $query): Collection {
        return User::where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->limit(20)
            ->get();
    }
    
    public function getUserStats(int $userId): array {
        $cacheKey = "user.{$userId}.stats";
        
        return $this->cache->remember($cacheKey, 3600, function() use ($userId) {
            return [
                'posts_count' => Post::where('user_id', $userId)->count(),
                'comments_count' => Comment::where('user_id', $userId)->count(),
                'likes_received' => Like::whereHas('post', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->count(),
            ];
        });
    }
}
```

### 4. Response Caching

```php
<?php
// app/Middleware/CacheMiddleware.php

class CacheMiddleware implements MiddlewareInterface {
    public function __construct(
        private CacheInterface $cache
    ) {}
    
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request);
        }
        
        $cacheKey = $this->getCacheKey($request);
        
        // Try to get cached response
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return unserialize($cached);
        }
        
        // Process request and cache response
        $response = $handler->handle($request);
        
        if ($response->getStatusCode() === 200) {
            $this->cache->set($cacheKey, serialize($response), 900); // 15 minutes
        }
        
        return $response;
    }
    
    private function getCacheKey(ServerRequestInterface $request): string {
        $uri = $request->getUri();
        return 'response:' . md5($uri->getPath() . '?' . $uri->getQuery());
    }
}
```

## Deployment Strategies

### 1. Production Build Process

```bash
#!/bin/bash
# scripts/build.sh

echo "Building Tipsy application for production..."

# Install dependencies
composer install --no-dev --optimize-autoloader

# Compile container
php build/compile-container.php

# Compile routes
php build/compile-routes.php

# Optimize configuration
php artisan config:cache

# Generate application key
php artisan key:generate

# Optimize Composer autoloader
composer dump-autoload --optimize --classmap-authoritative

echo "Build completed successfully!"
```

### 2. Docker Configuration

```dockerfile
# Dockerfile

FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Configure OPcache for production
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies and build
RUN composer install --no-dev --optimize-autoloader \
    && php build/compile-container.php \
    && php build/compile-routes.php

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

EXPOSE 9000

CMD ["php-fpm"]
```

### 3. Nginx Configuration

```nginx
# nginx/tipsy.conf

server {
    listen 80;
    server_name tipsy.local;
    root /var/www/public;
    
    index index.php;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    
    # Handle all requests through index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Performance optimizations
        fastcgi_buffering on;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }
    
    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(vendor|storage|tests|build)/ {
        deny all;
    }
}
```

### 4. Performance Monitoring

```php
<?php
// app/Middleware/PerformanceMonitoringMiddleware.php

class PerformanceMonitoringMiddleware implements MiddlewareInterface {
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $response = $handler->handle($request);
        
        $duration = microtime(true) - $startTime;
        $memoryUsed = memory_get_peak_usage(true) - $startMemory;
        
        // Log performance metrics
        logger()->info('Request performance', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'duration_ms' => round($duration * 1000, 2),
            'memory_mb' => round($memoryUsed / 1024 / 1024, 2),
            'status' => $response->getStatusCode(),
        ]);
        
        // Add performance headers in development
        if (config('app.debug')) {
            $response = $response
                ->withHeader('X-Response-Time', round($duration * 1000, 2) . 'ms')
                ->withHeader('X-Memory-Usage', round($memoryUsed / 1024 / 1024, 2) . 'MB');
        }
        
        return $response;
    }
}
```

This implementation guide provides a comprehensive roadmap for migrating from Tipsy v0.11.x to v2.0, along with modern development patterns and production deployment strategies. The migration tools and examples ensure a smooth transition while taking advantage of modern PHP features and performance optimizations.