<?php

declare(strict_types=1);

namespace Tipsy;

class Resource extends Model {

	public function __construct(mixed $args = []) {
		if (!is_array($args)) {
			if ($args !== null && $args !== '') {
				$this->load($args);
			}
			return;
		}

		$this->_baseConfig = $args;

		if (isset($args['_tipsy'])) {
			$this->_tipsy = $args['_tipsy'];
			unset($args['_tipsy']);
		}
		if (isset($args['_service'])) {
			$this->_service = $args['_service'];
			unset($args['_service']);
		}

		if (isset($args['_id'])) {
			$this->idVar($args['_id']);
			unset($args['_id']);
		}
		if (isset($args['_table'])) {
			$this->table($args['_table']);
			unset($args['_table']);
		}
		if (isset($args['_fields'])) {
			$this->__fields = $args['_fields'];
			unset($args['_fields']);
		}

		if ($args) {
			$this->load($args);
		}
	}


	/**
	 * Retrieve a field list from the db
	 *
	 * Will populate $this->fields based on the columns in the db for the
	 * current objects table.
	 *
	 * @return array
	 */
	public function fields(): array {
		if ($fields = $this->db()->fields($this->table())) {
			$this->_fields = $fields;
		} else {

			$fields = [];
			$table = $this->db()->quoteIdent($this->table());
			$q = match ($this->db()->driver()) {
				'mysql' => 'SHOW COLUMNS FROM ' . $table,
				'sqlite' => 'PRAGMA table_info(' . $table . ')',
				'pgsql' => 'SELECT column_name AS field, data_type AS type, is_nullable AS nullable, column_default AS col_default'
					. ' FROM information_schema.columns'
					. ' WHERE table_schema = current_schema() AND table_name = ' . $this->db()->db()->quote($this->table()),
				default => throw new Exception('Unsupported database driver: ' . ($this->db()->driver() ?? 'unknown')),
			};

			try {
				$rows = $this->db()->get($q);
			} catch (\Exception $e) {
				$rows = [];
			}

			if (!count($rows)) {
				// Create then re-read metadata — never cache an empty field list
				$this->createTable();
				$rows = $this->db()->get($q);
			}

			foreach ($rows as $row) {
				$fields = match ($this->db()->driver()) {
					'sqlite' => $this->_parseSqliteField($row, $fields),
					'mysql' => $this->_parseMysqlField($row, $fields),
					'pgsql' => $this->_parsePgsqlField($row, $fields),
					default => $fields,
				};
			}

			$this->_fields = $fields;
			$this->db()->fields($this->table(), $fields);
		}
		return $this->_fields;
	}

	private function _parseSqliteField(object $row, array $fields): array {
		$fields[$row->name] = (object)[
			'field' => $row->name,
			'type' => $row->type,
			'null' => $row->notnull ? false : true
		];
		return $fields;
	}

	private function _parseMysqlField(object $row, array $fields): array {
		$fields[$row->Field] = (object)[
			'field' => $row->Field,
			'type' => $row->Type,
			'null' => $row->Null === 'YES',
			'auto' => $row->Extra === 'auto_increment'
		];

		if ($fields[$row->Field]->type === 'tinyint(1)' || $fields[$row->Field]->type === 'tinyint(1) unsigned') {
			$fields[$row->Field]->type = 'boolean';
		} elseif (str_contains($fields[$row->Field]->type, 'int')) {
			$fields[$row->Field]->type = 'int';
		}

		return $fields;
	}

	private function _parsePgsqlField(object $row, array $fields): array {
		$name = $row->field;
		$default = $row->col_default ?? null;
		$fields[$name] = (object)[
			'field' => $name,
			'type' => $row->type,
			'null' => ($row->nullable ?? '') === 'YES',
			'auto' => !empty($default) && preg_match('/^nextval\(/', (string)$default) === 1
		];

		if (in_array($fields[$name]->type, ['integer', 'bigint', 'smallint'], true)) {
			$fields[$name]->type = 'int';
		} elseif (in_array($fields[$name]->type, ['boolean', 'bool'], true)) {
			$fields[$name]->type = 'boolean';
		}

		if ($fields[$name]->auto && $fields[$name]->field === $this->idVar()) {
			$fields[$name]->sequence = preg_replace('/^nextval\(\'(.*?)\'.*\)$/i', '\\1', (string)$default);
		}
		return $fields;
	}

