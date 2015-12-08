<?php

namespace Tipsy;

/**
 * Main class.
 */
class App
{
    private $_controllers;
    private $_config;
    private $_view;
    private $_services;
    private $_route;
    private $_url;
    private $_middlewares;
    private $_middlewareStart;

    public function __construct()
    {
        $this->_controllers = [];
        $this->_middlewares = [];
        $this->_config = [];
        $this->_services = [];
        $this->_services = [];
        $this->_rootScope = new Scope();
        $this->_middlewareStart = false;

        $this->_id = sha1(rand(1, 900000));
    }

    public function run($url = null)
    {
        return $this->start($url);
    }

    public function start($url = null)
    {
        $this->_url = $this->request()->path($url);
        $this->_route = $this->router()->match($this->_url);
        $this->_middlewareStart = true;

        foreach ($this->middlewares() as $middleware) {
            if ($middleware['started']) {
                continue;
            }
            Middleware::_start($middleware, $this);
        }

        $this->_route->controller()->init();
    }

    public function router()
    {
        if (!isset($this->_router)) {
            $this->_router = new Router(['tipsy' => $this]);
        }

        return $this->_router;
    }

    public function controller($controller, $closure = null)
    {
        if ($controller && is_callable($closure)) {
            $this->_controllers[$controller] = new Controller([
                'closure' => $closure,
                'tipsy' => $this,
            ]);

            return $this;
        } elseif ($controller) {
            return $this->_controllers[$controller];
        } else {
            return;
        }
    }

    public function config($args = null, $recursive = false)
    {
        $merge = ($recursive ? 'array_merge_recursive' : 'array_merge');
        if (is_string($args)) {
            // assume its a config file

            $iterator = new \GlobIterator($args);

            foreach ($iterator as $file) {
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

    public function service($service, $args = null, $static = false)
    {
        list($service, $extend) = $this->_serviceName($service, $args);

        if (!$this->_services[$service]) {
            if (is_object($args) && !is_callable($args) && !$args instanceof Service) {
                throw new Exception('Service must be an instace of Tipsy\Service');
            } elseif ($service && is_callable($args)) {
                $config = ['_controller' => new Service([
                    'closure' => $args,
                    'tipsy' => $this,
                ])];
            } elseif ($service && (class_exists($service) || (!is_array($args) && (is_object($args) || class_exists($args))))) {
                $class = is_object($args) ? $args : ((is_string($args) && class_exists($args)) ? $args : $service);
                $extend = $class;
            } elseif ($service && is_array($args)) {
                $config = $args ? $args : [];
            }

            if (is_string($extend) && $this->_services[$extend]) {
                $extend = $this->_services[$extend];
            }

            if ($static) {
                $config['_static'] = true;
            }

            $name = $extend ? $extend : 'Tipsy\Service';
            //$name = ($extend && !is_null($args)) ? $extend : 'Tipsy\Service';

            $config['_service'] = $service;

            $this->_services[$service] = [
                'reflection' => new \ReflectionClass($name),
                'config' => $config,
            ];

            return $this;
        } else {
            if ($this->_services[$service]['config']['_controller']) {
                $this->_services[$service]['config'] = $this->_services[$service]['config']['_controller']->init(['tipsy' => $this]);
            }

            if ($this->_services[$service]['config']['_static'] && $this->_services[$service]['instance']) {
                return $this->_services[$service]['instance'];
            }

            if ($this->_services[$service]['reflection']->hasMethod('__construct')) {
                $config = array_merge(is_array($this->_services[$service]['config']) ? $this->_services[$service]['config'] : [], ['_tipsy' => $this], $args ? $args : []);
                $instance = $this->_services[$service]['reflection']->newInstance($config);
            } else {
                $instance = $this->_services[$service]['reflection']->newInstance();
            }

            if (is_array($this->_services[$service]['config'])) {
                foreach ($this->_services[$service]['config'] as $name => $config) {
                    if (is_callable($config) && method_exists($instance, 'addMethod')) {
                        $instance->addMethod($name, $config);
                    } else {
                        $instance->{$name} = $config;
                    }
                    $instance->tipsy($this);
                }
            }

            if ($this->_services[$service]['config']['_static']) {
                $this->_services[$service]['instance'] = $instance;
            }

            return $instance;
        }
    }

    public function services($service = null)
    {
        if ($service) {
            return $this->_services[$service] ? true : false;
        }

        return $this->_services;
    }

    public function db()
    {
        if (!isset($this->_db)) {
            if ($this->services('Db')) {
                $this->_db = $this->service('Db');
                $this->_db->connect($this->_config['db']);
            } else {
                $this->_db = new Db($this->_config['db']);
            }

            // kill the db config in case something gets outputted
            unset($this->_config['db']);
        }

        return $this->_db;
    }

    public function view()
    {
        if (!isset($this->_view)) {
            $config = $this->_config['view'];
            $config['tipsy'] = $this;
            $this->_view = new View($config);
        }

        return $this->_view;
    }

    public function request()
    {
        if (!isset($this->_request)) {
            $this->_request = new Request();
        }

        return $this->_request;
    }

    private function _serviceName($service, $args = null)
    {
        if (strpos($service, '/')) {//!is_null($args) &&
            $service = explode('/', $service);
            if (count($service) > 2) {
                throw new Exception('Cant extend more than one model.');
            } elseif (count($service) > 1) {
                $extend = array_shift($service);
            }
            $service = array_shift($service);
        } elseif ($args === null) {
            $extend = $service;
            $service = explode('\\', $service);
            $service = array_pop($service);
        }

        return [$service, $extend];
    }

    public function rootScope()
    {
        return $this->_rootScope;
    }

    public function route()
    {
        return $this->_route;
    }

    public function url()
    {
        return $this->_url;
    }

    public function middleware($service, $args = [])
    {
        if (!is_string($service)) {
            $args = $service;
            $service = uniqid();
        }

        if (is_object($args) && !is_callable($args) && !$args instanceof Middleware) {
            throw new Exception('Middleware must be an instace of Tipsy\Middleware');
        }

        if (!$this->_services[$service]) {
            $this->service($service, $args, true);
        }
        if ($this->_middlewares[$service]) {
            return $this->service($service);
        }
        $middleware = [
            'service' => $service,
            'args' => $args,
            'started' => $this->_middlewareStart,
        ];
        $this->_middlewares[$service] = $middleware;
        if ($this->_middlewareStart) {
            Middleware::_start($middleware, $this);
        }

        return $this->service($service);
    }

    public function middlewares()
    {
        return $this->_middlewares;
    }

    public function factoryCount()
    {
        return $this->_factory->count();
    }

    public function factory($a = null, $b = null)
    {
        if (!$this->_factory) {
            $this->_factory = new Factory($this);
        }

        return $this->_factory->objectMap($a, $b);
    }
}
