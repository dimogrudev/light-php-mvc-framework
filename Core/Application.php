<?php

namespace Core;

final class Application
{
	const CLASS_FORMAT				= 'Framework\\Controllers\\%sController';
	const ACTION_FORMAT				= 'action%s';

	/** @var array $config App configuration */
	private static array $config	= [];

	/** @var object $controller Controller object */
	private object $controller;
	/** @var string $action Action method name */
	private string $action;
	/** @var array $params Method params */
	private array $params			= [];

	public function __construct(array $config)
	{
		self::$config = $config;
	}

	private function setController(string $controllerName): bool
	{
		if ($controllerName) {
			$className = sprintf(self::CLASS_FORMAT, $controllerName);

			if (class_exists($className)) {
				$this->controller = new $className();

				if ($className == get_class($this->controller)) {
					return true;
				} else {
					unset($this->controller);
				}
			}
		}

		return false;
	}

	private function routing(): bool
	{
		if (preg_match_all('/\/([\w\-]+)/', $_SERVER['REQUEST_URI'], $urlParts) === false) {
			return false;
		}

		foreach ($urlParts[1] as $urlPart) {
			if (!isset($this->action)) {
				$urlPart = str_replace('-', '', ucwords($urlPart, '-'));

				if (!isset($this->controller)) {
					if ($this->setController($urlPart)) {
						continue;
					}
				}

				if ($urlPart) {
					$this->action = sprintf(self::ACTION_FORMAT, $urlPart);
				}
			} else {
				if ($urlPart || is_numeric($urlPart)) {
					$this->params[] = $urlPart;
				}
			}
		}

		if (!isset($this->controller)) {
			$defaultController = self::getConfigParam('defaultController');

			if (!$this->setController($defaultController)) {
				throw new \Exception('Default controller does not exist');
			}
		}

		if ($this->controller instanceof \Core\Controller) {
			if (!isset($this->action)) {
				if ($this->controller->defaultAction) {
					$this->action = sprintf(self::ACTION_FORMAT, $this->controller->defaultAction);
				}
			}

			return true;
		}

		return false;
	}

	public function run(): void
	{
		if (!$this->routing()) {
			self::error(404);
		}

		if (isset($this->controller) && isset($this->action)) {
			$func = [$this->controller, $this->action];
			$args = [];

			if (is_callable($func)) {
				$methodInfo = new \ReflectionMethod($this->controller, $this->action);

				if ($this->action == $methodInfo->getName()) {
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

							if (
								!($paramType instanceof \ReflectionNamedType)
								|| !settype($this->params[$idx], $paramType->getName())
							) {
								$paramsValid = false;
								break;
							}

							$args[] = $this->params[$idx];
						}

						if ($paramsValid) {
							$result = call_user_func_array($func, $args);

							if (is_string($result)) {
								self::setServerTimingHeaders();
								echo $result;
							}

							return;
						}
					}
				}
			}
		}

		self::error(404);
	}

	private static function setServerTimingHeaders(): void
	{
		$total = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 1);

		$db = round((\Core\Modules\Database::getInstance())->getExecutionTime() * 1000, 1);
		$app = $total - $db;

		header('Server-Timing: db;dur=' . $db, false);
		header('Server-Timing: app;dur=' . $app, false);
		header('Server-Timing: total;dur=' . $total, false);
	}

	public static function getConfigParam(string $paramName)
	{
		if (isset(self::$config[$paramName])) {
			return self::$config[$paramName];
		}

		throw new \Exception('Configuration parameter \'' . $paramName . '\' is not set in the file');
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
