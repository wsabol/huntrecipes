<?
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

/** built site **/
$bodyClass = "errorPage";
require_once('_head.php');
?>
<!--wrap-->
<div class="wrap clearfix">
	<!--row-->
	<div class="row">
		<!--content-->
		<section class="content full-width">
			<!--row-->
			<div class="row">
				<div class="one-third">
					<div class="error-container">
						<span class="error_type">404</span>
						<span class="error_text">Page not found</span>
					</div>
				</div>

				<div class="two-third">
					<div class="container">
						<p>The page you’ve requested could not be found or it was already removed from the database. </p>
						<p>If you believe that this is an error, please kindly <a href="#">contact us</a>. Thank you!</p>
						<p>You can go <a href="/">back home</a> or try using the search. </p>
					</div>
				</div>
			</div>
			<!--//row-->
		</section>
		<!--//content-->
		
	</div>
	<!--//row-->
</div>
<!--//wrap-->
<?php
require_once('_footer.php');
$App = "";
?>


