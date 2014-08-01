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
	private $_view;
	private $_services;
	
	public function __construct() {
		$this->_controllers = [];
		$this->_config = [];
		$this->_models = [];
		$this->_services = [];
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
			
			$iterator = new \GlobIterator($args);
			
			foreach($iterator as $file) {
			    $config = parse_ini_file($file->getPathname(), true);
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
	
	public function service($service, $args = []) {
		list($model, $extend) = $this->_modelName($service, $args);

		if (!$this->_services[$model]) {
			$this->model($service, $args);
			$this->_services[$model] = $this->model($service, $args);;
		}
		return $this->_services[$model];

	}
	
	public function model($model, $args = []) {
		list($model, $extend) = $this->_modelName($model, $args);

		if (!$this->_models[$model]) {

			if ($model && is_callable($args)) {
				$config = call_user_func_array($args, []);

			} elseif ($model && !$args && class_exists($model)) {
				$extend = $model;

			} elseif ($model && is_array($args)) {
				$config = $args;

			}
			
			if ($this->_models[$extend]) {
				$extend = $this->_models[$extend];
			}

			$name = $extend ? $extend : 'Tipsy\Model';
			$config['model'] = $model;

			$this->_models[$model] = [
				'reflection' => new \ReflectionClass($name),
				'config' => $config
			];

			return $this;

		} else {

			if ($this->_models[$model]['reflection']->hasMethod('__construct')) {
				$config = array_merge(is_array($this->_models[$model]['config']) ? $this->_models[$model]['config'] : [],['tipsy' => $this],$args);
				$instance = $this->_models[$model]['reflection']->newInstance($config);
			} else {

				$instance = $this->_models[$model]['reflection']->newInstance();
			}

			foreach ($this->_models[$model]['config'] as $name => $config) {
				if (is_callable($config) && method_exists($instance, 'addMethod')) {
					$instance->addMethod($name, $config);
				} else {
					$instance->{$name} = $config;
				}
				$instance->_tipsy = $this;
			}

			return $instance;
		}
	}
	public function models($model = null) {
		if ($model) {
			return $this->_models[$model] ? true : false;
		}
		return $this->_models;
	}
	public function services($service = null) {
		if ($service) {
			return $this->_services[$service] ? true : false;
		}
		return $this->_services;
	}
	public function db() {
		if (!isset($this->_db)) {
			$this->_db = new Db($this->_config['db']);
		}
		return $this->_db;
	}
	public function view() {
		if (!isset($this->_view)) {
			$config = $this->_config['view'];
			$config['tipsy'] = $this;
			$this->_view = new View($config);
		}
		return $this->_view;
	}
	public function request() {
		if (!isset($this->_request)) {
			$this->_request = new Request;
		}
		return $this->_request;
	}

	private function _modelName($model, $args = []) {
		if (!is_null($args)) {
			$model = explode('/',$model);
			if (count($model) > 2) {
				throw new Exception('Cant extend more than one model.');
			} elseif (count($model) > 1) {
				$extend = array_shift($model);
			}
			$model = array_shift($model);
		}
		return [$model, $extend];
	}
}


class Service extends Model {
	
}


class Request {
	private $_properties;

    public function __construct() {
		$this->_properties = [];

        if ($this->method()) {
            switch ($this->method()) {
                case 'PUT':
                case 'DELETE':
                    if ($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded') {
                        parse_str($this->getContent(), $this->_properties);

                    } elseif ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                        $content = $this->getContent();
                        $request = json_decode($content,'array');
                        if (!$request) {
                            $this->_properties = false;
                        } else {
                            $this->_properties = $request;
                        }
                    }
                    break;

                case 'GET':
                    if ($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded' || !$_SERVER['CONTENT_TYPE']) {
                        $this->_properties = $_GET;
                    } elseif ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                        $this->_properties = $this->getRawRequest();
                    }
                    break;

                case 'POST':
                    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                        $this->_properties = json_decode($this->getContent(), 'array');
                    /* Found a case where the CONTENT_TYPE was 'application/x-www-form-urlencoded; charset=UTF-8'
                     *
                     * @todo Is there any case where we do not set the $request to $_POST nor the json?
                     * If not, there there should be OK to use the fallback scenario
                     */
                    // } elseif ($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded') {
                    } else  {
                        $this->_properties = $_POST;
                    }
                    break;
            }
        }
    }

    private function getContent() {
        if (!isset($this->_content)) {
            if (strlen(trim($this->_content = file_get_contents('php://input'))) === 0) {
                $this->_content = false;
            }
        }
        return $this->_content;
    }

    private function getRawRequest() {
        if (!isset($this->_rawRequest)) {

            $request = trim($_SERVER['REQUEST_URI']);
            $request = substr($request,strpos($request,'?')+1);
            $request = urldecode($request);
            $request = json_decode($request,'array');

            if (!$request) {
                $this->_rawRequest = false;
            } else {
                $this->_rawRequest = $request;
            }
        }
        return $this->_rawRequest;
    }

    public function method() {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

	public function &__get($name) {
		return $this->_properties[$name];
	}

	public function __set($name, $value) {
		return $this->_properties[$name] = $value;
	}
	
	public function &request() {
		return $this->_properties;
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
	
	public function __call($method, $args = []) {

		if (count($args) == 1) {
			$args[0]['method'] = strtoupper($method);
		} elseif (is_array($args[1])) {
			$args[1]['method'] = strtoupper($method);
		} else {
			$args[1] = [
				'controller' => $args[1],
				'method' => strtoupper($method)
			];
		}
		return call_user_func_array([$this, 'when'], $args);
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
		if (is_null($route['route'])) {
			throw new Exception('Invalid route specified.');
		}
		$route['tipsy'] = $this->_tipsy;
		
		if (!$route['method']) {
			$route['method'] = '*';
		}

		$this->_routes[] = new Route($route);
		
		return $this;
	}
	
	public function home($route) {
		return $this->when('', $route);
	}

	public function otherwise($default) {
		$this->_default = new Route([
			'controller' => $default,
			'method' => '*',
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
	private $_routeParams;

	public function __construct($args) {
		$this->_controller = $args['controller'];
		$this->_caseSensitive = $args['caseSensitive'] ? true : false;
		$this->_view = $args['view'] ? true : false;
		$this->_route = preg_replace('/^\/?(.*?)\/?$/i','\\1',$args['route']);
		$this->_tipsy = $args['tipsy'];
		$this->_method = $args['method'] == 'all' ? '*' : $args['method'];
		
		$this->_routeParams = new RouteParams;
	}
	
	public function match($page) {
	
		if ($this->method() != '*') {
			$methods = explode(',',strtolower($this->method()));
			$match = false;

			foreach ($methods as $method) {
				if ($method == strtolower($this->tipsy()->request()->method())) {
					$match = true;
					break;
				}
			}

			if (!$match) {
				return false;
			}
		}
		
		$pathParams = [];
		$paths = explode('/',$this->_route);
		
		// index page
		if (($this->_route === '' || $this->_route == '/') && ($page === '' || $page == '/')) {
			return $this;
		}

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
				$this->_routeParams->{$path} = $paths[$key];
			}
			
			return $this;
		}
		return false;
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

	public function method() {
		return $this->_method;
	}
}

/**
 * Controller object
 */
class Controller {
	private $_closure;
	private $_route;
	private $_scope;

	public function __construct($args = []) {
		if (isset($args['closure'])) {
			$this->_closure = \Closure::bind($args['closure'], $this, get_class());
		}
		if (isset($args['route'])) {
			$this->_route = $args['route'];
		}
		$this->_scope = new Scope;
	}
	public function init() {
		if ($this->closure()) {
			// @todo: dont need to define all this at once, just define the once referenced
			$exports = [
//				'db' => $this->route()->tipsy()->db(),
				'Route' => $this->route(),
				'Request' => $this->route()->tipsy()->request(),
				'Params' => $this->route()->params(),
				'Tipsy' => $this->route()->tipsy(),
				'View' => $this->route()->tipsy()->view(),
				'Scope' => $this->_scope
			];
			$this->route()->tipsy()->view()->scope($this->_scope);

			foreach ($this->route()->tipsy()->models() as $name => $model) {
				$exports[$name] = $this->route()->tipsy()->model($name);
			}
			foreach ($this->route()->tipsy()->services() as $name => $service) {
				$exports[$name] = $this->route()->tipsy()->service($name);
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

		$db = new \PDO('mysql:host='.$args['host'].';dbname='.$args['database'].';charset=utf8', $args['user'], $args['pass']);
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
}



class Model {
	private $_methods;
	private $_properties;

	public function json() {
		return json_encode($this->properties());
	}

	public function addMethod($method, $closure) {
		$this->_methods[$method] = $closure;
	}

	public function __call($method, $args) {
		if (is_callable($this->_methods[$method])) {
			$this->_methods[$method] = $this->_methods[$method]->bindTo($this);
			return call_user_func_array($this->_methods[$method], $args);
		} else {
			throw new Exception('Could not call ' . $method. ' on '.get_class());
		}
	}

	public function &properties() {
		return $this->_properties ? $this->_properties : get_object_vars($this);
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


























class DBO extends Model {
	private $_table;
	private $_id_var;
	private $_fields;
	private $_db;
	private $_tipsy;
	private $_baseConfig;
	private $_model;
	
	public function __construct($args = []) {

		if ($args['tipsy']) {
			$this->_tipsy = $args['tipsy'];
			unset($args['tipsy']);
		}
		if ($args['model']) {
			$this->_model = $args['model'];
			unset($args['model']);
		}
		/*
		foreach ($args as $key=>$arg) {
			echo $key."\n";
		}
		echo "\n\n";

		if (!$args) {

		die($this->_model);
			// auto table name and id
			$args = [
				'id' => '',
				'table' => ''
			];
		}
		*/

		if ($args['id']) {
			$this->_id_var = $args['id'];
			unset($args['id']);
		}
		if ($args['table']) {
			$this->_table = $args['table'];
			unset($args['table']);
		}
		if ($args) {
			$this->load($args);
		}
//		$this->_baseConfig = $args;
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
			if ($this->tipsy()->models($this->model())) {
				$items[] = $this->tipsy()->model($this->model(), $row);
			} elseif (class_exists($this->model())) {
				$items[] = new $classname($row);
			} else {
				$items[] = $row;
			}
		}
		return new Looper($items);
	}

	public function tipsy() {
		return $this->_tipsy;
	}
	public function db() {
		return $this->tipsy()->db();
	}
	public function model() {
		return $this->_model;
	}


}

class Exception extends \Exception {
	
}








class Scope {
	private $_properties;
	
	public function __construct() {
		$this->_properties = [];
	}

	public function &__get($name) {
		return $this->_properties[$name];
	}

	public function __set($name, $value) {
		return $this->_properties[$name] = $value;
	}
	
	public function &properties() {
		return $this->_properties;
	}
}

class RouteParams extends Scope {
	
}

























class View {
	private $_layout = 'layout';
	private $_headers;
	private $_rendering = false;
	private $_stack;
	private $_path = '';
	private $_tipsy;
	private $_filters = [];
	private $_extension = '.phtml';

	public function __construct ($args = []) {
		$this->headers = [];

		$this->config($args);
		
		$this->_tipsy = $args['tipsy'];
		$this->_scope = $scope;
	}
	
	public function config($args = null) {
		if (isset($args['layout'])) {
			$this->_layout = $args['layout'];
		}
		
		if (isset($args['stack'])) {
			$this->_stack = $args['stack'];
		}
		
		if (isset($args['path'])) {
			$this->_path = $args['path'];
		}
	}
	
	public function stack() {
		$stack = $this->tipsy()->config()['view']['stack'];
		if (!$stack) {
			$stack = [''];
		}
		return $stack;
	}
	
	public function file($src) {
		$stack = $this->stack();

		foreach ($stack as $dir) {
			$path = joinPaths($this->_path, $dir, $src.$this->_extension);
			if (file_exists($path) && is_file($path)) {
				$file = $path;
				break;
			}
		}

		return $file;
	}
	
	public function layout() {
		return $this->file($this->_layout);
	}

	public function render($view, $params = null) {
		if (isset($params['set'])) {
			foreach ($params['set'] as $key => $value) {
				$$key = $value;
			}
		}

		$file = $this->file($view);
		if (!$file) {
			throw new Exception('Could not find view file: "'.$view.'" in "'.(implode(',',$this->stack())).'"');
		}
		$layout = $this->layout();
		

		$p = $this->scope()->properties();

		extract($this->scope()->properties(), EXTR_REFS);

		if ($this->_rendering || !isset($params['display'])) {
			
			ob_start();
			include($file);
			$page = $this->filter(ob_get_contents(),$params);
			ob_end_clean();
			
		} else {
			
			$this->_rendering = true;
			ob_start();
			include($file);
			$this->content = $this->filter(ob_get_contents(),$params);
			ob_end_clean();
			
			if ($layout) {
				ob_start();
				include($layout);
				$page = $this->filter(ob_get_contents(),$params);
				ob_end_clean();
				$this->_rendering = false;
			} else {
				$page = $this->content;
			}
		}		
		
		if (isset($params['var'])) {
			$this->{$params['var']} = $page;
		}
		return $page;	
	}

	public function display($view,$params=null) {
	/*
		if (!headers_sent()) {
			foreach ($this->headers->http as $key => $value) {
				header(isset($value['name']) ? $value['name'].': ' : '' . $value['value'],isset($value['replace']) ? $value['replace'] : true);
			}
		}
		*/
		if (is_null($params)) {
			$params['display'] = true;
		}
		echo $this->render($view,$params);
	}

	public function filter($content) {
		foreach ($this->_filters as $filter) {
			$content = $filter::filter($content);
		}
		return $content;
	}
	
	public function tipsy() {
		return $this->_tipsy;
	}
	
	public function scope(&$scope = null) {
		if ($scope) {
			$this->_scope = $scope;
		}
		return $this->_scope;
	}
}

class View_Filter {
	
}

class StripWhite extends View_Filter {
	public static function filter($content) {
		$find = [
			'/^(\s?)(.*?)(\s?)$/',
			'/\t|\n|\r/',
			'/(\<\!\-\-)(.*?)\-\-\>/'
		];
		$replace = [
			'\\2',
			'',
			''
		];
		return preg_replace($find, $replace, $content);
	}
}


function joinPaths() {
	$args = func_get_args();
	$paths = [];
	foreach ($args as $arg) {
		$paths = array_merge($paths, (array)$arg);
	}

	$paths = array_map(create_function('$p', 'return trim($p, "/");'), $paths);
	$paths = array_filter($paths);
	return join('/', $paths);
}














































// @todo: clean this up. its just a copy and paste from cana. we dont need all of it
class Looper implements \Iterator {
	private $_items;
	private $_position;

	public function __construct() {
		$items = [];
		foreach (func_get_args() as $arg) {
			if (is_object($arg) && (get_class($arg) == 'Looper' || is_subclass_of($arg,'Looper'))) {
				$arg = $arg->items();
			} elseif (is_object($arg)) {
				$arg = [$arg];
			}
			$items = array_merge((array)$arg, $items);
		}

		$this->_items = $items;
		$this->_position = 0;
	}
	
	// if anyone knows any way to pass func_get_args by reference i would love you. i want string manipulation
	public static function o() {
		$iterator = new ReflectionClass(get_called_class());
		return $iterator->newInstanceArgs(func_get_args());
	}
	
	public function items() {
		return $this->_items;
	}
	
	public function get($index) {
		return $this->_items[$index];
	}

	public function eq($pos) {
		$pos = $pos < 0 ? count($this->_items) - abs($pos) : $pos;
		return $this->_items[$pos];
	}
	
	public function remove($start) {
		unset($this->_items[$start]);
		return $this;
	}
	
	public function slice($start, $end = null) {
		$items = $this->_items;
		$items = array_slice($items, $start, $end);

		return $this->_returnItems($items);
	}
	
	public function not() {
		$items = call_user_func_array([$this, '_filter'], func_get_args());
		return $this->_returnItems($items['no']);
	}
		
	public function filter() {
		$items = call_user_func_array([$this, '_filter'], func_get_args());
		return $this->_returnItems($items['yes']);
	}
	
	public function each($func, $params = []) {
		foreach ($this->_items as $key => $item) {
			$func = $func->bindTo(!is_object($item) ? (object)$item : $item);
			$func($key, $item);
			$this->_items[$key] = $item;
		}
	}
	
	public function e($f) {
		self::each($f);
	}

	public function rewind() {
		$this->_position = 0;
	}

	public function current() {
		return $this->_items[$this->_position];
	}

	public function key() {
		return $this->_position;
	}

	public function next() {
		++$this->_position;
	}

	public function valid() {
		return isset($this->_items[$this->_position]);
	}
	
	public function json() {
		foreach ($this->_items as $key => $item) {
			if (is_callable($item, 'exports') || method_exists($item, 'exports')) {
				$items[$key] = (new ReflectionMethod($item, 'exports'))->invokeArgs($item, []);
			}
			$items[$key] = $item->exports();
		}
		return json_encode($items);
	}
	
	public function count() {
		return count($this->_items);
	}

	public function parent() {
		return $this->_parent;
	}

	private function _filter() {
		$items = $this->_items;
		$mismatch = [];
		$strict = false;

		if (func_num_args() == 1 && is_callable(func_get_arg(0))) {
			$func = func_get_arg(0);

		} elseif (func_num_args() == 2 && !is_array(func_get_arg(0)) && !is_array(func_get_arg(1))) {
			$filters[][func_get_arg(0)] = func_get_arg(1);

		} else {
			foreach (func_get_args() as $arg) {
				if (is_array($arg)) {
					$filters[] = $arg;
				}
			}
		}

		if ($filters) {
			foreach ($filters as $key => $set) {
				foreach ($items as $key => $item) {
					$mis = 0;
					foreach ($set as $k => $v) {
						if ($item->{$k} != $v) {
							$mis++;
						}
					}
					if (($strict && count($set) == $mis) || $mis) {
						$mismatch[$key]++;
					}
				}
			}
		}
		
		if ($func) {
			foreach ($items as $key => $item) {
				if (!$func($item,$key)) {
					$mismatch[$key] = $key;
					break;
				}
			}
		}

		foreach ($items as $key => $value) {
			if (array_key_exists($key, $mismatch) && ($func || $mismatch[$key] == count($filters))) {
				$trash[] = $items[$key];
			} else {
				$newitems[] = $items[$key];
			}
		}
		
		return ['yes' => $newitems,'no' => $trash];
	}

	private function _returnItems($items) {
		if (count($items) != count($this->_items)) {
			$return = new self($items);
			$return->_parent = $this;
		} else {
			$return = $this;
		}
		return $return;
	}
	
	public function __toString() {
		$print = '';
		foreach ($this->_items as $key => $item) {
			if (is_object($item) && method_exists($item,'__toString')) {
				$print .= $item->__toString();
			} elseif (is_string($item) || is_int($item)) {
				$print .= $item;
			}
		}
		return $print;
	}
	
	// export all available objects as a csv. asume that they are all table objects
	// may not be the best place to put this but o well. exporting iterators is great.
	public function csv() {

		$fields = [];
		foreach ($this->_items as $key => $item) {
			if (is_object($item) && method_exists($item,'csv')) {
				foreach ($item->csv() as $field => $value) {
					$fields[$field] = $field;
				}
			}
		}
		$output = '';
		foreach ($fields as $field) {
			$output .= ($output ? ',' : '').$field;
		}
		$output .= "\n";
		foreach ($this->_items as $key => $item) {
			if (is_object($item) && method_exists($item,'csv')) {
				$o = $item->csv();
				foreach ($fields as $field) {
					$output .= '"'.addslashes($o[$field]).'",';
				}
				$output = substr($output,0,-1);
				$output .= "\n";
			}
		}
		return $output;
		
	}
	
	public function __call($name, $arguments) {
		foreach ($this->_items as $key => $item) {
			if (is_callable($item, $name) || method_exists($item, $name)) {
				$items[] = (new ReflectionMethod($item, $name))->invokeArgs($item, $arguments);
			} else {
				// not callable
			}
		}

		return i::o($items);
	}

	public function &__get($name) {
		if (property_exists($this,$name)) {
			return $this->{$name};
		} else {
			if (isset($name{0}) && $name{0} == '_') {
				return $this->_items[0]->{$name};
			} else {
				return $this->_items[0]->_properties[$name];
			}
		}
	}

	public function __set($name, $value) {
		if (property_exists($this,$name)) {
			$this->{$name} = $value;
		} else {
			foreach ($this->_items as $key => $item) {
				$this->_items[$key]->{$name} = $value;
			}
		}
		return $value;
	}
	
	public function __isset($property) {
		if (isset($property{0}) && $property{0} == '_') {
			return $this->_items[0]->{$property} ? true : false;
		} else {
			return $this->_items[0]->_properties[$property] ? true : false;
		}
	}
	
	public function __unset($property) {
		if (isset($property{0}) && $property{0} == '_') {
			unset($this->_items[0]->{$property});
		} else {
			unset($this->_items[0]->_properties[$property]);
		}
		return $this;
	}
}