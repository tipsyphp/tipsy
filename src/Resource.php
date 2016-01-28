<?php

namespace Tipsy;

class Resource extends Model {
	private $_table;
	private $_id_var;
	private $_fields;
	private $_db;
	private $_tipsy;
	private $_baseConfig;
	private $_service;

	public function __construct($args = []) {

		$this->_baseConfig = $args;

		if ($args['_tipsy']) {
			$this->_tipsy = $args['_tipsy'];
			unset($args['_tipsy']);
		}
		if ($args['_service']) {
			$this->_service = $args['_service'];
			unset($args['_service']);
		}
		/*
		foreach ($args as $key=>$arg) {
			echo $key."\n";
		}
		echo "\n\n";

		if (!$args) {

			// auto table name and id
			$args = [
				'id' => '',
				'table' => ''
			];
		}
		*/

		if ($args['_id']) {
			$this->idVar($args['_id']);
			unset($args['_id']);
		}
		if ($args['_table']) {
			$this->table($args['_table']);
			unset($args['_table']);
		}
		if ($args['_fields']) {
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
	 * Will populate $this->fields based on he columns in the db for the
	 * current objects table.
	 *
	 * @return array
	 */
	public function fields() {
		if ($fields = $this->db()->fields($this->table())) {
			$this->_fields = $fields;
		} else {

			$fields = [];
			switch ($this->db()->driver()) {
				case 'mysql':
					$q = 'SHOW COLUMNS FROM `'.$this->table().'`';
					break;

				case 'sqlite':
					$q = 'PRAGMA table_info("'.$this->table().'")';
					break;

				case 'pgsql':
					$q = 'SELECT column_name as Field, data_type as Type, is_nullable as Null, column_default as Default FROM information_schema.columns WHERE table_name = \''.$this->table().'\'';
					break;
			}

			try {

				$rows = $this->db()->get($q);
				if (!count($rows)) {
					$this->createTable();
					} else {

					foreach ($rows as $row) {

						switch ($this->db()->driver()) {
							case 'sqlite':
								$fields[$row->name] = (object)[
									'field' => $row->name,
									'type' => $row->type,
									'null' => $row->notnull ? false : true
								];
								break;

							case 'mysql':
								$fields[$row->Field] = (object)[
									'field' => $row->Field,
									'type' => $row->Type,
									'null' => $row->Null == 'YES' ? true : false,
									'auto' => $row->Extra == 'auto_increment' ? true : false
								];

								if ($fields[$row->Field]->type == 'tinyint(1)' || $fields[$row->Field]->type == 'tinyint(1) unsigned') {
									$fields[$row->Field]->type = 'boolean';
								} elseif (strpos($fields[$row->Field]->type, 'int') !== false) {
									$fields[$row->Field]->type = 'int';
								}

								break;

							case 'pgsql':
								$fields[$row->field] = (object)[
									'field' => $row->field,
									'type' => $row->type,
									'null' => $row->null == 'YES' ? true : false,
									'auto' => $row->default && preg_match('/^nextval\(/',$row->default) ? true : false
								];

								if ($fields[$row->field]->type == 'integer') {
									$fields[$row->field]->type = 'int';
								}

								if ($fields[$row->field]->auto && $fields[$row->field]->field == $this->idVar()) {
									$fields[$row->field]->sequence = preg_replace('/^nextval\(\'(.*)\'.*\)$/i','\\1', $row->default);
								}
								break;
						}
					}
				}

			} catch (\Exception $e) {
				// table doesnt exist. create it if we can
				$this->createTable();
			}

			$this->_fields = $fields;
			$this->db()->fields($this->table(), $fields);
		}
		return $this->_fields;
	}

	public function get($id = null) {
		return $this;
	}

	public function createTable() {
		if (!$this->__fields) {
			throw new Exception('Could not create table "'.$this->table().'"');
		}

		$q = 'create table `'.$this->table().'` (';
		foreach ($this->__fields as $k => $field) {
			$q .= ' `'.$k.'` '.$field->name.' ';

			if ($field->default === null) {
				$default = 'NULL';
			} elseif ($field->default === true) {
				$default = 'true';
			} elseif ($field->default === false) {
				$default = 'false';
			} elseif (is_int($field->default)) {
				$default = $field->default;
			} else {
				$default = "'".$field->default."'";
			}

			switch ($field->type) {
				case 'int':
					if ($this->db()->driver() == 'pgsql') {
						$int = $field->auto ? 'serial' : 'integer'
							.($field->null ? '' : ' NOT NULL ');
					} else {
						$int = 'int('.($field->length ? $field->length : 11).')'
							.($field->unsigned ? 'unsigned' : '')
							.($field->null ? '' : ' NOT NULL ')
							.($field->auto ? ' AUTO_INCREMENT ' : '')
							.($field->default ? ' DEFAULT '.$default : '');
					}
					$q .= $int.' ';
					break;

				case 'char':
					$q .= 'varchar('.($field->length ? $field->length : 255).') '
						  .($field->null ? '' : ' NOT NULL ')
						  .($field->default ? ' DEFAULT '.$default : '');
					break;

				case 'bool':
					if ($this->db()->driver() == 'pgsql') {
						$bool = 'bool NOT NULL ';
					} else {
						$bool = 'tinyint(1) NOT NULL ';
					}
					$q .= $bool.' '
						  .($field->default ? ' DEFAULT '.$default : '');
					break;
			}
			$q .= ',';
		}

		$q .= 'PRIMARY KEY  (`'.$this->idVar().'`))';

		$this->db()->query($q);
	}

	public function dropTable() {
		$this->db()->query('DROP TABLE IF EXISTS `'.$this->table().'`');
	}

	public static function __create_static($args = []) {
		$name = get_called_class();
		$args['_tipsy'] = Tipsy::app();
		$obj = new $name($args);
		$obj->save();
		return $obj;
	}

	public function __create($args = []) {
		$object = clone $this;
		$object->tipsy($this->tipsy());
		$object->load($args);
		$object->save(true);
		return $object;
	}

	public function dbId() {
		return $this->{$this->idVar()};
	}

	/**
	 * Load the object with properties
	 *
	 * Passing in an object will populate $this with the current vars of that object
	 * as public properties. Passing in an int id will load the object with the
	 * table and key associated with the object.
	 *
	 * @param $id object|int
	 */
	public function load($id = null) {
		// fill the object with blank properties based on the fields of that table
		$fields = $this->fields();
		foreach ($fields as $key => $field) {
			$this->{$key} = $this->{$key} ? $this->{$key} : '';
		}

		if (!$id && $this->dbId()) {
			$id = $this->dbId();
		}

		if (is_object($id)) {
			$node = $id;

		} elseif (is_array($id)) {
			$node = (object)$id;

		} elseif ($id) {
			if (!$node) {
				$node = (object)$this->db()->get('select * from `' . $this->table() . '` where `'.$this->idVar().'` = ? limit 1', [$id])[0];
			}
		}

		if (!$node) {
			$node = new Model;
		}

		if (isset($node)) {
			foreach(get_object_vars($node) as $var => $value) {
				$this->$var = $value;
			}
		}

		foreach ($this->fields() as $field) {
			switch ($field->type) {
				case 'int':
					$this->{$field->field} = (int)$this->{$field->field};
					break;
				case 'boolean':
					$this->{$field->field} = $this->{$field->field} ? true : false;
					break;
			}
		}

		if ($this->tipsy() && $this->tipsy()->config()['tipsy']['factory'] !== false) {
			$this->tipsy()->factory($this);
		}

		return $this;
	}


	/**
	 * Saves an entry in the db. if there is no curerent id it will add one
	 */
	public function save($insert = null) {
		if (is_null($insert)) {
			$insert = $this->dbId() ? false : true;
		}

		if ($insert) {
			$query = 'INSERT INTO `'.$this->table().'`';
		} else {
			$query = 'UPDATE `'.$this->table().'`';
		}

		$fields = $this->fields();

		$numset = 0;

		foreach ($fields as $field) {
			if ($field->auto === true && $insert && $field->field == $this->idVar()) {
				continue;
			}

			if ($this->{$field->field} == '' && $field->null) {
				$this->{$field->field} = null;
			} elseif ($this->{$field->field} == null && !$field->null) {
				$this->{$field->field} = '';
			}

			switch ($field->type) {
				case 'boolean':
					$this->{$field->field} = $this->{$field->field} ? true : false;
					break;

				case 'int':
					$this->{$field->field} = intval($this->{$field->field});
					break;

				case 'datetime':
					if ($this->{$field->field} == '0000-00-00 00:00:00' && $field->null) {
						$this->{$field->field} = null;
					}
					break;

				case 'date':
					if ($this->{$field->field} == '0000-00-00' && $field->null) {
						$this->{$field->field} = null;
					}
					break;
			}

			$this->{$field->field} = (!$this->{$field->field} && $field->null) ? null : $this->{$field->field};

			$query .= !$numset ? ($insert ? '(' : ' SET ') : ',';
			if ($insert) {
				$query .= ' `'.$field->field.'` ';
			} else {
				if ($field->field != $this->idVar()) {
					$query .= ' `'.$field->field.'`=:'.$field->field;
				} else {
					// should proabably be cleaned up a bit
					$query = substr($query, 0, -5);
					$numset--;
				}
			}

			$fs[$field->field] = $this->{$field->field};

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
				$query .= ' :'.$field->field;
				$numset++;
			}

			$query .= ')';
		} else {
			$query .= ' WHERE '.$this->idVar().'=:id';
			$fs['id'] = $this->{$this->idVar()};
		}

		$stmt = $this->db()->query($query, $fs);

		if ($insert) {
			$this->{$this->idVar()} = $this->db()->db()->lastInsertId($fields[$this->idVar()]->sequence ? $fields[$this->idVar()]->sequence : null);
		}

		return $this;
	}


	/**
	 * Delete a row in a table
	 */
	public function delete() {
		if ($this->dbId()) {
			$this->db()->query('DELETE FROM `'.$this->table().'` WHERE `'.$this->idVar().'` = ?', [$this->dbId()]);
		} else {
			throw new Exception('Cannot delete. No ID was given.');
		}
		return $this;
	}

	public function strip() {
		$fieldsMeta = $this->fields();
		foreach ($fieldsMeta as $field) {
			$fields[] = $field->Field;
		}

		$vars = get_object_vars($this);
		foreach ($vars as $key => $var) {
			if (!in_array($key, $fields) && $key{0} != '_') {
				unset($this->$key);
			}
		}
		return $this;
	}

	public function serialize($array) {
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

	public function idVar($id_var = null) {
		if (is_null($id_var)) {
			return $this->_id_var;
		} else {
			$this->_id_var = $id_var;
			return $this;
		}
	}

	public function table($table = null) {
		if (is_null($table)) {
			return $this->_table;
		} else {
			$this->_table = $table;
			return $this;
		}
	}

	public function __o($args) {
		//return call_user_func_array([self, '__o_static'], func_get_args());
		$classname = get_called_class();

		foreach (func_get_args() as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $item) {
					$items[] = $this->tipsy()->factory($classname,$item);
				}
			} else {
				$items[] = $this->tipsy()->factory($classname,$arg);
			}
		}
		foreach ($items as $item) {
			$item->tipsy($this->tipsy());
			echo get_called_class($item);
		}

		if (count($items) == 1) {
			return array_pop($items);
		} else {
			return new Looper($items);
		}
	}

