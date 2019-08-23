@extends('crudkit::core-admin-panel')
@section('page-content')
<div>
	@if($loginMessage != '')
		@php 
			$alertClass = $loginMessageType != '' ? (' alert-'.$loginMessageType) : ''; 
		@endphp
		<div class="alert{{ $alertClass }}">
		  {{$loginMessage}}
		</div>
	@endif
	<form id="login-form" name="login-form" method="post" action="api/login">
		<input type="hidden" name="_token" value="{{ csrf_token() }}" />
		<input type="hidden" name="crudkit-login-attempt" value="1" />
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="crudkit-userid">Benutzername: </label>
					<input type="text" id="crudkit-userid" name="crudkit-userid" class="form-control"/>
				</div>
				<div class="form-group">
					<label for="crudkit-password">Passwort: </label>
					<input type="password" id="crudkit-password" name="crudkit-password" class="form-control"/>
				</div>
			</div>
		</div>	
		<button type="submit" form="login-form" class="btn btn-primary btn-lg" style="margin-top:20px"><i class="fa fa-sign-in"></i> &nbsp;Login</button>
	</form>
</div>
@endsection