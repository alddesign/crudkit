<form id="search-form" name="search-form" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@listView')}}" method="GET">
	<input type="hidden" name="page-id" value="{{ $pageId }}" />
	@foreach ($filters as $index => $filter)
		<!-- Apply all Filters -->
		<input type="hidden" name="ff-{{$index}}" value="{{$filter->field}}" />
		<input type="hidden" name="fo-{{$index}}" value="{{$filter->operator}}" />
		<input type="hidden" name="fv-{{$index}}" value="{{$filter->value}}" />
	@endforeach
	<div class="col-md-3">
		<select id="search-column-name" name="sc" class="form-control">
		@foreach ($summaryColumns as $column)
			<option class="list-group-item" value="{{$column->name}}" @if($searchColumnName === $column->name){{'selected'}}@endif>{{$column->label}}</option>
		@endforeach
		</select>
	</div>
	<div class="col-md-3">
		<div class="input-group">
			<input id="search-text" name="st" class="form-control" type="text" value="{{$searchText}}" />
			<span class="input-group-btn">
				<button id="search-button" class="btn btn-default" type="submit"><i class="fa fa-search"></i> &nbsp;{{$texts['search']}}</button>
				@if($hasSearch)
					<button name="sr" value="1" id="reset-search-button" class="btn btn-default" type="submit" ><i class="fa fa-undo"></i> &nbsp;{{$texts['reset_search']}}</button>
				@endif
			</span>
		</div>
	</div>
</form>