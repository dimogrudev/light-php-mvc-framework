<?php

namespace Core;

class Application
{
	private const CLASS_FORMAT	= 'Framework\\Controllers\\%sController';
	private const ACTION_FORMAT	= 'action%s';

	public static array $config = [];

	private ?object $controller	= null;
	private ?string $action		= null;
	private array $params		= [];

	public function __construct(array $config)
	{
		self::$config = $config;
	}

	private function routing(): bool
	{
		$urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		$replacementsCount = 0;
		$filtered = preg_replace('/[^a-z0-9\/\-]/', '', strtolower($urlPath), -1, $replacementsCount);

		if ($replacementsCount > 0) {
			return false;
		}

		$parts = explode('/', substr($filtered, 1));
		for ($i = 0; $i < count($parts); $i++) {
			if ($this->controller === null || $this->action === null) {
				$ccVal = str_replace('-', '', ucwords($parts[$i], '-'));

				if ($this->controller === null) {
					$className = sprintf(self::CLASS_FORMAT, $ccVal);

					if (!$ccVal || !class_exists($className)) {
						$className = sprintf(self::CLASS_FORMAT, self::$config['defaultController']);
						$i--;
					}

					$this->controller = new $className();
					if ($className != get_class($this->controller)) {
						return false;
					}
				} else {
					if ($ccVal) {
						$this->action = sprintf(self::ACTION_FORMAT, $ccVal);
					}
				}
			} else {
				if ($parts[$i] || is_numeric($parts[$i])) {
					$this->params[] = $parts[$i];
				}
			}
		}

		if ($this->controller && !$this->action) {
			if ($this->controller->defaultAction) {
				$this->action = sprintf(self::ACTION_FORMAT, $this->controller->defaultAction);
			}
		}

		return true;
	}

	public function run(): void
	{
		if (!$this->routing()) {
			self::error(404);
		}

		if ($this->controller && $this->action) {
			$func = [$this->controller, $this->action];
			$args = [];

			if (is_callable($func)) {
				$methodInfo = new \ReflectionMethod($this->controller, $this->action);

				if ($this->action === $methodInfo->getName()) {
					if ($methodInfo->getNumberOfParameters() >= count($this->params)) {
						$paramsValid = true;

						foreach ($methodInfo->getParameters() as $idx => $methodParam) {
							if (!isset($this->params[$idx])) {
								if (!$methodParam->isDefaultValueAvailable()) {
									$paramsValid = false;
									break;
								}

								continue;
							}

							$paramType = $methodParam->getType();
							assert($paramType instanceof \ReflectionNamedType);

							if (!settype($this->params[$idx], $paramType->getName())) {
								$paramsValid = false;
								break;
							}
							
							$args[] = $this->params[$idx];
						}

						if ($paramsValid) {
							$result = call_user_func_array($func, $args) ?? '';

							self::setServerTimingHeaders();
							echo $result;
							
							return;
						}
					}
				}
			}
		}

		self::error(404);
	}
	
	public static function setServerTimingHeaders(): void
	{
		$total = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 1);

		$db = round((\Core\Modules\Database::getInstance())->getExecutionTime() * 1000, 1);
		$app = $total - $db;

		header('Server-Timing: db;dur=' . $db, false);
		header('Server-Timing: app;dur=' . $app, false);
		header('Server-Timing: total;dur=' . $total, false);
	}

	public static function isLocalhost(array $whitelist = ['127.0.0.1', '::1']): bool
	{
		return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
	}

	/**
	 * @param string $location URL to redirect a page to
	 * @param int $code HTTP status code
	 * @return never
	 */
	public static function redirect(string $location, int $code = 303)
	{
		header('Location: ' . $location, true, $code);
		exit;
	}

	/**
	 * @param int $code HTTP status code
	 * @return never
	 */
	public static function error(int $code = 404)
	{
		http_response_code($code);
		exit;
	}
}
