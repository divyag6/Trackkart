@extends('app')
   
@section('sidebar')
	@include('includes.sidebar')
@stop

@section('content')
 
	<div class="col-md-8 col-md-offset-1">
		<div class="row" style="padding-bottom: 10px">
			
			<div>
		
			<a title="Add a new vendor" href="/vendorconfig/create" class="btn btn-info pull-right icon" role="button"  aria-label="Add New">
				<span>Add Vendor</span>
			</a>
		
			</div>
		</div>
		
		<div class="row">
		@if(!isset($message))
			<div class="panel panel-info">
				<div class="panel-heading">Vendor Configurations</div>
				<table class="table-hover table">
					@foreach($configs as $config)
						<tr>
							<td>
								<a title="Delete vendor" id="del" href="/vendorconfig/{{$config->id}}" class="btn pull-left" role="button" data-method="delete" data-confirm="Are you sure?" data-token={{csrf_token()}} aria-label="Delete">
									<span class="glyphicon glyphicon-minus" style="color: black" aria-hidden="true"></span>
								</a>
							</td>
							<td>
								{{ $config->name }}
							</td>
							<td>
								{{ $config->email }}
								
								<a title="Edit" href="/vendorconfig/{{$config->id}}/edit" class="btn pull-right" role="button" aria-label="Edit">
									<span class="glyphicon glyphicon-chevron-right" style="color: black" aria-hidden="true"></span>
								</a>
							</td>
						</tr>
					@endforeach
				</table>
			</div>
			
			@else
				@include('includes.message')
			@endif
		</div>
	</div>	
	
@stop






