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

		die($this->_service);
			// auto table name and id
			$args = [
				'id' => '',
				'table' => ''
			];
		}
		*/

		if ($args['_id']) {
			$this->_id_var = $args['_id'];
			unset($args['_id']);
		}
		if ($args['_table']) {
			$this->_table = $args['_table'];
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
			}

			$rows = $this->db()->get($q);

			try {
				$rows = $this->db()->get($q);
				
				foreach ($rows as $row) {

					switch ($this->db()->driver()) {
						case 'sqlite':
							$fields[$row->name] = [
								'type' => $row->type,
								'null' => $row->notnull ? false : true
							];
							break;
	
						case 'mysql':
							$fields[$row->Field] = [
								'type' => $row->Type,
								'null' => $row->Null == 'YES' ? true : false
							];
							break;
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
	
	public function get($id) {
		$class = get_called_class();
		$object = new $class($this->_baseConfig);
		$object->_tipsy = $this->_tipsy;
		$object->load($id);
		return $object;
	}
	
	public function createTable() {
		if (!$this->__fields) {
			throw new Exception('Could not create table "'.$this->table().'"');
		}

		$q = 'create table `'.$this->table().'` (';
		foreach ($this->__fields as $k => $field) {
			$q .= ' `'.$k.'` '.$field.' NULL, ';
		}
		
		$q .= 'PRIMARY KEY  (`'.$this->idVar().'`))';

		$this->db()->query($q);
	}
	
	public function dropTable() {
		$this->db()->query('DROP TABLE `'.$this->table().'`');
	}
	
	public function create($args = []) {
		$class = get_called_class();
		// @note: not sure if this is good or bad....
		//$object = new $class($this->_baseConfig);
		$object = clone $this;
		$object->_tipsy = $this->_tipsy;
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


/*
		if (Cana::config()->cache->object !== false) {
			Cana::factory($this);
		}
*/
		return $this;
	}


	/**
	 * Saves an entry in the db. if there is no curerent id it will add one
	 */
	public function save($insert = null) {

		if (is_null($insert)) {
			$insert = $this->dbId() ? false : true;
		}

		if ($id) {
			$this->{$this->idVar()} = $id;
		}

		if ($insert) {
			$query = 'INSERT INTO `'.$this->table().'` VALUES(';
		} else {
			$query = 'UPDATE `'.$this->table().'` SET ';
		}

		$fields = $this->fields();

		$args = [];

		foreach ($fields as $k => $field) {

			if ($this->property($k) === false) {
				continue;
			}

			if ($this->{$k} == '' && $field['null']) {
				$this->{$k} = null;
			} elseif ($this->{$k} == null && !$field['null']) {
				$this->{$k} = '';
			}
			
			$q1 .= $q1 ? ', ' : '';
			if ($insert) {
				$q1 .= ' ? ';
			} else {
				$q1 .= ' `'.$k.'`= ? ';
			}

			$args[] = is_null($this->{$k}) ? null : $this->{$k};
		}
		
		$query .= $q1;

		if ($insert) {
			$query .= ')';
		}

		if (!$insert) {
			$query .= ' WHERE '.$this->idVar().'= ?';
			$args[] = $this->dbId();
		}

		$this->db()->query($query, $args);

		if ($insert) {
			$this->{$this->idVar()} = $this->db()->db()->lastInsertId();
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

	public static function o() {
		$classname = get_called_class();
		foreach (func_get_args() as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $item) {
					$items[] = Cana::factory($classname,$item);
				}
			} else {
				$items[] = Cana::factory($classname,$arg);
			}
		}

		if (count($items) == 1) {
			return array_pop($items);
		} else {
			return new Looper($items);
		}

	}

	public function s() {
		if (func_num_args() == 2) {
			$this->{func_get_arg(0)} = func_get_arg(1);
		} elseif (func_num_args() == 1 && is_array(func_get_arg(0))) {
			foreach (func_get_arg(0) as $key => $value) {
				$this->{$key} = $value;
			}
		}
		return $this;
	}
	
	public function zget($query, $int = 0) {
		return $this->q($query)->get($int);
	}

	public function q($query) {
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
		return $this->tipsy()->db();
	}
	public function service() {
		return $this->_service;
	}


}