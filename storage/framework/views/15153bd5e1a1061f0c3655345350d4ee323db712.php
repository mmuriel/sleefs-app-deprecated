<header>
	<figure>
		<img src="<?php echo e($slot); ?>/imgs/sleefs-logo.png" style="width: 5rem;" />
	</figure>
	<nav>
		<ul>
			<li>
				<a href="<?php echo e(env("APP_URL")); ?>">POs Updates</a>
			</li>
			<li>
				<a href="<?php echo e(env("APP_URL")); ?>/pos">POs</a>
			</li>
			<li>
				<a href="<?php echo e(route('logout')); ?>" id="logout-link">
                                            Logout
				</a>
				<form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" style="display: none;">
				<?php echo e(csrf_field()); ?>

				</form>
			</li>
		</ul>
	</nav>
</header>
<script>
	
	$(document).ready(function(){

		$("#logout-link").on("click",function(e){
			e.preventDefault();
			document.getElementById('logout-form').submit();
		})

	});
</script>