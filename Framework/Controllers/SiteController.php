<?php

namespace Framework\Controllers;

use Framework\Models\Customer;

class SiteController extends \Core\Controller
{
	public $defaultAction	= 'Index';

	public function actionIndex(): string
	{
		$this->layout = 'Site';
		$this->autoVersioning = true;

		return $this->render('Site/Index', [
			'title'		=> 'Index',
			'customers'	=> Customer::findAll()
		]);
	}
}
