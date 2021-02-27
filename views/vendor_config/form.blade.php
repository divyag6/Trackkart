<div class="form-group">
	{!!Form::label('name','Vendor Name:')!!}
	{!!Form::select('name',$vendor,'1',['class'=>'form-control'])!!}
</div>
<div class="form-group">	
	{!!Form::label('email','Email Id:')!!}
	{!!Form::text('email',$email,['class'=>'form-control'])!!}
</div>
<div class="form-group">
	{!!Form::label('password','Password:')!!}
	{!!Form::password('password',['class'=>'form-control'])!!}
</div>
<div class="form-group">
	{!!Form::submit('$submitButtonText',['class'=>'btn btn-primary form-control'])!!}
</div>