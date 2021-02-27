@extends('app')
@section('sidebar') @include('includes.sidebar') @stop
@section('content')
<div class="container-fluid">
	<div class="row">
		<div class="col-md-8 col-xs-8 col-xs-offset-2 col-md-offset-1">
			<div class="panel panel-info">
				<div class="panel-heading">Reset Password</div>
				<div class="panel-body">
					@if (count($errors) > 0)
						<div class="alert alert-danger">
							<strong>Whoops!</strong> There were some problems with your input.<br><br>
							<ul>
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif

					<form class="form-horizontal" role="form" method="POST" action="/account">
						<input type="hidden" name="_token" value="{{ csrf_token() }}">
						

						<div class="form-group">
							<label class="col-md-4 col-xs-12 control-label">E-Mail Address</label>
							<div class="col-md-6 col-xs-12">
								<input type="email" class="form-control" name="email" value={{ $user->email }}>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-md-4 col-xs-12 control-label">Old Password</label>
							<div class="col-md-6 col-xs-12">
								<input type="password" class="form-control" name="old_password">
							</div>
						</div>
						

						<div class="form-group">
							<label class="col-md-4 col-xs-12 control-label">New Password</label>
							<div class="col-md-6 col-xs-12">
								<input type="password" class="form-control" name="password">
							</div>
						</div>

						<div class="form-group">
							<label class="col-md-4 col-xs-12 control-label">Confirm Password</label>
							<div class="col-md-6 col-xs-12">
								<input type="password" class="form-control" name="password_confirmation">
							</div>
						</div>

						<div class="form-group">
							<div class="col-md-6 col-xs-4 col-xs-offset-1 col-md-offset-4">
								<button type="submit" class="btn btn-info">
									Reset Password
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

