<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<title><?= $title ? $title . ' — ' : '' ?>Light PHP MVC Framework</title>
		
		<link rel="stylesheet" href="/vendor/bootstrap/bootstrap-4.6.2.min.css">
		
		<?php foreach ($autoVersioning['css'] as $css) : ?>
			<link rel="stylesheet" href="/css/<?= $css; ?>">
		<?php endforeach; ?>
	</head>
	<body>
		<div class="container">
			<?= $content ?>
		</div>
		
		<script src="/vendor/jquery/jquery-3.5.1.min.js"></script>
		<script src="/vendor/popper/popper-1.16.1.min.js"></script>
		<script src="/vendor/inputmask/inputmask-5.0.9.min.js"></script>
		<script src="/vendor/bootstrap/bootstrap-4.6.2.min.js"></script>
		
		<?php foreach ($autoVersioning['js'] as $js) : ?>
			<script src="/js/<?= $js; ?>"></script>
		<?php endforeach; ?>
	</body>
</html>