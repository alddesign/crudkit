<!DOCTYPE html>
<html>
	<head>
		<title>{{config('crudkit.app_name', 'CRUDKit')}}</title>
		<link href="{{ URL::asset('crudkit/img/favicon.png') }}" rel="shortcut icon" />
		<link href="{{ URL::asset('crudkit/adminlte/bootstrap/css/bootstrap.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/adminlte/dist/css/AdminLTE.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/adminlte/dist/css/skins/_all-skins.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/jquery-ui-1.12.1.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/font-awesome.min.css') }}" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/crudkit.css') }}" rel="stylesheet" />
		@if(!empty(config('crudkit.fontsize','')))
		<!-- Crudkit Fontsize -->
		<style type="text/css">
			div.content-wrapper, 
			aside.main-sidebar, 
			aside.main-sidebar li.header
			{
				font-size: {{ config('crudkit.fontsize') }}; 
			}
		</style>
		@endif
		
		<script src="{{ URL::asset('crudkit/adminlte/plugins/jQuery/jquery-3.2.1.min.js') }}"></script>
		<script src="{{ URL::asset('crudkit/adminlte/plugins/jQueryUI/jquery-ui-1.12.1.min.js') }}"></script>	
		<script src="{{ URL::asset('crudkit/adminlte/bootstrap/js/bootstrap.min.js') }}"></script>
		<script src="{{ URL::asset('crudkit/adminlte/dist/js/app.min.js') }}" type="text/javascript"></script>			
		<script src="{{ URL::asset('crudkit/js/jquery-validation/jquery.validate.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/messages_de.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/additional-methods.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/jquery.validate.decimal-comma.js') }}" type="text/javascript"></script>
		@if($pageType === 'chart')
			<script src="{{ URL::asset('crudkit/js/chartjs/chart.bundle.min.js') }}" type="text/javascript"></script>
			<script src="{{ URL::asset('crudkit/js/crudkit-chart.js') }}" type="text/javascript"></script>
		@endif
		<script src="{{ URL::asset('crudkit/js/crudkit.js') }}" type="text/javascript"></script>
	</head>
	<body class="skin-{{ config('crudkit.skin', 'blue') }}">
		@yield('core-body')
	</body>
</html>