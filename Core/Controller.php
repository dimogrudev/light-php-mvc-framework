<?php

namespace Core;

abstract class Controller
{
	protected string $controllerName;

	protected ?string $layout		= null;
	protected bool $autoVersioning	= false;

	public $defaultAction			= 'Index';

	public function __construct()
	{
		$this->controllerName = substr(
			$className = get_class($this),
			strrpos($className, '\\') + 1,
			-strlen('Controller')
		);
	}

	/**
	 * Renders a view and applies layout if available
	 */
	protected function render(string $viewName, array $data = []): string
	{
		$content = $this->renderPartial($viewName, $data);
		return $this->renderContent($content, $data);
	}

	/**
	 * Renders a view without applying layout
	 */
	protected function renderPartial(string $viewName, array $data = []): string
	{
		extract($data);

		$viewPath = __DIR__ . '/../Framework/Views/' . $viewName . '.php';

		if (!file_exists($viewPath)) {
			\Core\Application::error(500);
		}
		
		ob_start();
		require $viewPath;
		return ob_get_clean();
	}

	/**
	 * Renders a static string by applying a layout
	 */
	protected function renderContent(string $content, array $data = []): string
	{
		extract($data);

		if ($this->autoVersioning) {
			$autoVersioning = [];

			foreach (\Core\Application::getConfigParam('autoVersioning') as $extension => $staticFiles) {
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

		if ($this->layout) {
			ob_start();
			require __DIR__ . '/../Layouts/' . $this->layout . '.php';
			return ob_get_clean();
		}

		return $content;
	}
}
