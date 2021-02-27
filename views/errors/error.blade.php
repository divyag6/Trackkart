@extends('app')

@section('sidebar')
	@include('includes.sidebar')
@stop

@section('content')
	<div class="col-md-9">
		@include('includes.message')
	</div>
	
@stop