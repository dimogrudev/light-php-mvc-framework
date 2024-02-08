<?php

namespace Core;

abstract class Controller
{
	protected string $controllerName;

	protected ?string $layout	= null;
	protected bool $autoVersioning = false;

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

		if ($this->autoVersioning) {
			foreach (\Core\Application::$config['autoVersioning'] as $extension => $staticFiles) {
				$autoVersioning[$extension] = [];

				foreach ($staticFiles as $file) {
					$filePath = __DIR__ . '/../Public/' . $extension . '/' . $file;

					if (file_exists($filePath)) {
						$mtime = filemtime($filePath);
						
						$extPos = strpos($file, $extension);
						$autoVersioning[$extension][] = substr($file, 0, $extPos) . $mtime . '.' . $extension;
					}
				}
			}
		}

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
