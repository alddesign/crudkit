@section('action-buttons')
	<!-- Chart -->
	@if($chartAllowed)
		<a href="{{$chartPageUrl}}" class="btn btn-default crudkit-button" id="curdkit-chart-button"><i class="fa fa-bar-chart"></i> &nbsp;{{$texts['show_as_chart']}}</a>
	@endif
	<!-- Export -->
	@if($exportAllowed)
		<a href="{{$exportCsvUrl}}" class="btn btn-default crudkit-button" id="curdkit-export-csv-button"><i class="fa fa-file-excel-o"></i> &nbsp;{{$texts['csv_export']}}</a>
		<a href="{{$exportXmlUrl}}" class="btn btn-default crudkit-button" id="curdkit-export-xml-button"><i class="fa fa-code"></i> &nbsp;{{$texts['xml_export']}}</a>
	@endif
	<!-- Create -->
	@if($createAllowed)
		<a href="{{ action('\Alddesign\Crudkit\Controllers\CrudkitController@createView', ['page-id' => $pageId]) }}" class="btn btn-primary crudkit-button" id="curdkit-create-button"><i class="fa fa-plus-circle"></i> &nbsp;{{$texts['new']}}</a>
	@endif
@endsection