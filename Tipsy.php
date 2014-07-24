<?php

/**
 * Tipsy
 * An MVW PHP Framework
 */
 
 
namespace Tipsy;



/**
 * Main class
 */
class Tipsy {
	private $_controllers;
	private $_config;
	private $_models;
	
	public function __construct() {
		$this->_controllers = [];
		$this->_config = [];
		$this->_models = [];
	}

	public function start() {
		// Use the __url variable instead of the request URL. this can be passed from .htaccess
		$url = $_REQUEST['__url'] ? $_REQUEST['__url'] : explode('?', $_SERVER['REQUEST_URI'], 2)[0];
		$this->page = explode('/', $url);
		$route = $this->router()->match($url);

		$route->controller()->init();
	}
	public function router() {
		if (!isset($this->_router)) {
			$this->_router = new Router(['tipsy' => $this]);
		}
		return $this->_router;
	}
	public function controller($controller, $closure = null) {
		if ($controller && is_callable($closure)) {
			$this->_controllers[$controller] = new Controller(['closure' => $closure]);
			return $this;
		} elseif ($controller) {
			return $this->_controllers[$controller];
		} else {
			return null;
		}
	}
	public function config($args = null, $recursive = false) {
		$merge = ($recursive ? 'array_merge_recursive' : 'array_merge');
		if (is_string($args)) {
			// assume its a config file
			$config = parse_ini_file($args, true);
			if ($config === false) {
				throw new Exception('Failed to read config.');
			} else {
				$this->_config = $merge($this->_config, $config);
			}
			
			return $this;

		} elseif (is_array($args)) {
			$this->_config = $merge($this->_config, $args);
			return $this;

		} else {
			return $this->_config;
		}		
	}
	public function model($model, $args = null) {
		if ($args) {
			$model = explode('/',$model);
			if (count($model) > 2) {
				throw new Exception('Cant extend more than one model.');
			} elseif (count($model) > 1) {
				$extend = array_shift($model);
			}
			$model = array_shift($model);

			if ($model && is_callable($args)) {
				$config = call_user_func_array($args, []);
			} elseif ($model && is_array()) {
				$config = $args;
			}
			
			if ($this->_models[$extend]) {
				$extend = $this->_models[$extend];
			}

			$this->_models[$model] = [
				'reflection' => new \ReflectionClass('Tipsy\\'.($extend ? $extend : 'Model')),
				'config' => $config
			];
			return $this;

		} else {
			$instance = $this->_models[$model]['reflection']->newInstance($this->_model[$model]['config']);

			foreach ($this->_models[$model]['config'] as $name => $config) {
				if (is_callable($config)) {
					$instance->addMethod($name, $config);
				} else {
					$instance->{$name} = $config;
				}
				$instance->_tipsy = $this;
			}

			return $instance;
		}
	}
	public function models() {
		return $this->_models;
	}
	public function db() {
		if (!isset($this->_db)) {
			$this->_db = new Db($this->_config['db']);
		}
		return $this->_db;
	}
}


/**
 * Handles definition and resolution of routes to controllers
 */
class Router {

	private $_routes;
	private $_tipsy;

	public function __construct($args = []) {
		$this->_routes = [];
		$this->_tipsy = $args['tipsy'];
	}

	public function when($r, $args = null) {
		if (is_array($r)) {
			$route = $r;
		} else {
			if (is_array($args)) {
				$route = $args;			
			} else {
				$route = ['controller' => $args];
			}
			$route['route'] = $r;
		}
		$route['tipsy'] = $this->_tipsy;

		$this->_routes[] = new Route($route);
		
		return $this;
	}
	
	public function otherwise($default) {
		$this->_default = new Route([
			'controller' => $default,
			'tipsy' => $this->_tipsy
		]);
	}
	
	public function match($page) {
		foreach ($this->routes() as $route) {
			if ($route->match($page)) {
				return $route;
			}
		}

		return $this->defaultRoute();
	}
	
	public function routes($routes = null) {
		if (isset($$routes)) {
			$this->_routes = $routes;
		}
		return $this->_routes;
	}
	
	public function defaultRoute() {
		return $this->_default ? $this->_default : new Route(['tipsy' => $this->_tipsy]);
	}

}

