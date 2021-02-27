@extends('app')

@section('sidebar')
		@include('includes.sidebar')
@stop

@section('content')
	
		<div class="col-md-8 col-md-offset-1">	
			<div class="panel panel-info">
				<div class="panel-heading">Edit an existing configuration.</div>
				<div class="panel-body">
					
					@include('errors.list')
				
					{!!Form::model($config,['method'=>'PATCH','url'=>'vendorconfig/'.$config->id,'class'=>'form-horizontal'])!!}
					<div class="form-group">
						{!!Form::label('name','Vendor Name:',['class'=>'col-md-4 control-label'])!!}
						<div class="col-md-6">
							{!!Form::select('vendor_id',$vendor,null,['class'=>'form-control'])!!}
						</div>
					</div>
					<div class="form-group">	
						{!!Form::label('email','Email Id:',['class'=>'col-md-4 control-label'])!!}
						<div class="col-md-6">
							{!!Form::text('email',null,['class'=>'form-control'])!!}
						</div>
					</div>
					<div class="form-group">
						{!!Form::label('password','Old Password:',['class'=>'col-md-4 control-label'])!!}
						<div class="col-md-6">
							{!!Form::password('oldpassword',['class'=>'form-control'])!!}
						</div>	
					</div>
					<div class="form-group">
						{!!Form::label('password','New Password:',['class'=>'col-md-4 control-label'])!!}
						<div class="col-md-6">
							{!!Form::password('password',['class'=>'form-control'])!!}
						</div>	
					</div>
					<div class="form-group">
						<div class="col-md-6 col-md-offset-4">
							{!!Form::submit('Update Vendor',['class'=>'btn btn-info'])!!}
						</div>	
					</div>
					{!!Form::close()!!}
				</div>
			</div>
		</div>


@stop