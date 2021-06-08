@extends('crudkit::base.content')
@section('page-content')
<div>
	@if(!empty($loginMessage))
		<div class="alert alert-{{ $loginMessageType }}">
		  {{$loginMessage}}
		</div>
	@endif
	<form id="login-form" name="login-form" method="post" action="api/login">
		<input type="hidden" name="_token" value="{{ csrf_token() }}" />
		<input type="hidden" name="crudkit-login-attempt" value="1" />
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="crudkit-userid">{{$texts['username']}}: </label>
					<input type="text" id="crudkit-userid" name="crudkit-userid" class="form-control"/>
				</div>
				<div class="form-group">
					<label for="crudkit-password">{{$texts['password']}}: </label>
					<input type="password" id="crudkit-password" name="crudkit-password" class="form-control"/>
				</div>
			</div>
		</div>	
		<button type="submit" form="login-form" class="btn bg-{{config('crudkit.accent', 'blue')}} btn-lg" style="margin-top:20px"><i class="fa fa-sign-in"></i> &nbsp;{{$texts['login']}}</button>
	</form>
</div>
@endsection