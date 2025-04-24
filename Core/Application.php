<?php

namespace Core;

use ReflectionClass;
use ReflectionMethod;

final class Application
{
	const CONTROLLERS_DIR				= 'Framework/Controllers/Instances';

	const CLASS_FORMAT					= '%sController';
	const ACTION_FORMAT					= 'action%s';

	/** @var array $config App configuration */
	private static array $config		= [];

	/** @var string $subdir Controller subdirectory */
	private string $subdir				= '';
	/** @var ReflectionClass $controller Controller class information */
	private ReflectionClass $controller;
	/** @var ReflectionMethod $action Action method information */
	private ReflectionMethod $action;
	/** @var array $args Action method arguments */
	private array $args					= [];

	public function __construct(array $config)
	{
		self::$config = $config;
	}

	private function setSubdir(string $dirName): bool
	{
		if (file_exists(__DIR__ . '/../' . self::CONTROLLERS_DIR . '/' . $this->subdir . $dirName)) {
			$this->subdir .= $dirName . '/';
			return true;
		}

		return false;
	}

	private function setController(string $controllerName): bool
	{
		$className = str_replace('/', '\\', self::CONTROLLERS_DIR . '/' . $this->subdir)
			. sprintf(self::CLASS_FORMAT, $controllerName);

		if (class_exists($className)) {
			$classInfo = new ReflectionClass($className);

			if (
				$classInfo->getName() == $className
				&& $classInfo->isSubclassOf(\Core\Controller::class)
			) {
				$this->controller = $classInfo;
				return true;
			}
		}

		return false;
	}

	private function setAction(string $actionName): bool
	{
		$methodName = sprintf(self::ACTION_FORMAT, $actionName);

		if ($this->controller->hasMethod($methodName)) {
			$methodInfo = $this->controller->getMethod($methodName);

			if ($methodInfo->getName() == $methodName) {
				$this->action = $methodInfo;
				return true;
			}
		}

		return false;
	}

	private function routing(): bool
	{
		if (preg_match_all('/\/([\w\-]+)/', $_SERVER['REQUEST_URI'], $urlParts) === false) {
			return false;
		}

		$actionName = null;

		foreach ($urlParts[1] as $urlPart) {
			if (!$actionName) {
				$urlPart = str_replace('-', '', ucwords($urlPart, '-'));

				if ($urlPart) {
					if (!isset($this->controller)) {
						if ($this->setSubdir($urlPart)) {
							continue;
						}
						if ($this->setController($urlPart)) {
							continue;
						}
					}

					$actionName = $urlPart;
					continue;
				} else {
					return false;
				}
			}

			if ($urlPart || is_numeric($urlPart)) {
				$this->args[] = $urlPart;
			}
		}

		if (!isset($this->controller)) {
			if ($this->subdir) {
				return false;
			}

			$defaultController = self::getConfigParam('defaultController');

			if (!$this->setController($defaultController)) {
				throw new \Exception('Default controller does not exist');
			}
		}

		if (!$actionName) {
			$props = $this->controller->getDefaultProperties();

			if (isset($props['defaultAction']) && $props['defaultAction']) {
				$actionName = (string)$props['defaultAction'];
			}
		}

		return $actionName && $this->setAction($actionName);
	}

	public function run(): void
	{
		if (!$this->routing()) {
			self::error(404);
		}

		if ($this->action->getNumberOfParameters() >= count($this->args)) {
			$methodArgs = [];

			foreach ($this->action->getParameters() as $idx => $methodParam) {
				if (!isset($this->args[$idx])) {
					if (!$methodParam->isDefaultValueAvailable()) {
						$methodArgs = null;
						break;
					}

					continue;
				}

				$paramType = $methodParam->getType();

				if (
					!($paramType instanceof \ReflectionNamedType)
					|| !settype($this->args[$idx], $paramType->getName())
				) {
					$methodArgs = null;
					break;
				}

				$methodArgs[] = $this->args[$idx];
			}

			if (is_array($methodArgs)) {
				$controllerInstance = $this->controller->newInstance();
				$result = $this->action->invokeArgs($controllerInstance, $methodArgs);

				if (is_string($result)) {
					self::setServerTimingHeaders();
					echo $result;
				}

				return;
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
