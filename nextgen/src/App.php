<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Main application class that handles routing, services, and middleware
 */
class App {
	private array $_controllers;
	private array $_config;
	private ?View $_view = null;
	private array $_services;
	private ?Route $_route = null;
	private ?string $_url = null;
	private array $_middlewares;
	private bool $_middlewareStart;
	private ?Router $_router = null;
	private ?Request $_request = null;
	private ?Db $_db = null;
	private Scope $_rootScope;
	private ?Factory $_factory = null;
	private string $_id;

	public function __construct(?array $params = null) {
		$this->_controllers = [];
		$this->_middlewares = [];
		$this->_config = [];
		$this->_services = [];
		$this->_rootScope = new Scope();
		$this->_middlewareStart = false;
		$this->_id = sha1((string)rand(1, 900000));
	}

	public function run(?string $url = null): mixed {
		return $this->start($url);
	}

	public function start(?string $url = null): mixed {
		$this->_url = $this->request()->path($url);
		$this->_route = $this->router()->match($this->_url);
		$this->_middlewareStart = true;

		foreach ($this->middlewares() as $middleware) {
			if ($middleware['started']) {
				continue;
			}
			Middleware::_start($middleware, $this);
		}

		return $this->_route->controller()->init();
	}
	public function router(): Router {
		if (!isset($this->_router)) {
			$this->_router = new Router(['tipsy' => $this]);
		}
		return $this->_router;
	}

	public function controller(string $controller = null, callable $closure = null): mixed {
		if ($controller && is_callable($closure)) {
			$this->_controllers[$controller] = new Controller([
				'closure' => $closure,
				'tipsy' => $this
			]);
			return $this;

		} elseif ($controller) {
			return $this->_controllers[$controller] ?? null;

		} else {
			return null;
		}
	}
	public function config(string|array|null $args = null, int $recursive = 0): mixed {
		$merge = match ($recursive) {
			2 => 'array_merge',
			1 => 'array_merge_recursive',
			default => 'array_replace_recursive'
		};

		if (is_string($args)) {
			$iterator = new \GlobIterator($args);

			foreach ($iterator as $file) {
				$config = match ($file->getExtension()) {
					'ini' => parse_ini_file($file->getPathname(), true),
					'yaml', 'yml' => $this->parseYamlFile($file->getPathname()),
					default => throw new Exception('Unknown file type: "' . $file->getFileName() . '"')
				};

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

	private function parseYamlFile(string $pathname): array {
		if (function_exists('yaml_parse_file')) {
			return yaml_parse_file($pathname);
		} elseif (class_exists('\Symfony\Component\Yaml\Parser')) {
			$yaml = new \Symfony\Component\Yaml\Parser();
			return $yaml->parse(file_get_contents($pathname));
		} else {
			throw new Exception('Could not find yaml parser. Try "composer require symfony/yaml"');
		}
	}

	public function service(string $service, mixed $args = null, bool $static = false): mixed {
		[$service, $extend] = $this->_serviceName($service, $args);

		if (!isset($this->_services[$service])) {

			if (is_object($args) && !is_callable($args) && !$args instanceof Service) {
				throw new Exception('Service must be an instance of Tipsy\Service');

			} elseif ($service && is_callable($args)) {
				$config = ['_controller' => new Service([
					'closure' => $args,
					'tipsy' => $this
				])];

			} elseif ($service && (class_exists($service) || (!is_array($args) && (is_object($args) || class_exists((string)$args))))) {
				$class = is_object($args) ? $args : ((is_string($args) && class_exists($args)) ? $args : $service);
				$extend = $class;

			} elseif ($service && is_array($args)) {
				$config = $args ?: [];
			}

			if (is_string($extend) && isset($this->_services[$extend])) {
				$extend = $this->_services[$extend];
			}

			if ($static) {
				$config['_static'] = true;
			}

			$name = $extend ?: 'Tipsy\Service';
			$config['_service'] = $service;

			$this->_services[$service] = [
				'reflection' => new \ReflectionClass($name),
				'config' => $config
			];

			return $this;

		} else {

			if (isset($this->_services[$service]['config']['_controller'])) {
				$this->_services[$service]['config'] = $this->_services[$service]['config']['_controller']->init(['tipsy' => $this]);
			}

			if (isset($this->_services[$service]['config']['_static']) && isset($this->_services[$service]['instance'])) {
				return $this->_services[$service]['instance'];
			}

			if ($this->_services[$service]['reflection']->hasMethod('__construct')) {
				$config = array_merge(
					is_array($this->_services[$service]['config']) ? $this->_services[$service]['config'] : [],
					['_tipsy' => $this],
					$args ? (array)$args : []
				);
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

			if (isset($this->_services[$service]['config']['_static'])) {
				$this->_services[$service]['instance'] = $instance;
			}

			return $instance;
		}
	}
	public function services(?string $service = null): bool|array {
		if ($service) {
			return isset($this->_services[$service]);
		}
		return $this->_services;
	}

	public function db(): Db {
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

	public function view(): View {
		if (!isset($this->_view)) {
			$config = $this->_config['view'] ?? [];
			$config['tipsy'] = $this;
			$this->_view = new View($config);
		}
		return $this->_view;
	}

	public function request(): Request {
		if (!isset($this->_request)) {
			$this->_request = new Request();
		}
		return $this->_request;
	}

	private function _serviceName(string $service, mixed $args = null): array {
		$extend = null;
		
		if (str_contains($service, '/')) {
			$serviceParts = explode('/', $service);
			if (count($serviceParts) > 2) {
				throw new Exception('Cannot extend more than one model.');
			} elseif (count($serviceParts) > 1) {
				$extend = array_shift($serviceParts);
			}
			$service = array_shift($serviceParts);

		} elseif ($args === null) {
			$extend = $service;
			$serviceParts = explode('\\', $service);
			$service = array_pop($serviceParts);
		}
		return [$service, $extend];
	}

	public function rootScope(): Scope {
		return $this->_rootScope;
	}

	public function route(): ?Route {
		return $this->_route;
	}

	public function url(): ?string {
		return $this->_url;
	}

	public function middleware(string|array $service, array $args = []): mixed {
		if (!is_string($service)) {
			$args = $service;
			$service = uniqid();
		}

		if (is_object($args) && !is_callable($args) && !$args instanceof Middleware) {
			throw new Exception('Middleware must be an instance of Tipsy\Middleware');
		}

		if (!isset($this->_services[$service])) {
			$this->service($service, $args, true);
		}
		if (isset($this->_middlewares[$service])) {
			return $this->service($service);
		}
		$middleware = [
			'service' => $service,
			'args' => $args,
			'started' => $this->_middlewareStart
		];
		$this->_middlewares[$service] = $middleware;
		if ($this->_middlewareStart) {
			Middleware::_start($middleware, $this);
		}
		return $this->service($service);
	}

	public function middlewares(): array {
		return $this->_middlewares;
	}

	public function factoryCount(): int {
		return $this->_factory?->count() ?? 0;
	}

	public function factory(mixed $a = null, mixed $b = null): mixed {
		if (!$this->_factory) {
			$this->_factory = new Factory($this);
		}
		return $this->_factory->objectMap($a, $b);
	}

	public function __call(string $method, array $args): mixed {
		return $this->router()->{$method}(...$args);
	}
}