	public static function __o_static() {
		$classname = get_called_class();

		foreach (func_get_args() as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $item) {
					$items[] = Tipsy::factory($classname,$item);
				}
			} else {
				$items[] = Tipsy::factory($classname,$arg);
			}
		}
		foreach ($items as $item) {
			$item->tipsy(Tipsy::app());
		}

		if (count($items) == 1) {
			return array_pop($items);
		} else {
			return new Looper($items);
		}
	}

	public static function __q_static() {
		return call_user_func_array([self, '__query_static'], func_get_args());
	}

	public static function __query_static() {
		$name = get_called_class();
		$class = new $name();
		$class->tipsy(Tipsy::app());
		$class->service($name);
		return (new \ReflectionMethod($class, '__query'))->invokeArgs($class, func_get_args());
	}

	public function __q($query) {
		return (new \ReflectionMethod($this, '__query'))->invokeArgs($this, func_get_args());
	}

	public function __query($query) {

		$args = [];

		if (func_num_args() == 2 && is_array(func_get_arg(1))) {
			$args = func_get_arg(1);
		} elseif (func_num_args() > 1) {
			for ($i = 1; $i < func_num_args(); $i++){
				$args[] = func_get_arg($i);
			}
		}

		$res = $this->db()->query($query, $args);

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

	public function tipsy($tipsy = null) {
		if (!is_null($tipsy)) {
			$this->_tipsy = $tipsy;
		}
		return $this->_tipsy;
	}

	public function db() {
		if ($this && $this->tipsy() && $this->tipsy()->db()) {
			return $this->tipsy()->db();
		} else {
			return Tipsy::db();
		}
	}

	public function service($service = null) {
		if (!is_null($service)) {
			$this->_service = $service;
		}
		return $this->_service;
	}
}
