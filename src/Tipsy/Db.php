<?php

namespace Tipsy;

class Db {
	private $_db;
	private $_fields;

	public function __construct($config = []) {
		$db = $this->connect($config);
		$this->_db = $db;
	}

	public function connect($args = null) {
		if (!$args) {
			throw new Exception('Invalid DB config.');
		}

		$options = [];

		// will overwrite any existing args
		if ($args['url']) {
			$url = parse_url($args['url']);

			$args['driver'] = $url['scheme'];
			$args['user'] = $url['user'];
			$args['pass'] = $url['pass'];
			$args['host'] = $url['host'];
			$args['port'] = $url['port'];
			$args['database'] = substr($url['path'], 1);
			parse_str($url['query'], $args['options']);

			if ($args['options'] && is_array($args['options'])) {
				foreach ($args['options'] as $key => $value) {
					$args[$key] = $value;
				}
			}
		}

		if (!$args['charset']) {
			$args['charset'] = 'utf8';
		}

		if ($args['persistent']) {
			$options[PDO::ATTR_PERSISTENT] = true;
		}

		if ($args['sslca']) {
			$options[PDO::MYSQL_ATTR_SSL_CA] = $args['sslca'];
			$options[PDO::ATTR_TIMEOUT] = 4;
			$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		}

		if (!$args['driver']) {
			$args['driver'] = 'mysql';
		}

		if (!$args['dsn']) {
			$args['dsn'] = $args['driver'].':host='.$args['host'].($args['port'] ? ';port='.$args['port'] : '').';dbname='.$args['database'].';charset='.$args['charset'];
		}
		$db = new \PDO($args['dsn'], $args['user'], $args['pass'], $options);
		$this->_driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		return $db;
	}

	public function exec($query) {
		return $this->db()->exec($query);
	}

	public function query($query, $args = []) {
		$stmt = $this->db()->prepare($query);
		$stmt->execute($args);
		//$db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql'
		return $stmt;
	}

	public function get($query, $args = [], $type = 'object') {
		$stmt = $this->query($query, $args);
		return $stmt->fetchAll($type == 'object' ? \PDO::FETCH_OBJ : \PDO::FETCH_ASSOC);
	}

	public function db() {
		return $this->_db;
	}

	public function fields($table, $fields = null) {
		if ($table && $fields) {
			$this->_fields[$table] = $fields;
		}
		return $this->_fields[$table];
	}

	public function driver() {
		return $this->_driver;
	}
}