	public function get(int|string|null $id = null): static {
		return $this;
	}

	public function createTable(): void {
		if (!$this->__fields) {
			throw new Exception('Could not create table "' . $this->table() . '"');
		}

		$q = 'CREATE TABLE ' . $this->db()->quoteIdent($this->table()) . ' (';
		foreach ($this->__fields as $k => $field) {
			$q .= ' ' . $this->db()->quoteIdent($k) . ' ';

			$fieldDefault = $field->default ?? null;
			$default = match (true) {
				$fieldDefault === null => 'NULL',
				$fieldDefault === true => 'true',
				$fieldDefault === false => 'false',
				is_int($fieldDefault) => (string)$fieldDefault,
				default => "'" . $fieldDefault . "'",
			};

			$q .= match ($field->type) {
				'int' => $this->_createIntColumn($field, $default),
				'char' => 'varchar(' . ($field->length ?? 255) . ') '
					. (!empty($field->null) ? '' : ' NOT NULL ')
					. ($fieldDefault !== null ? ' DEFAULT ' . $default : ''),
				'bool' => ($this->db()->driver() === 'pgsql' ? 'bool NOT NULL ' : 'tinyint(1) NOT NULL ')
					. ($fieldDefault !== null ? ' DEFAULT ' . $default : ''),
				default => '',
			};
			$q .= ',';
		}

		$q .= 'PRIMARY KEY (' . $this->db()->quoteIdent($this->idVar()) . '))';

		$this->db()->query($q);
	}

	private function _createIntColumn(object $field, string $default): string {
		if ($this->db()->driver() === 'pgsql') {
			return ($field->auto ? 'serial' : 'integer')
				. ($field->null ? '' : ' NOT NULL ');
		}
		return 'int(' . ($field->length ?: 11) . ')'
			. ($field->unsigned ? 'unsigned' : '')
			. ($field->null ? '' : ' NOT NULL ')
			. ($field->auto ? ' AUTO_INCREMENT ' : '')
			. ($field->default ? ' DEFAULT ' . $default : '');
	}

	public function dropTable(): void {
		$this->db()->query('DROP TABLE IF EXISTS ' . $this->db()->quoteIdent($this->table()));
	}

	public static function __create_static(array $args = []): static {
		$name = static::class;
		$args['_tipsy'] = Tipsy::app();
		$obj = new $name($args);
		$obj->save();
		return $obj;
	}

	public function __create(array $args = []): static {
		$object = clone $this;
		$object->tipsy($this->tipsy());
		$object->load($args);
		$object->save(true);
		return $object;
	}

	public function dbId(): mixed {
		return $this->{$this->idVar()};
	}

	/**
	 * Load the object with properties
	 *
	 * Passing in an object will populate $this with the current vars of that object
	 * as public properties. Passing in an int id will load the object with the
	 * table and key associated with the object.
	 *
	 * @param $id object|array|int|string|null
	 */
	public function load(object|array|int|string|null $id = null): static {
		// fill the object with blank properties based on the fields of that table
		$fields = $this->fields();
		foreach ($fields as $key => $field) {
			// Use isset — `?:` would coerce false/0 into '' and break PG booleans/ints
			if (!isset($this->{$key})) {
				$this->{$key} = '';
			}
		}

		if (!$id && $this->dbId()) {
			$id = $this->dbId();
		}

		$node = null;

		if (is_object($id)) {
			$node = $id;

		} elseif (is_array($id)) {
			$node = (object)$id;

		} elseif ($id) {
			$result = $this->db()->get(
				'SELECT * FROM ' . $this->db()->quoteIdent($this->table())
				. ' WHERE ' . $this->db()->quoteIdent($this->idVar()) . ' = ? LIMIT 1',
				[$id]
			);
			$node = !empty($result[0]) ? (object)$result[0] : null;
		}

		if (!$node) {
			$node = new Model();
		}

		foreach (get_object_vars($node) as $var => $value) {
			$this->$var = $value;
		}

		foreach ($this->fields() as $field) {
			match ($field->type) {
				'int' => $this->{$field->field} = (int)$this->{$field->field},
				'boolean' => $this->{$field->field} = (bool)$this->{$field->field},
				default => null,
			};
		}

		if ($this->tipsy() && ($this->tipsy()->config()['tipsy']['factory'] ?? null) !== false) {
			$this->tipsy()->factory($this);
		}

		return $this;
	}


