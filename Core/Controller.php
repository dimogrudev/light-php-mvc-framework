<?php

namespace Core;

abstract class Controller
{
	protected string $controllerName;
	protected ?string $layout	= null;

	public $defaultAction		= 'Index';

	public function __construct()
	{
		$this->controllerName = substr(
			$className = get_class($this),
			strrpos($className, '\\') + 1,
			-strlen('Controller')
		);
	}

	protected function render(string $viewName, array $data = []): string
	{
		extract($data);
		$viewPath = __DIR__ . '/../Framework/Views/' . $viewName . '.php';

		ob_start();

		if (!is_null($this->layout)) {
			require $viewPath;
			$content = ob_get_clean();

			ob_start();
			require __DIR__ . '/../Layouts/' . $this->layout . '.php';

			unset($content);
		} else {
			require $viewPath;
		}

		return ob_get_clean();
	}
}
