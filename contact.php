<?
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 


/** built site **/
$bodyClass = "";
require_once('_head.php');
?>
<!--wrap-->
<div class="wrap clearfix">
	<!--row-->
	<div class="row">
		<!--content-->
		<section class="content center full-width">
			<div class="modal container">
				<form method="post" action="/API/contact/contact_callback.php" name="contactform" id="contactform">
					<h3>Contact us</h3>
					<div id="message" class="alert alert-danger"></div>
					<div class="f-row">
						<input type="text" class="form-control" placeholder="Your name" id="name" >
					</div>
					<div class="f-row">
						<input type="email" class="form-control" placeholder="Your email" id="email" >
					</div>
					<div class="f-row">
						<input type="text" class="form-control" placeholder="Your phone number" id="phone" >
					</div>
					<div class="f-row">
						<textarea class="form-control" placeholder="Your message" id="comments"></textarea>
					</div>
					<div class="f-row bwrap">
						<input type="submit" value="Send message" >
					</div>
				</form>
			</div>
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
	


