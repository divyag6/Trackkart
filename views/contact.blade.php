@extends('app')

@section('content')
<div class="container">
	<div class="row">
		<div class="col-md-10 col-md-offset-1"style="padding-top: 15px;">
			
			<p style="text-align: center;">Just send us a message in the form below with any question you may have or email us at <u>support@trackkart.com</u> .We will be happy to hear from you.</p>
			<br>
			<div class="panel panel-info">
				<div class="panel-heading">Contact Us</div>
				<div class="panel-body">
					@if (count($errors) > 0)
						<div class="alert alert-danger alert-important">
							<strong>Whoops!</strong> There were some problems with your input.<br>
							<ul>
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif

					<form class="form-horizontal" role="form" method="POST" action="/contact">
						<input type="hidden" name="_token" value="{{ csrf_token() }}">

						
						<div class="col-md-6">
							
							<div class="form-group">
								
	    						<label for="name" class="control-label">Your Name</label>
	    						<input type="text" class="form-control" name="name">
	   						</div>
	   						
	   						<div class="form-group">
	   						 	<label for="email" class="control-label">Email address</label>
	    						<input type="email" class="form-control" name="email" placeholder="Enter email">
	   						</div>
					 	 	
					 	 	<div class="form-group">
								<label class="control-label">Subject</label>
						 		<input type="text" class="form-control" name="subject">
	   						</div>
						</div>
				 	
					 	<div class="col-md-5 col-md-offset-1">
							<div class="form-group">
								<label class="control-label">Message</label>
								<textarea class="form-control input-lg" name="message"></textarea>
							</div>
					 	 	
							<div class="form-group">
								<div class="pull-right">
									<button type="submit" class="btn btn-md btn-info" style="margin-right: 15px;">
										Send
									</button>
								</div>
							</div>
						</div>
						
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
