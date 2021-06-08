@php
	$js = isset($js) ? $js : [];
	$css = isset($css) ? $css : [];
	$pageId = isset($pageId) ? $pageId : '';
	$pageType = isset($pageType) ? $pageType : '';
	$record = in_array($pageType, ['card','create', 'update']) && isset($record) ? $record : null;
	$icons = config('crudkit.icons', []);
@endphp
<!DOCTYPE html>
<html>
	<head>
		<title>{{config('crudkit.app_name', 'CRUDKit')}}</title>
		{{-- CSS --}}
		@foreach($icons as $size => $icon)
			<link href="{{ URL::asset($icon) }}" rel="icon" sizes="{{ $size }}"/>
		@endforeach
		<link href="{{ URL::asset('crudkit/css/bootstrap.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/adminlte.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/adminlte.all-skins.min.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/bootstrap-datepicker.css') }}" type="text/css" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/font-awesome.min.css') }}" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/select2.min.css') }}" rel="stylesheet" />
		<link href="{{ URL::asset('crudkit/css/crudkit.css') }}" rel="stylesheet" />
		{{-- JS --}}
		<script src="{{ URL::asset('crudkit/js/jquery.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/bootstrap.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/bootstrap-datepicker.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/bootstrap-datepicker.de.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/adminlte.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/qrious.min.js') }}" type="text/javascript"></script>	
		<script src="{{ URL::asset('crudkit/js/jquery-validation/jquery.validate.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/messages_de.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/additional-methods.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/jquery-validation/jquery.validate.decimal-comma.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/crudkit-ajax-select.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/select2.full.min.js') }}" type="text/javascript"></script>
		<script src="{{ URL::asset('crudkit/js/select2.de.js') }}" type="text/javascript"></script>
		@if($pageType === 'chart')
			<script src="{{ URL::asset('crudkit/js/chart.bundle.min.js') }}" type="text/javascript"></script>
			<script src="{{ URL::asset('crudkit/js/crudkit-chart.js') }}" type="text/javascript"></script>
		@endif
		<script src="{{ URL::asset('crudkit/js/crudkit.js') }}" type="text/javascript"></script>
		{{-- Custom JS/CSS --}}
		@include('crudkit::base.html_js-css')
	</head>
	<body class="skin-{{config('crudkit.skin', 'blue')}} accent-{{config('crudkit.accent', 'blue')}} crudkit-body crudkit-pagetype-{{$pageType}}">
		@yield('core')
	</body>
</html>