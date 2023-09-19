<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<title><?= $title ? $title . ' — ' : '' ?>Light PHP MVC Framework</title>
		
		<link rel="stylesheet" href="/css/bootstrap-4.6.2.min.css">
		<link rel="stylesheet" href="/css/custom.css">
	</head>
	<body>
		<div class="container">
			<?= $content ?>
		</div>

		<script src="/js/jquery-3.5.1.slim.min.js"></script>
		<script src="/js/popper-1.16.1.min.js"></script>
		<script src="/js/bootstrap-4.6.2.min.js"></script>
		<script src="/js/custom.js"></script>
	</body>
</html>