<?php

return [
	'defaultController' => 'Site',
	
	'autoVersioning'	=> [
		'css'				=> [
			'bootstrap-4.6.2.min.css',
			'custom.css'
		],
		'js'				=> [
			'jquery-3.5.1.min.js',
			'popper-1.16.1.min.js',
			'bootstrap-4.6.2.min.js',
			'custom.js'
		]
	],
	'pdo'					=> [
		'host'					=> '127.0.0.1',
		'dbName'				=> 'framework',

		'user'					=> 'root',
		'pass'					=> ''
	]
];
