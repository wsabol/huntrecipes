	</main>
	<!--//main-->
	
	<!--footer-->
	<footer class="foot" role="contentinfo">
		<div class="wrap clearfix">
			<div class="row">
				
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
								<li><a href="/login.php" title="Login">Login</a></li>
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
</body>
</html>