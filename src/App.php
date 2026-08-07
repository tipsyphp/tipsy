<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Main class
 */
class App {
	/** @var array<string, Controller> */
	private array $_controllers = [];
	private array $_config = [];
	/** @var array<string, array{reflection: \ReflectionClass, config: array}> */
	private array $_services = [];
	private array $_middlewares = [];
	private bool $_middlewareStart = false;
	private ?Route $_route = null;
	private ?string $_url = null;
	private ?Router $_router = null;
	private ?Db $_db = null;
	private ?View $_view = null;
	private ?Request $_request = null;
	private ?Factory $_factory = null;
	private Scope $_rootScope;

	public function __construct() {
		$this->_rootScope = new Scope();
	}

	public function run(?string $url = null): void {
		$this->start($url);
	}

	public function start(?string $url = null): void {
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

	public function router(): Router {
		if ($this->_router === null) {
			$this->_router = new Router(['tipsy' => $this]);
		}
		return $this->_router;
	}

	public function controller(?string $controller = null, ?callable $closure = null): static|Controller|null {
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

	public function config(string|array|null $args = null, int|bool $recursive = 0): static|array {
		$recursive = (int) $recursive;
		$merge = match ($recursive) {
			2 => 'array_merge',
			1 => 'array_merge_recursive',
			default => 'array_replace_recursive',
		};

		if (is_string($args)) {
			$iterator = new \GlobIterator($args);

			foreach ($iterator as $file) {
				if ($file->getExtension() === 'ini') {
					$config = parse_ini_file($file->getPathname(), true);

				} elseif ($file->getExtension() === 'yaml' || $file->getExtension() === 'yml') {
					if (function_exists('yaml_parse_file')) {
						$config = yaml_parse_file($file->getPathname());
					} elseif (class_exists('\\Symfony\\Component\\Yaml\\Parser')) {
						$yaml = new \Symfony\Component\Yaml\Parser();
						$config = $yaml->parse(file_get_contents($file->getPathname()));
					} else {
						throw new Exception('Could not find yaml parser. Try "composer require symfony/yaml"');
					}

				} else {
					throw new Exception('Unknown file type: "' . $file->getFileName() . '"');
				}

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

	public function service(string $service, mixed $args = null, bool $static = false): object {
		[$service, $extend] = $this->_serviceName($service, $args);

		if (!isset($this->_services[$service]) || !$this->_services[$service]) {

			if (is_object($args) && !is_callable($args) && !$args instanceof Service) {
				throw new Exception('Service must be an instance of Tipsy\\Service');

			} elseif ($service && is_callable($args)) {
				$config = ['_controller' => new Service([
					'closure' => $args,
					'tipsy' => $this
				])];

			} elseif ($service && (class_exists($service) || (!is_array($args) && (is_object($args) || (is_string($args) && class_exists($args)))))) {
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

			$name = $extend ?: 'Tipsy\\Service';

			$config['_service'] = $service;

			$this->_services[$service] = [
				'reflection' => new \ReflectionClass($name),
				'config' => $config ?? []
			];

			return $this;

		} else {

			if (isset($this->_services[$service]['config']['_controller']) && $this->_services[$service]['config']['_controller']) {
				$this->_services[$service]['config'] = $this->_services[$service]['config']['_controller']->init(['tipsy' => $this]);
			}

			if (!empty($this->_services[$service]['config']['_static']) && isset($this->_services[$service]['instance'])) {
				return $this->_services[$service]['instance'];
			}

			if ($this->_services[$service]['reflection']->hasMethod('__construct')) {
				$config = array_merge(
					is_array($this->_services[$service]['config']) ? $this->_services[$service]['config'] : [],
					['_tipsy' => $this],
					$args ? (is_array($args) ? $args : []) : []
				);
				$instance = $this->_services[$service]['reflection']->newInstance($config);

			} else {
				$instance = $this->_services[$service]['reflection']->newInstance();
			}

			if (method_exists($instance, 'tipsy')) {
				$instance->tipsy($this);
			}

			if (is_array($this->_services[$service]['config'])) {
				foreach ($this->_services[$service]['config'] as $name => $config) {
					// Skip internal service metadata (_service, _static, _tipsy, _controller, …)
					if (is_string($name) && str_starts_with($name, '_')) {
						continue;
					}
					if (is_callable($config) && method_exists($instance, 'addMethod')) {
						$instance->addMethod($name, $config);
					} else {
						$instance->{$name} = $config;
					}
					$instance->tipsy($this);
				}
			}

			if (!empty($this->_services[$service]['config']['_static'])) {
				$this->_services[$service]['instance'] = $instance;
			}

			return $instance;
		}
	}

	public function services(?string $service = null): array|bool {
		if ($service) {
			return isset($this->_services[$service]) && $this->_services[$service] ? true : false;
		}
		return $this->_services;
	}

	public function db(): Db {
		if ($this->_db === null) {
			if ($this->services('Db')) {
				$this->_db = $this->service('Db');
				$this->_db->connect($this->_config['db']);
			} else {
				$this->_db = new Db($this->_config['db'] ?? null);
			}

			// kill the db config in case something gets outputted
			unset($this->_config['db']);
		}

		return $this->_db;
	}

	public function view(): View {
		if ($this->_view === null) {
			$config = $this->_config['view'] ?? [];
			$config['tipsy'] = $this;
			$this->_view = new View($config);
		}
		return $this->_view;
	}

	public function request(): Request {
		if ($this->_request === null) {
			$this->_request = new Request();
		}
		return $this->_request;
	}

	private function _serviceName(string $service, mixed $args = null): array {
		$extend = null;

		if (str_contains($service, '/')) {
			$parts = explode('/', $service);
			if (count($parts) > 2) {
				throw new Exception('Cant extend more than one model.');
			} elseif (count($parts) > 1) {
				$extend = array_shift($parts);
			}
			$service = array_shift($parts);

		} elseif ($args === null) {
			$extend = $service;
			$parts = explode('\\', $service);
			$service = array_pop($parts);
		}
		return [$service, $extend];
	}

	public function rootScope(): Scope {
		return $this->_rootScope;
	}

	public function route(?Route $route = null): ?Route {
		if ($route !== null) {
			$this->_route = $route;
		}
		return $this->_route;
	}

	public function url(): ?string {
		return $this->_url;
	}

	public function middleware(string|array|callable|object $service, mixed $args = []): object {
		if (!is_string($service)) {
			$args = $service;
			$service = uniqid();
		}

		if (is_object($args) && !is_callable($args) && !$args instanceof Middleware) {
			throw new Exception('Middleware must be an instance of Tipsy\\Middleware');
		}

		if (!isset($this->_services[$service]) || !$this->_services[$service]) {
			$this->service($service, $args, true);
		}
		if (isset($this->_middlewares[$service]) && $this->_middlewares[$service]) {
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
		if ($this->_factory === null) {
			$this->_factory = new Factory($this);
		}
		return $this->_factory->objectMap($a, $b);
	}

	public function __call(string $method, array $args): mixed {
		return $this->router()->$method(...$args);
	}
}
