<div class="container-fluid" >
<div class="row">
<nav class="navbar navbar-custom" >

	
		<div class="col-md-4 col-xs-5" >
			
				<ul class="nav navbar-nav navbar-left nav-pills" style="padding-top:80px;">
	     			<li><a href="/">Home</a></li>
	        		<li><a href="/about">About</a></li>
	        		<li><a href="/contact">Contact</a></li>
	    		</ul>
	    </div>	
	    <div class="col-md-4 col-xs-3" >
	   
	    		<img class="img-responsive" style="margin: 0 auto;" alt="trackkart.com" src='http://localhost/trackkart.png'>
	    </div>
	    <div class="col-md-4 col-xs-4" >
				<ul class="nav navbar-nav navbar-right nav-pills" style="padding-top:80px;padding-right:40px">
			    	@if($user)
			        	<li>
			        		<div class="dropdown">
	  							<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
	    							Welcome {{$user->name}}
	    							<span class="caret"></span>
	  							</button>
	  							<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
	  								
	    							<li role="presentation"><a role="menuitem" tabindex="-1" href="/account">All Orders</a></li>
	    							<li role="presentation"><a role="menuitem" tabindex="-1" href="/account/create">Change Password</a></li>
	    							<li role="presentation"><a role="menuitem" tabindex="-1" href="/vendorconfig">Vendor Configrations</a></li>
	    							<li role="presentation"><a role="menuitem" tabindex="-1" href="/auth/logout">Logout</a></li>
	  							</ul>
							</div>
		        		</li>
		        	@else
		        		<li><a href="/auth/login">Login</a></li>
						<li><a href="/auth/register">Register</a></li>
					@endif
			        		
				</ul>
			
		
			</div>
</nav>	
</div>
</div>

