@extends('crudkit::core-admin-panel')
@php
	$texts = Alddesign\Crudkit\Classes\DataProcessor::getTexts();
@endphp
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
		<a href="{{ url('admin-panel/create-view?page-id='.$pageId) }}" class="btn btn-primary crudkit-button" id="curdkit-create-button"><i class="fa fa-plus-circle"></i> &nbsp;{{$texts['new']}}</a>
	@endif
@endsection
@section('page-content')

	<div class="row crudkit-search crudkit-row">
		<!-- Search From -->
		<form id="search-form" name="search-form" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@listView')}}" method="GET">
			<input type="hidden" name="page-id" value="{{ $pageId }}" />
			@foreach ($filters as $index => $filter)
				<!-- Apply all Filters -->
				<input type="hidden" name="ff-{{$index}}" value="{{$filter->field}}" />
				<input type="hidden" name="fo-{{$index}}" value="{{$filter->operator}}" />
				<input type="hidden" name="fv-{{$index}}" value="{{$filter->value}}" />
			@endforeach
			<div class="col-md-3">
				<select id="search-column-name" name="search-column-name" class="form-control">
				@foreach ($summaryColumns as $summaryColumn)
					<option class="list-group-item" value="{{$summaryColumn->name}}" @if($searchColumnName === $summaryColumn->name){{'selected'}}@endif>{{$summaryColumn->label}}</option>
				@endforeach
				</select>
			</div>
			<div class="col-md-3">
				<div class="input-group">
					<input id="search-text" name="search-text" class="form-control" type="text" value="{{$searchText}}" />
					<span class="input-group-btn">
						<button id="search-button" class="btn btn-default" type="submit"><i class="fa fa-search"></i> &nbsp;{{$texts['search']}}</button>
						@if($hasSearch)
							<button name="reset-search" value="true" id="reset-search-button" class="btn btn-default" type="submit" ><i class="fa fa-undo"></i> &nbsp;{{$texts['reset_search']}}</button>
						@endif
					</span>
				</div>
			</div>
		</form>
	</div>

	<!-- Filter / Search Warining -->
	@if($hasFilters || $hasSearch)
		<div class="crudkit-filter-search-warning crudkit-row"> 
			@if($hasFilters)
				<div class="alert alert-warning">
					{!!$texts['filter_warning_html']!!}
				</div>	
			@endif
			@if($hasSearch)
				<div class="alert alert-warning">
					{!!$texts['search_warning_html']!!}
				</div>	
			@endif
		</div>
	@endif
	<div class="crudkit-record-list crudkit-row">
		<!-- Record List -->
		<table class="table">
			<thead>
			<tr>
			@foreach ($summaryColumns as $summaryColumn)
				@if(!$summaryColumn->isHidden('list'))
					<th>
						{{$summaryColumn->label}}
					</th>
				@endif
			@endforeach
			@foreach ($actions as $action)
				@if($action->onList)
					<th>
						<i>{{$action->columnLabel}}</i>
					</th>	
				@endif
			@endforeach
			</tr>
			</thead>
			<tbody>
			@foreach ($records['records'] as $index => $record)
				<tr>
				@foreach ($summaryColumns as $summaryColumn)
					@php
						$suffix = empty($summaryColumn->options['suffix']) ? '' : (' ' . $summaryColumn->options['suffix']);						
						$cardPageUrl = (in_array($summaryColumn->name, $cardPageUrlColumns, true)) &&  (!empty($cardPageUrls[$index])) ? $cardPageUrls[$index] : '';
						$manyToOneUrl = !empty($manyToOneUrls[$index][$summaryColumn->name]) ? $manyToOneUrls[$index][$summaryColumn->name] : '';
						$oneToManyUrl = !empty($oneToManyUrls[$index][$summaryColumn->name]) ? $oneToManyUrls[$index][$summaryColumn->name] : '';
						$value = $record[$summaryColumn->name];
					@endphp
					@if(!$summaryColumn->isHidden('list'))
						<td>
						@if($summaryColumn->type === 'text')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
								@else
									{{$value}} {{$suffix}}
								@endif
							</div>
						@endif		
						@if($summaryColumn->type === 'textlong')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>	
								@else
									{{$value}} {{$suffix}}
								@endif
							</div>
						@endif	
						@if($summaryColumn->type === 'email')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
								@else
									{{$value}} {{$suffix}}
								@endif
							</div>
						@endif
						@if($summaryColumn->type === 'integer')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
								@else
									{{$value}} {{$suffix}}
								@endif
							</div>
						@endif
						@if($summaryColumn->type === 'decimal')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
								@else
									{{$value}} {{$suffix}}
								@endif
							</div>
						@endif
						@if($summaryColumn->type === 'enum')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$summaryColumn->options['enum'][$value]}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$summaryColumn->options['enum'][$value]}} {{$suffix}} </a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$summaryColumn->options['enum'][$value]}} {{$suffix}} </a>
								@else
									{{$summaryColumn->options['enum'][$value]}} {{$suffix}}
								@endif
							</div>
						@endif	
						@if($summaryColumn->type === 'datetime')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
								@else
									{{$value}} {{$suffix}}
								@endif
							</div>
						@endif				
						@if($summaryColumn->type === 'date')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
								@else
									{{$value}} {{$suffix}}
								@endif
							</div>
						@endif
						@if($summaryColumn->type === 'time')
							<div>
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}} {{$suffix}} </a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
								@else
									{{$value}} {{$suffix}}
								@endif
							</div>
						@endif									
						@if($summaryColumn->type === 'boolean')
							<div>
								
								@if($cardPageUrl != '')
									<a href="{!! $cardPageUrl !!}" class="btn btn-primary"> {{$value}}</a>
								@elseif($manyToOneUrl != '')
									<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}}</a> 
								@elseif($oneToManyUrl != '')
									<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
								@else
									@if($value === $texts['yes'])
										<code class="bg-success text-success">{{$value}}</code>
									@else
										<code class="bg-danger text-danger">{{$value}}</code>
									@endif
								@endif
							</div>
						@endif				
						@if($summaryColumn->type === 'image')
							<div>
								<span><code class="bg-warning text-primary">{{ $value }}</code></span>
							</div>
						@endif
						@if($summaryColumn->type === 'blob')
							<div>
								<span><code class="bg-warning text-primary">{{ $value }}</code></span>
							</div>
						@endif
						</td>
					@endif
				@endforeach
				<!-- Line Actions -->
				@foreach ($actions as $action)
					@if($action->onList)
						@php 
							$faIconClass = !empty($action->faIcon) ? 'fa fa-'.$action->faIcon : '';	
							$btnClass = !empty($action->btnClass) ? 'btn btn-'.$action->btnClass: 'btn btn-default';
							$btnClass .= !$action->enabled ? ' disabled' : '';							
						@endphp
						<td>
							<form action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@action')}}" method="post" class="crudkit-line-action">
								<input type="hidden" name="_token" value="{{ csrf_token() }}" />
								<input type="hidden" name="page-id" value="{{ $pageId }}" />
								<input type="hidden" name="action-name" value="{{ $action->name }}" />
								@foreach ($primaryKeyColumns as $primaryKeyColumnName => $primaryKeyColumn)
									<input type="hidden" name="pk-{!! $loop->index !!}" value="{{ $record[$primaryKeyColumnName] }}" />
								@endforeach
								<button type="submit" class="{{$btnClass}} crudkit-line-action-button">
									@if($faIconClass != '')<i class="{{$faIconClass}}"></i> &nbsp;@endif
									{{ $action->label }}
								</button>
							</form>
						</td>	
					@endif
				@endforeach
				</tr>
			@endforeach
			</tbody>
		</table>
		<!-- Pagination -->
		@if(count($records['records']) < $records['total'])
			@php
				$showFirst = ($pageNumber > 1);
				$showLast = ($pageNumber * $recordsPerPage) < $records['total'];
			@endphp
			<ul class="pagination">
				<!-- First / Previous -->
				@if($showFirst)
					<li><a href="{{$paginationUrls['first']}}">{{$texts['first']}}</a></li>
					<li><a href="{{$paginationUrls['previous']}}">{{$texts['prev']}}</a></li>
				@else 
					<li class="disabled"><span>{{$texts['first']}}</span></li>
					<li class="disabled"><span class="disabled">{{$texts['prev']}}</span></li>
				@endif
				
				<!-- Pages -->
				@if(isset($paginationUrls['predot'])) <li class="disabled"><span>...</span></li> @endif
				@foreach($paginationUrls['pages'] as $index => $url)
					<li class="@if($index == $pageNumber){{'active'}} @endif"><a href="{{$url}}">{{ $index }}</a></li>
				@endforeach
				@if(isset($paginationUrls['afterdot'])) <li class="disabled"><span>...</span></li> @endif
				<!-- Last / Next -->
				@if($showLast)
					<li><a href="{{$paginationUrls['next']}}">{{$texts['next']}}</a></li>
					<li><a href="{{$paginationUrls['last']}}">{{$texts['last']}}</a></li>
				@else
					<li class="disabled"><span>{{$texts['next']}}</span></li>
					<li class="disabled"><span>{{$texts['last']}}</span></li>
				@endif
			</ul>
		@endif
	</div>
@endsection