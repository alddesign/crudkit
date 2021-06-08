@extends('crudkit::base.content')
{{-- Action Buttons --}}
@include('crudkit::list_action-buttons')

@section('page-content')
	{{-- Search Form --}}
	<div class="row crudkit-search crudkit-row">
		@include('crudkit::list_search-form')
	</div>
	{{-- Search/Filter Warning --}}
	@include('crudkit::list_filter_search_warning')
	<div class="crudkit-record-list crudkit-row table-responsive">
		{{-- Header --}}
		<table class="table">
			<thead>
			<tr>
			@if($cardAllowed)<th class="crudkit-cardpage-cell"></th>@endif
			@foreach ($summaryColumns as $column)
				@include('crudkit::list_lookups-th', ['position' => 'before-field'])
				@if(!$column->isHidden('list'))
					<th>
						{{$column->label}}
					</th>
				@endif
				@include('crudkit::list_lookups-th', ['position' => 'after-field'])
			@endforeach
			@foreach ($actions as $action)
				@if($action->onList && $action->visible)
					<th>
						<i>{{$action->label}}</i>
					</th>	
				@endif
			@endforeach
			</tr>
			</thead>
			<tbody>
			{{-- Record list --}}
			@foreach ($records['records'] as $index => $record)
				<tr>
				@if($cardAllowed)
				<td>
					<a href="{!! $cardPageUrls[$index] !!}" class="btn btn-accent crudkit-cardpage-btn"><i class="fa fa-chevron-right"></i> </a>
				</td>
				@endif
				@foreach ($summaryColumns as $column)
					@php
						$options = $column->options;
						$suffix = empty($options['suffix']) ? '' : (' ' . $options['suffix']);						
						$cardPageUrl = (in_array($column->name, $cardPageUrlColumns, true)) &&  (!empty($cardPageUrls[$index])) ? $cardPageUrls[$index] : '';
						$manyToOneUrl = !empty($manyToOneUrls[$index][$column->name]) ? $manyToOneUrls[$index][$column->name] : '';
						$value = $record[$column->name];
						$valueEnum = isset($options['enum'][$value]) ? $options['enum'][$value] : $value;
					@endphp
					{{-- LOOKUPS BEFORE FIELD --}}
					@include('crudkit::list_lookups-field', ['position' => 'before-field'])
					{{-- FIELDS --}}
					@if(!$column->isHidden('list'))
						<td class="crudkit-card-field-wrapper2">
						<span class="crudkit-card-field-wrapper3">
						@if(in_array($column->type, ['text', 'textlong', 'integer', 'decimal', 'datetime', 'date', 'time'], true))
							@if($cardPageUrl != '')
								<a href="{!! $cardPageUrl !!}" class="btn btn-accent"> {{$value}} {{$suffix}} </a>
							@elseif(!empty($column->link))
								<a href="{!! $column->link !!}"> {{$value}} {{$suffix}} </a>
							@elseif($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}"> {{$value}} {{$suffix}} </a>
							@elseif(!empty($options['link']))
								<a href="{{$value}}" target="_blank"> {{$value}} {{$suffix}} </a> 
							@elseif(!empty($options['email']))
								<a href="mailto:{{$value}}" target="_blank"> {{$value}} {{$suffix}} </a> 
							@else
								{{$value}} {{$suffix}}
							@endif
						@endif		
						@if($column->type === 'enum')
							@if($cardPageUrl != '')
								<a href="{!! $cardPageUrl !!}" class="btn btn-accent"> {{$valueEnum}} {{$suffix}} </a>
							@elseif(!empty($column->link))
								<a href="{!! $column->link !!}"> {{$value}} {{$suffix}} </a>
							@elseif($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}">{{$valueEnum}} {{$suffix}}</a> 
							@else
								{{$valueEnum}} {{$suffix}}
							@endif
						@endif							
						@if($column->type === 'boolean')
							@if($cardPageUrl != '')
								<a href="{!! $cardPageUrl !!}" class="btn btn-accent"> {{$value[1]}}</a>
							@elseif(!empty($column->link))
								<a href="{!! $column->link !!}"> {{$value}} {{$suffix}} </a>
							@elseif($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}">{{$value[1]}}</a> 
							@else
								@if($value[0] === true)
									<kbd style="background-color: #333; color: #eee; font-size: 1.7rem;">{{$value[1]}}</kbd>
								@else
									<kbd style="background-color: #eee; color: #111; font-size: 1.7rem;">{{$value[1]}}</kbd>
								@endif
							@endif
						@endif				
						@if(in_array($column->type, ['image', 'blob'], true))
							<span><code class="bg-warning text-primary">{{ $value }}</code></span>
						@endif
						</span>
						@include('crudkit::list_lookups-field', ['position' => 'to-field'])
						</td>
					@endif
					{{-- LOOKUPS AFTER FIELD --}}
					@include('crudkit::list_lookups-field', ['position' => 'after-field'])
				@endforeach
				{{-- Line Actions --}}
				@include('crudkit::list_line-actions')
				</tr>
			@endforeach
			</tbody>
		</table>
		{{-- Pagination --}}
		@include('crudkit::list_pagination')
	</div>
@endsection