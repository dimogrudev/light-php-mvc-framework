<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<title><?= $title ? $title . ' — ' : '' ?>Light PHP MVC Framework</title>
		
		<?php foreach ($autoVersioning['css'] as $css) : ?>
			<link rel="stylesheet" href="/css/<?= $css; ?>">
		<?php endforeach; ?>
	</head>
	<body>
		<div class="container">
			<?= $content ?>
		</div>
		
		<?php foreach ($autoVersioning['js'] as $js) : ?>
			<script src="/js/<?= $js; ?>"></script>
		<?php endforeach; ?>
	</body>
</html>