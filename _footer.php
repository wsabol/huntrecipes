	</main>
	<!--//main-->
	<?php if ( @$_SESSION['Login']['id'] == 0 ) { ?>
		<!--call to action-->
		<section class="cta">
			<div class="wrap clearfix">
				<a href="/register.php" class="button big white right">Sign up</a>
				<h2>Love this food? Get more when you sign up for an account.</h2>
			</div>
		</section>
		<!--//call to action-->
	<?php } ?>
	
	<!--footer-->
	<footer class="foot" role="contentinfo">
		<div class="wrap clearfix">
			<div class="row">
				<article style="font-style: italic;" class="three-fourth">
					<h5>Dear Hunt Family,</h5>
					<p>We know how to have a fun! Dad and Dotty sparked these gourmet get-togethers with the first reunion in 1983. Through their loving example, these 14 summers
						have created memories that are with us everyday. You have shared many of these in this book thus giving them a life of their own. But this is only a beginning-over 14 years our clan
						is stronger by 14 people. Here's to many more great times together!
					<p>Joan and Stuart<br>June 30, 1997</p>
				</article>
				<article class="one-fourth">
					<h5>Stay in Touch</h5>
					<div class="row">
						<div class="full-width">
							<ul class="social">
								<li><a href="https://www.facebook.com/jo.sabol" title="facebook"><i class="fa fa-fw fa-facebook"></i></a></li>
								<li><a href="https://www.linkedin.com/in/joan-hunt-sabol-03258096/" title="linkedin"><i class="fa fa-fw fa-linkedin"></i></a></li>
								<li><a href="https://www.pinterest.com/josabol/" title="pinterest"><i class="fa fa-fw fa-pinterest-p"></i></a></li>
								<li><a href="/contact.php" title="Contact"><i class="fa fa-fw fa-envelope"></i></a></li>
							</ul>
						</div>
					</div>
				</article>
				
				<div class="bottom">
					<p class="copy">Copyright <?=( date('Y') > 2017 ? "2017 - ": "" ).date('Y')?> <a href="http://www.willsabol.com/" target="_blank" >Will Sabol</a>. All rights reserved</p>
					
					<nav class="foot-nav">
						<ul>
							<li><a href="/" title="Home">Home</a></li>
							<li><a href="/browse.php" title="Recipes">Recipes</a></li>
							<li><a href="/contact.php" title="Contact">Contact</a></li>
							<?php if ( @$_SESSION['Login']['id']*1 > 0 ) { ?>
								<li><a href="/profile.php" title="Account">Account</a></li>
							<?php } else { ?>
								<li><a href="/login/" title="Login">Login</a></li>
							<?php } ?>
						</ul>
					</nav>
				</div>
			</div>
		</div>
	</footer>
	<!--//footer-->

	<!--<script src="/assets/js/jquery.uniform.min.js"></script>-->
	<script src="/assets/js/jquery.slicknav.min.js"></script>
	<script src="/assets/js/scripts.js"></script>
	<script src="/assets/js/recipes.js"></script>
<!--	<script src="/ServiceWorker.js"></script>-->
</body>
</html>
