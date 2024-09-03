<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="keywords" content="Hunt, family, recipes, reunion, cookbook" />
		<meta name="description" content="Hunt Recipes - a family reunion cookbook.">
		<meta name="author" content="Will Sabol">
		<meta property="locale" contnt="en-US">
		<link rel="author" href="http://www.willsabol.com/">
		
		<title><?=( isset($pageTitle) ? $pageTitle." - " : "" )."HuntRecipes"?></title>
		
		<!-- social open graph -->
		<meta property="fb:app_id" content="1722798604684723" />
		<meta property="og:type"          content="website" />
		<meta property="og:title"         content="<?=( isset($pageTitle) ? $pageTitle : "HuntRecipes" )?>" />
		<meta property="og:description"   content="Hunt Recipes - a family reunion cookbook." />
		<meta property="og:image"         content="<?=( isset($siteImage) ? $siteImage : "https://huntrecipes.willsabol.com/assets/images/ico/HR_250.png" ) ?>" />
		
		<link rel="apple-touch-icon" sizes="57x57" href="/assets/images/favicon/apple-icon-57x57.png">
		<link rel="apple-touch-icon" sizes="60x60" href="/assets/images/favicon/apple-icon-60x60.png">
		<link rel="apple-touch-icon" sizes="72x72" href="/assets/images/favicon/apple-icon-72x72.png">
		<link rel="apple-touch-icon" sizes="76x76" href="/assets/images/favicon/apple-icon-76x76.png">
		<link rel="apple-touch-icon" sizes="114x114" href="/assets/images/favicon/apple-icon-114x114.png">
		<link rel="apple-touch-icon" sizes="120x120" href="/assets/images/favicon/apple-icon-120x120.png">
		<link rel="apple-touch-icon" sizes="144x144" href="/assets/images/favicon/apple-icon-144x144.png">
		<link rel="apple-touch-icon" sizes="152x152" href="/assets/images/favicon/apple-icon-152x152.png">
		<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-icon-180x180.png">
		<link rel="icon" type="image/png" sizes="192x192"  href="/assets/images/favicon/android-icon-192x192.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="96x96" href="/assets/images/favicon/favicon-96x96.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon/favicon-16x16.png">
		<link rel="manifest" href="/assets/manifest.json">
		<meta name="msapplication-TileColor" content="#ffffff">
		<meta name="msapplication-TileImage" content="/assets/images/favicon/ms-icon-144x144.png">
		<meta name="theme-color" content="#ffffff">
		
		<!-- Bootstrap 3.3.6 -->
		<link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="/assets/css/style.css" />
		<link rel="stylesheet" href="/assets/css/icons.css" />
		<link href="https://fonts.googleapis.com/css?family=Raleway:400,300,500,600,700,800" rel="stylesheet">
		<script src="https://use.fontawesome.com/e808bf9397.js"></script>
		<link rel="shortcut icon" href="/assets/images/favicon/favicon.ico" />
		<link rel='stylesheet' href='/assets/font-awesome/css/font-awesome.min.css' type='text/css'>
		<!-- iCheck -->
		<link rel="stylesheet" href="/assets/iCheck/flat/_all.css">
		<link href='/assets/css/style.recipes_common.css' rel='stylesheet' type='text/css'>
		
		<script type="text/javascript" src='/assets/jquery-3.1.1/jquery.min.js'></script>
		<!-- Bootstrap 3.3.6 -->
		<script src="/assets/bootstrap/js/bootstrap.min.js"></script>
		<!-- DataTables -->
		<script src="/assets/datatables/jquery.dataTables.min.js"></script>
		<script src="/assets/datatables/dataTables.bootstrap.min.js"></script>
		<!-- iCheck -->
    <script type="text/javascript" src="/assets/iCheck/icheck.min.js"></script>
		<script type="text/javascript" src='/API/v0/fraction/fraction.js'></script>
		<script type="text/javascript" src='/assets/js/_js_common.js'></script>
		<script type="text/javascript" src="/assets/js/recipes_init.js"></script>
		
		<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

			ga('create', 'UA-72183767-7', 'auto');
			ga('send', 'pageview');

		</script>

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
		<![endif]-->
	</head>
	<body class="<?=@$bodyClass?>">
		<!--preloader-->
		<div class="preloader">
			<div class="spinner"></div>
		</div>
		<!--//preloader-->
		<?php
		require_once('_main_header.php');
		?>
		<!--main-->
		<main class="main" role="main">
