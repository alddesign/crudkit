<!DOCTYPE html>
<html>
	<head>
		<title>{{config('crudkit.app_name', 'CRUDKit')}}</title>
		<link href="{{ URL::asset('crudkit/img/favicon.png') }}" rel="shortcut icon" />
		<link href="{{ URL::asset('crudkit/css/bootstrap.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/adminlte.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/adminlte.all-skins.min.css') }}" type="text/css" rel="stylesheet" />
		
		<link href="{{ URL::asset('crudkit/css/jquery-ui-1.12.1.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/font-awesome.min.css') }}" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/crudkit.css') }}" rel="stylesheet" />
		@if(!empty(config('crudkit.fontsize','')))
		<!-- Crudkit Fontsize -->
		<style type="text/css">
			div.content-wrapper, aside.main-sidebar, aside.main-sidebar li.header {	font-size: {{ config('crudkit.fontsize') }}; }
		</style>
		@endif
		<script src="{{ URL::asset('crudkit/js/jquery.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-ui.min.js') }}" type="text/javascript"></script>	
		<script src="{{ URL::asset('crudkit/js/datepicker-de.js') }}" type="text/javascript"></script>	
		<script src="{{ URL::asset('crudkit/js/bootstrap.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/adminlte.min.js') }}" type="text/javascript"></script>	
		<script src="{{ URL::asset('crudkit/js/jquery-validation/jquery.validate.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/messages_de.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/additional-methods.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/jquery.validate.decimal-comma.js') }}" type="text/javascript"></script>
		@if($pageType === 'chart')
			<script src="{{ URL::asset('crudkit/js/chart.bundle.min.js') }}" type="text/javascript"></script>
			<script src="{{ URL::asset('crudkit/js/crudkit-chart.js') }}" type="text/javascript"></script>
		@endif
		<script src="{{ URL::asset('crudkit/js/crudkit.js') }}" type="text/javascript"></script>
		<script type="text/javascript"> 
			var crudkit = 
			{
				language : '{{ config('crudkit.language', '') }}'
			};
		</script>
	</head>
	<body class="skin-{{ config('crudkit.skin', 'blue') }}">
		@yield('core-body')
	</body>
</html>