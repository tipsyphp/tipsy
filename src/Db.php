<?php

declare(strict_types=1);

namespace Tipsy;

class Db extends Model {
	protected ?\PDO $_db = null;
	protected array $_fields = [];
	private ?string $_driver = null;

	public function __construct(array|null $config = null) {
		if (isset($config['_tipsy'])) {
			return;
		}
		if ($config !== null) {
			$this->connect($config);
		}
	}

	public function parseUrl(string $url): array {
		$parsed = parse_url($url);
		$args = [];

		$args['driver'] = $parsed['scheme'] ?? '';
		$args['user'] = $parsed['user'] ?? '';
		$args['pass'] = $parsed['pass'] ?? '';
		$args['host'] = $parsed['host'] ?? '';
		$args['port'] = $parsed['port'] ?? null;
		$args['database'] = substr($parsed['path'] ?? '', 1);
		parse_str($parsed['query'] ?? '', $args['options']);

		if (!empty($args['options']) && is_array($args['options'])) {
			foreach ($args['options'] as $key => $value) {
				$args[$key] = $value;
			}
		}

		return $args;
	}

	public function connect(array $args): static {
		if (empty($args)) {
			throw new \Exception('Invalid DB config.');
		}
		$options = [];

		// will overwrite any existing args
		if (!empty($args['url'])) {
			$args = array_merge($this->parseUrl($args['url']), $args);
		}

		if (!empty($args['persistent'])) {
			$options[\PDO::ATTR_PERSISTENT] = true;
		}

		if (!empty($args['sslca'])) {
			$options[\PDO::MYSQL_ATTR_SSL_CA] = $args['sslca'];
			$options[\PDO::ATTR_TIMEOUT] = 4;
			$options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
		}

		if (empty($args['driver'])) {
			$args['driver'] = 'pgsql';
		}

		$args['driver'] = match ($args['driver']) {
			'postgres', 'postgresql' => 'pgsql',
			default => $args['driver'],
		};

		if ($args['driver'] === 'mysql') {
			$args['charset'] = 'utf8';
		}

		if (empty($args['dsn'])) {
			$args['dsn'] = $args['driver'] . ':host=' . $args['host']
				. (!empty($args['port']) ? ';port=' . $args['port'] : '')
				. ';dbname=' . $args['database']
				. (!empty($args['charset']) ? ';charset=' . $args['charset'] : '');
		}

		$db = new \PDO($args['dsn'], $args['user'] ?? '', $args['pass'] ?? '', $options);
		$this->_driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, !empty($args['sslca']));
		// Set default fetch mode once at connection time — avoids per-query overhead
		$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

		$this->db($db);

		return $this;
	}

	public function exec(string $query): int|false {
		return $this->db()->exec($query);
	}

	public function query(string $query, ?array $args = null): \PDOStatement {
		$pdo = $this->db();
		if ($pdo === null) {
			throw new Exception('Database is not connected.');
		}
		$stmt = $pdo->prepare($query);
		$stmt->execute($args ?? []);
		return $stmt;
	}

	public function get(string $query, ?array $args = null, string $type = 'object'): array {
		$stmt = $this->query($query, $args);
		return $stmt->fetchAll($type === 'object' ? \PDO::FETCH_OBJ : \PDO::FETCH_ASSOC);
	}

	public function db(?\PDO $db = null): ?\PDO {
		if ($db !== null) {
			$this->_db = $db;
		}
		return $this->_db;
	}

	public function fields(?string $table = null, ?array $fields = null): ?array {
		if ($table !== null && $fields !== null) {
			$this->_fields[$table] = $fields;
		}
		return $this->_fields[$table] ?? null;
	}

	public function driver(): ?string {
		return $this->_driver;
	}

	/**
	 * Quote an SQL identifier for the active driver.
	 * Postgres/SQLite use double quotes; MySQL uses backticks.
	 */
	public function quoteIdent(string $name): string {
		return match ($this->_driver) {
			'mysql' => '`' . str_replace('`', '``', $name) . '`',
			default => '"' . str_replace('"', '""', $name) . '"',
		};
	}

	public function tipsy(?App $tipsy = null): ?App {
		if ($tipsy !== null) {
			$this->_tipsy = $tipsy;
		}
		return $this->_tipsy ?? null;
	}
}
