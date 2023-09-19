<?php

require __DIR__ . '/../autoload.php';

$config = require __DIR__ . '/../config.php';
$app = new Core\Application($config);
$app->run();