/**
 * Route object
 */
class Route  {

	private $_tipsy;

	public function __construct($args) {
		$this->_controller = $args['controller'];
		$this->_caseSensitive = $args['caseSensitive'] ? true : false;
		$this->_view = $args['view'] ? true : false;
		$this->_route = preg_replace('/^\/?(.*?)\/?$/i','\\1',$args['route']);
		$this->_tipsy = $args['tipsy'];
	}
	
	public function match($page) {

		$this->_routeParams = [];
		
		$pathParams = [];
		$paths = explode('/',$this->_route);

		foreach ($paths as $key => $path) {
			if (strpos($path,':') === 0) {
				$pathParams[$key] = substr($path,1);
			}
		}

		$r = preg_replace('/:[a-z]+/i','.*',$this->_route);
		$r = preg_replace('/\//','\/',$r);

		if (preg_match('/^'.$r.'$/'.($this->_caseSensitive ? '' : 'i'),$page)) {
			$paths = explode('/',$page);

			foreach ($pathParams as $key => $path) {
				$this->_routeParams[$path] = $paths[$key];
			}
			
			return $this;
		}
		return false;
	}

	public function param($param) {
		return $this->_routeParams[$param];
	}
	
	public function params() {
		return $this->_routeParams;
	}
	
	public function controller() {

		if (!isset($this->_controllerRef)) {

			if (is_callable($this->_controller)) {

				$controller = new Controller([
					'closure' => $this->_controller
				]);
				$this->_controllerRef = $controller;

			} elseif(is_object($this->_controller)) {
				$this->_controllerRef = $this->_controller;

			} elseif (is_string($this->_controller) && $this->tipsy()->controller($this->_controller)) {

				$this->_controllerRef = $this->tipsy()->controller($this->_controller);

			} elseif (is_string($this->_controller) && class_exists($this->_controller)) {

				$this->_controllerRef = new $this->_controller;
			}
			
			if ($this->_controllerRef) {
				$this->_controllerRef->route($this);
			}
		}
		
		if (!$this->_controllerRef) {
			throw new Exception('No controller attached to route.');
		}
		
		return $this->_controllerRef;
	}
	
	public function tipsy() {
		return $this->_tipsy;
	}
}

/**
 * Controller object
 */
class Controller {
	private $_closure;
	private $_route;
	public $scope;

	public function __construct($args = []) {
		if (isset($args['closure'])) {
			$this->_closure = \Closure::bind($args['closure'], $this, get_class());
		}
		if (isset($args['route'])) {
			$this->_route = $args['route'];
		}
		$this->scope = new Scope;
	}
	public function init() {
		if ($this->closure()) {
			$exports = [
//				'db' => $this->route()->tipsy()->db(),
				'route' => $this->route(),
				'params' => $this->route()->params(),
				'tipsy' => $this->route()->tipsy()
			];

			foreach ($this->route()->tipsy()->models() as $name => $model) {
				$exports[$name] = $this->route()->tipsy()->model($name);
			}
			$args = [];
		
			$refFunc = new \ReflectionFunction($this->_closure);
			foreach ($refFunc->getParameters() as $refParameter) {
				if ($exports[$refParameter->getName()]) {
					$args[] = $exports[$refParameter->getName()];
				} else {
					$args[] = null;
				}
			}

			call_user_func_array($this->_closure, $args);
		}
	}
	public function closure() {
		return $this->_closure;
	}
	
	public function route($route = null) {
		if ($route) {
			$this->_route = $route;
		}
		return $this->_route;
	}

	
}

/**
 * Scope object
 */
class Scope {
	
}

class Db {
	private $_db;
	private $_fields;

	public function __construct($config = []) {
		$db = $this->connect($config);
		$this->_db = $db;

	}
	public function connect($args) {
		$db = new \PDO('mysql:host='.$args['host'].';dbname='.$args['database'].';charset=utf8', $args['user'], $args['pass']);
		//$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		//$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		return $db;
	}

	public function query($query, $args = []) {
		$stmt = $this->db()->prepare($query);
		$stmt->execute($args);
		return $stmt;
	}
	