	/**
	 * Saves an entry in the db. if there is no current id it will add one
	 */
	public function save(?bool $insert = null): static {
		if ($insert === null) {
			$insert = $this->dbId() ? false : true;
		}

		$table = $this->db()->quoteIdent($this->table());
		$query = $insert
			? 'INSERT INTO ' . $table
			: 'UPDATE ' . $table;

		$fields = $this->fields();
		$numset = 0;
		$fs = [];

		foreach ($fields as $field) {
			if ($field->auto === true && $insert && $field->field === $this->idVar()) {
				continue;
			}

			if ($this->{$field->field} === '' && $field->null) {
				$this->{$field->field} = null;
			} elseif ($this->{$field->field} === null && !$field->null) {
				$this->{$field->field} = match ($field->type) {
					'boolean' => false,
					'int' => 0,
					default => '',
				};
			}

			match ($field->type) {
				'boolean' => $this->_prepareBoolField($field),
				'int' => $this->{$field->field} = intval($this->{$field->field}),
				'datetime' => $this->_prepareDatetimeField($field),
				'date' => $this->_prepareDateField($field),
				default => null,
			};

			$this->{$field->field} = (!$this->{$field->field} && $field->null) ? null : $this->{$field->field};

			$ident = $this->db()->quoteIdent($field->field);
			$query .= !$numset ? ($insert ? '(' : ' SET ') : ',';
			if ($insert) {
				$query .= ' ' . $ident . ' ';
			} else {
				if ($field->field !== $this->idVar()) {
					$query .= ' ' . $ident . '=:' . $field->field;
				} else {
					// should probably be cleaned up a bit
					$query = substr($query, 0, -5);
					$numset--;
				}
			}

			$fs[$field->field] = $field->type === 'boolean'
				? $this->_boolForDriver((bool)$this->{$field->field})
				: $this->{$field->field};

			$numset++;

		}

		if ($insert) {
			$query .= ' ) VALUES (';
			$numset = 0;

			foreach ($fields as $field) {
				if ($field->auto === true) {
					continue;
				}

				$query .= !$numset ? '' : ',';
				$query .= ' :' . $field->field;
				$numset++;
			}

			$query .= ')';
		} else {
			$query .= ' WHERE ' . $this->db()->quoteIdent($this->idVar()) . '=:id';
			$fs['id'] = $this->{$this->idVar()};
		}

		$this->db()->query($query, $fs);

		if ($insert) {
			$this->{$this->idVar()} = $this->db()->db()->lastInsertId(
				isset($fields[$this->idVar()]->sequence) ? $fields[$this->idVar()]->sequence : null
			);
		}

		return $this;
	}

	private function _prepareBoolField(object $field): void {
		$value = $this->{$field->field};
		// Normalize empty strings left over from blank property fills
		if ($value === '' || $value === null) {
			$value = false;
		}
		// Keep a real bool on the model; driver literals are applied only when binding
		$this->{$field->field} = (bool)$value;
	}

	private function _boolForDriver(bool $value): int|string|bool {
		// PDO_PGSQL stringifies false as "" which Postgres rejects as boolean input
		return match ($this->db()->driver()) {
			'mysql' => $value ? 1 : 0,
			'pgsql' => $value ? 'true' : 'false',
			default => $value,
		};
	}

	private function _prepareDatetimeField(object $field): void {
		if ($this->{$field->field} === '0000-00-00 00:00:00' && $field->null) {
			$this->{$field->field} = null;
		}
	}

	private function _prepareDateField(object $field): void {
		if ($this->{$field->field} === '0000-00-00' && $field->null) {
			$this->{$field->field} = null;
		}
	}


