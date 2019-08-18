@extends('crudkit::core-admin-panel')
@php
$texts = Alddesign\Crudkit\Classes\DataProcessor::getTexts();
@endphp

@section('page-content')
<!-- Dynamic Filters -->
<div id="crudkit-filters" class="crudkit-row"> 
	<div class="crudkit-row row">
		<div class="col-md-4">
			<button class="btn btn-success crudkit-filter-add-button" type="button"> &nbsp;<i class="fa fa-plus"></i> &nbsp;{{$texts['filters']}}</button>
			&nbsp;&nbsp;
			<a id="crudkit-save-chart-button" class="btn btn-primary disabled" href="" download="crudkit-chart.png"> &nbsp;<i class="fa fa-save"></i> &nbsp;{{$texts['save_chart']}}</a>
		</div>
	</div>
	<div id="crudkit-filter-reference" class="row"><!-- Reference Filter (hidden) - used by JS to create new instances in DOM -->
		<div class="col-md-2"> 
			<select class="crudkit-filter-field form-control">
				<option class="list-group-item" value="" selected></option>
				@foreach ($columns as $column)
					<option class="list-group-item" value="{{$column->name}}">{{$column->label}}</option>
				@endforeach
			</select>
		</div>
		<div class="col-md-2">
			<select class="crudkit-filter-operator form-control">
				@foreach($filterOperators as $key => $value)
					<option class="list-group-item" value="{{$key}}" @if($loop->first){{'selected'}}@endif>{{$value}}</option>
				@endforeach
			</select>
		</div>
		<div class="col-md-2">
			<div class="input-group">
				<input class="crudkit-filter-value form-control" type="text" value="" placeholder="{{$texts['filter_value']}}" />
				<span class="input-group-btn">
					<button class="btn btn-danger crudkit-filter-remove-button" type="button"><i class="fa fa-times"></i> &nbsp;Entfernen</button>
				</span>
			</div>
		</div>
	</div>
	<!-- Add existing Filters -->
	@foreach($filters as $index => $filter)
	<div class="row crudkit-filter"><!-- hidden -->
		<div class="col-md-2"> 
			<select class="crudkit-filter-field form-control">
				<option class="list-group-item" value=""></option>
				@foreach ($columns as $column)
					<option class="list-group-item" value="{{$column->name}}" @if($filter['field'] === $column->name){{'selected'}}@endif>{{$column->label}}</option>
				@endforeach
			</select>
		</div>
		<div class="col-md-2">
			<select class="crudkit-filter-operator form-control">
				@foreach($filterOperators as $key => $value)
					<option class="list-group-item" value="{{$key}}" @if($filter['operator'] === $key){{'selected'}}@endif>{{$value}}</option>
				@endforeach
			</select>
		</div>
		<div class="col-md-2">
			<div class="input-group">
				<input class="crudkit-filter-value form-control" type="text" value="{{$filter['value']}}" placeholder="{{$texts['filter_value']}}"/>
				<span class="input-group-btn">
					<button class="btn btn-danger crudkit-filter-remove-button" type="button"><i class="fa fa-minus"></i> &nbsp;Entfernen</button>
				</span>
			</div>
		</div>
	</div>
	@endforeach
</div>
<script type="text/javascript">
	var _crudkitGetChartDataUrl = '{!! $getChartDataUrl !!}';
	var _crudkitGetChartDataUrlParameters = {!! $getChartDataUrlParamters !!};
</script>
<div id="crudkit-chart-controls" class="row crudkit-row">
	<!-- X Axis -->
	<div class="col-md-2">
		<label for="crudkit-chart-x">X-Achse</label>
		<select id="crudkit-chart-x" class="form-control">
			@foreach ($columns as $column)
				<option class="list-group-item" value="{{$column->name}}">{{$column->label}}</option>
			@endforeach
		</select>
	</div>
	<!-- Y Axis -->
	<div class="col-md-2">
		<label for="crudkit-chart-y">Y-Achse</label>
		<select id="crudkit-chart-y" class="form-control">
			@foreach ($columns as $columnName => $column)
				<option class="list-group-item" value="{{$column->name}}">{{$column->label}}</option>
			@endforeach
		</select>
	</div>
	<!-- Y Axis -->
	<div class="col-md-2">
		<label for="crudkit-chart-aggregation">Y-Achse Aggregierung</label>
		<div class="input-group">
			<select id="crudkit-chart-aggregation" class="form-control">
				<option class="list-group-item" value="count">Anzahl Datensätze</option>
				<option class="list-group-item" value="sum">Summe</option>
				<option class="list-group-item" value="avg">Durchschnitt</option>
				<option class="list-group-item" value="min">Minimum</option>
				<option class="list-group-item" value="max">Maximum</option>
			</select>
			<span class="input-group-btn">
				<button id="crudkit-chart-load-button" class="btn btn-default"><i class="fa fa-undo"></i> &nbsp;{{$texts['load']}}</button>
			</span>
		</div>
	</div>
</div>
<div class="crudkit-record-list crudkit-row">
	<div id="crudkit-chart-wrapper">
		<i id="crudkit-chart-loader" class="hidden fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
		<canvas id="crudkit-chart"></canvas>
	</div>
</div>

<!-- Shows error messages after ajax request -->
<div id="crudkit-chart-error" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg">
	<div class="modal-content">
	  <div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&times;</button>
		<h4 class="modal-title"></h4><!-- Title -->
	  </div>
	  <div class="modal-body"></div><!-- Content -->
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	  </div>
	</div>
  </div>
</div>
@endsection