	public function fetch($query, $args = []) {
		$stmt = $this->query($query, $args);
		return $stmt->fetchObject();
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
}

class Model {
	private $_methods;
	public function json() {
		return json_encode($this->values());
	}
	public function values() {
		return $this->_values ? $this->_values : get_object_vars($this);
	}
	public function addMethod($method, $closure) {
		$this->_methods[$method] = $closure;
	}
	public function __call($method, $args) {
		if (is_callable($this->_methods[$method])) {
			return call_user_func_array($this->_methods[$method], $args);
		} else {
			throw new Exception('Could not call ' . $method. ' on '.get_class());
		}
	}
}

class Iterator {
	
}






















class DBO extends Model {
	private $_table;
	private $_id_var;
	private $_fields;
	private $_db;
	private $_properties;
	private $_tipsy;
	private $_baseConfig;
	
	public function __construct($args = []) {
		if ($args['id']) {
			$this->_id_var = $args['id'];
		}
		if ($args['table']) {
			$this->_table = $args['table'];
		}
		$this->_baseConfig = $args;
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
			$rows = $this->db()->get('SHOW COLUMNS FROM `'.$this->table().'`');
			foreach ($rows as $row) {
				$row->Null = $row->Null == 'YES' ? true : false;
				$fields[] = $row;
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
	
	public function create($args = []) {
		$class = get_called_class();
		$object = new $class($this->_baseConfig);
		$object->_tipsy = $this->_tipsy;
		$object->load($args);
		$object->save();
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
		foreach ($fields as $field) {
			$this->{$field->Field} = $this->{$field->Field} ? $this->{$field->Field} : '';
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
	public function save($id = null) {

		$insert = $this->dbId() ? false : true;

		if ($id) {
			$this->{$this->idVar()} = $id;
		}

		if ($insert) {
			$query = 'INSERT INTO `'.$this->table().'`';
		} else {
			$query = 'UPDATE `'.$this->table().'`';
		}

		$fields = $this->fields();

		$numset = 0;
		$args = [];

		foreach ($fields as $field) {
			if ($this->property($field->Field) === false) {
				continue;
			}

			if ($this->{$field->Field} == '' && $field->Null) {
				$this->{$field->Field} = null;
			} elseif ($this->{$field->Field} == null && !$field->Null) {
				$this->{$field->Field} = '';
			}

			$query .= !$numset ? ' SET' : ',';
			$query .= ' `'.$field->Field.'`= ? ';
			$args[] = is_null($this->{$field->Field}) ? null : $this->{$field->Field};
			$numset++;

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

	public function db($db = null) {
		return $this->_tipsy->db();
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

	public function properties() {
		return $this->_properties;
	}

	public function property($name) {
		return isset($this->_properties[$name]) ? $this->_properties[$name] : null;
	}

	public function &__get($name) {
		if (isset($name{0}) && $name{0} == '_') {
			return $this->{$name};
		} else {
			return $this->_properties[$name];
		}
	}

	public function __set($name, $value) {
		if ($name{0} == '_') {
			return $this->{$name} = $value;
		} else {
			return $this->_properties[$name] = $value;
		}
	}

	public function __isset($name) {
		return $name{0} == '_' ? isset($this->{$name}) : isset($this->_properties[$name]);
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
			return new Cana_Iterator($items);
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

	public static function l($list) {
		$list = Cana_Model::l2a($list);
		return self::o($list);
	}

	public static function c($list) {
		$list = Cana_Model::l2a($list, ',');
		return self::o($list);
	}

	public static function q($query, $args = []) {

		$res = $db->query($query);
		$classname = get_called_class();
		while ($row = $res->fetch()) {
			$items[] = new $classname($row);
		}
		return new Cana_Iterator($items);
	}

	public function exports() {
		return $this->properties();
	}

	public function csv() {
		$csv = $this->properties();
		if ($this->idVar() != 'id') {
			unset($csv['id']);
		}
		return $csv;
	}


}


class File extends DBO {
	private $_id_var = 'id';
	private $_table = 'file';
}

class Instanciator {
	public function o($id) {
		
	}
}

class Exception extends \Exception {
	
}