	/**
	 * Delete a row in a table
	 */
	public function delete(): static {
		if ($this->dbId()) {
			$this->db()->query(
				'DELETE FROM ' . $this->db()->quoteIdent($this->table())
				. ' WHERE ' . $this->db()->quoteIdent($this->idVar()) . ' = ?',
				[$this->dbId()]
			);
		} else {
			throw new Exception('Cannot delete. No ID was given.');
		}
		return $this;
	}

	public function strip(): static {
		$fieldsMeta = $this->fields();
		$fieldNames = [];
		foreach ($fieldsMeta as $field) {
			$fieldNames[] = $field->field ?? $field->Field ?? '';
		}

		$vars = get_object_vars($this);
		foreach ($vars as $key => $var) {
			if (!in_array($key, $fieldNames, true) && isset($key[0]) && $key[0] !== '_') {
				unset($this->$key);
			}
		}
		return $this;
	}

	public function serialize(array|object $array): static {
		if (!is_array($array)) {
			$array = get_object_vars($array);
		}
		foreach ($array as $key => $val) {
			if (array_key_exists($key, $this->properties())) {
				$this->$key = $val;
			}
		}
		return $this;
	}

	public function idVar(?string $id_var = null): static|string|null {
		if ($id_var === null) {
			return $this->_id_var;
		} else {
			$this->_id_var = $id_var;
			return $this;
		}
	}

	public function table(?string $table = null): static|string|null {
		if ($table === null) {
			return $this->_table;
		} else {
			$this->_table = $table;
			return $this;
		}
	}

	public function __o(mixed ...$args): mixed {
		$classname = static::class;
		$items = [];

		foreach ($args as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $item) {
					$items[] = $this->tipsy()->factory($classname, $item);
				}
			} else {
				$items[] = $this->tipsy()->factory($classname, $arg);
			}
		}
		foreach ($items as $item) {
			$item->tipsy($this->tipsy());
		}

		if (count($items) === 1) {
			return array_pop($items);
		} else {
			return new Looper($items);
		}
	}

	public static function __o_static(): mixed {
		$classname = static::class;
		$items = [];

		foreach (func_get_args() as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $item) {
					$items[] = Tipsy::factory($classname, $item);
				}
			} else {
				$items[] = Tipsy::factory($classname, $arg);
			}
		}
		foreach ($items as $item) {
			$item->tipsy(Tipsy::app());
		}

		if (count($items) === 1) {
			return array_pop($items);
		} else {
			return new Looper($items);
		}
	}

	public static function __q_static(): mixed {
		return forward_static_call_array([static::class, '__query_static'], func_get_args());
	}

	public static function __query_static(): mixed {
		$name = static::class;
		$class = new $name();
		$class->tipsy(Tipsy::app());
		$class->service($name);
		return (new \ReflectionMethod($class, '__query'))->invokeArgs($class, func_get_args());
	}

	public function __q(string $query): mixed {
		return (new \ReflectionMethod($this, '__query'))->invokeArgs($this, func_get_args());
	}

	public function __query(string $query): mixed {

		$args = [];

		if (func_num_args() === 2 && is_array(func_get_arg(1))) {
			$args = func_get_arg(1);
		} elseif (func_num_args() > 1) {
			for ($i = 1; $i < func_num_args(); $i++) {
				$args[] = func_get_arg($i);
			}
		}

		$res = $this->db()->query($query, $args);

		$items = [];
		while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
			if ($this->tipsy()->services($this->service())) {
				$items[] = $this->tipsy()->service($this->service(), $row);
			} elseif (class_exists($this->service())) {
				$classname = $this->service();
				$items[] = new $classname($row);
			} else {
				$items[] = $row;
			}
		}
		return new Looper($items);
	}

	public function tipsy(?App $tipsy = null): ?App {
		if ($tipsy !== null) {
			$this->_tipsy = $tipsy;
		}
		return $this->_tipsy;
	}

	public function db(): Db {
		if ($this->tipsy()?->db()) {
			return $this->tipsy()->db();
		}
		return Tipsy::db();
	}

	public function service(?string $service = null): ?string {
		if ($service !== null) {
			$this->_service = $service;
		}
		return $this->_service;
	}
}
