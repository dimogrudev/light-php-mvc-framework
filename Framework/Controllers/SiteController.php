<?php

namespace Framework\Controllers;

class SiteController extends \Core\Controller
{
	public $defaultAction	= 'Index';

	public function actionIndex(): string
	{
		$this->layout = 'Site';
		return $this->render('Site/Index', [
			'title' => 'Index'
		]);
	}
}
