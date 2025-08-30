<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Database connection and query wrapper extending PDO
 * 
 * Provides simplified database operations with URL-based configuration,
 * connection management, and basic query methods.
 */
class Db extends Model
{
    /** @var ?\PDO Database connection */
    protected ?\PDO $_db = null;
    
    /** @var array<string, array<string, mixed>> Table field definitions */
    protected array $_fields = [];
    
    /** @var string Database driver name */
    protected string $_driver = '';

    /**
     * @param ?array<string, mixed> $config Database configuration
     */
    public function __construct(?array $config = null)
    {
        parent::__construct();
        
        if ($config && !isset($config['_tipsy'])) {
            $this->connect($config);
        }
    }

    /**
     * Parse database URL into connection parameters
     * 
     * @param string $url Database URL (e.g., mysql://user:pass@host:port/database)
     * @return array<string, mixed>
     */
    public function parseUrl(string $url): array
    {
        $parsed = parse_url($url);
        $args = [];

        $args['driver'] = $parsed['scheme'] ?? '';
        $args['user'] = $parsed['user'] ?? '';
        $args['pass'] = $parsed['pass'] ?? '';
        $args['host'] = $parsed['host'] ?? '';
        $args['port'] = $parsed['port'] ?? null;
        $args['database'] = isset($parsed['path']) ? substr($parsed['path'], 1) : '';
        
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $options);
            $args['options'] = $options;
            
            if (is_array($options)) {
                $args = array_merge($args, $options);
            }
        }

        return $args;
    }

    /**
     * Connect to database with configuration
     * 
     * @param ?array<string, mixed> $args Connection parameters
     * @throws \Exception When invalid configuration provided
     */
    public function connect(?array $args = null): self
    {
        if (!$args) {
            throw new \Exception('Invalid DB config.');
        }

        $options = [];

        // Parse URL if provided
        if (isset($args['url'])) {
            $args = array_merge($this->parseUrl($args['url']), $args);
        }

        // Configure PDO options
        if ($args['persistent'] ?? false) {
            $options[\PDO::ATTR_PERSISTENT] = true;
        }

        if ($args['sslca'] ?? false) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $args['sslca'];
            $options[\PDO::ATTR_TIMEOUT] = 4;
            $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        }

        // Driver normalization
        $args['driver'] = match ($args['driver'] ?? 'mysql') {
            'postgres' => 'pgsql',
            default => $args['driver'] ?? 'mysql'
        };

        // Set charset for MySQL
        if ($args['driver'] === 'mysql') {
            $args['charset'] = $args['charset'] ?? 'utf8';
        }

        // Build DSN if not provided
        if (!isset($args['dsn'])) {
            $dsn = $args['driver'] . ':host=' . ($args['host'] ?? 'localhost');
            
            if ($args['port'] ?? null) {
                $dsn .= ';port=' . $args['port'];
            }
            
            $dsn .= ';dbname=' . ($args['database'] ?? '');
            
            if ($args['charset'] ?? null) {
                $dsn .= ';charset=' . $args['charset'];
            }
            
            $args['dsn'] = $dsn;
        }

        // Create PDO connection
        $db = new \PDO($args['dsn'], $args['user'] ?? '', $args['pass'] ?? '', $options);
        $this->_driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        // Configure PDO behavior
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, ($args['sslca'] ?? false) ? true : false);

        $this->db($db);

        return $this;
    }

    /**
     * Execute a statement directly
     */
    public function exec(string $query): int|false
    {
        return $this->db()->exec($query);
    }

    /**
     * Prepare and execute a query with optional parameters
     * 
     * @param array<string, mixed>|null $args Query parameters
     */
    public function query(string $query, ?array $args = null): \PDOStatement
    {
        $stmt = $this->db()->prepare($query);
        $stmt->execute($args ?? []);
        return $stmt;
    }

    /**
     * Execute query and fetch all results
     * 
     * @param array<string, mixed>|null $args Query parameters
     * @return array<int, object>|array<int, array<string, mixed>>
     */
    public function get(string $query, ?array $args = null, string $type = 'object'): array
    {
        $stmt = $this->query($query, $args);
        $fetchType = $type === 'object' ? \PDO::FETCH_OBJ : \PDO::FETCH_ASSOC;
        return $stmt->fetchAll($fetchType);
    }

    /**
     * Get/set database connection
     */
    public function db(?\PDO $db = null): ?\PDO
    {
        if ($db !== null) {
            $this->_db = $db;
        }
        return $this->_db;
    }

    /**
     * Get/set table field definitions
     * 
     * @param ?array<string, mixed> $fields Field definitions
     * @return ?array<string, mixed>
     */
    public function fields(string $table, ?array $fields = null): ?array
    {
        if ($table && $fields !== null) {
            $this->_fields[$table] = $fields;
        }
        return $this->_fields[$table] ?? null;
    }

    /**
     * Get database driver name
     */
    public function driver(): string
    {
        return $this->_driver;
    }

    /**
     * Get/set framework instance
     */
    public function tipsy(?App $tipsy = null): ?App
    {
        if ($tipsy !== null) {
            $this->_tipsy = $tipsy;
        }
        return $this->_tipsy;
    }
}
