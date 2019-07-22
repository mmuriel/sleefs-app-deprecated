<header>
	<figure>
		<img src="{{ $slot }}/imgs/sleefs-logo.png" style="width: 5rem;" />
	</figure>
	<nav>
		<ul>
			<li>
				<a href="{{ env("APP_URL") }}">POs Updates</a>
			</li>
			<li>
				<a href="{{ env("APP_URL") }}/pos">POs</a>
			</li>
			<li>
				<a href="{{ env("APP_URL") }}/inventoryreport">Inventory Reports</a>
			</li>
			<li>
				<a href="{{ route('logout') }}" id="logout-link">
                                            Logout
				</a>
				<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
				{{ csrf_field() }}
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