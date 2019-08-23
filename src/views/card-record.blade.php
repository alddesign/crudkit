@extends('crudkit::core-admin-panel')
@php
	$texts = Alddesign\Crudkit\Classes\DataProcessor::getTexts();
@endphp
@section('action-buttons')
	<!-- Update -->
	@if ($updateAllowed)
		<a class="btn btn-primary pull-right crudkit-button" href="{{$updateUrl}}"><i class="fa fa-pencil"></i> &nbsp;{{$texts['edit']}}</a>
	@endif
	<!-- Delete -->
	@if ($deleteAllowed)
		@if($confirmDelete)
			<a id="crudkit-delete-button" class="btn btn-danger pull-right crudkit-button" href="{{$deleteUrl}}"><i class="fa fa-trash"></i> &nbsp;{{$texts['delete']}}</a>		
		@else
			<!-- Modal -->
			<button id="crudkit-confirm-delete-button" class="btn btn-danger pull-right crudkit-button" data-toggle="modal" data-target="#crudkit-confirm-delete-modal" type="button"><i class="fa fa-trash"></i> &nbsp;{{$texts['delete']}}</button>
			<div id="crudkit-confirm-delete-modal" class="modal fade"  role="dialog">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title">{{$texts['delete']}}</h4>
						</div>
						<div class="modal-body">
							{{$texts['delete_confirmation']}}
						</div>
						<div class="modal-footer">
							<a href="{{$deleteUrl}}" class="btn btn-primary">{{$texts['yes']}}</a>
							<button type="button" class="btn btn-default" data-dismiss="modal">{{$texts['no']}}</button>
						</div>
					</div>
				</div>
			</div>
		@endif
	@endif
	<!-- Actions Top -->
	<span id="crudkit-actions-top">
	@foreach ($actions as $actionName => $action)
		@if($action->onCard && ($action->position === 'top' || $action->position === 'both'))
			@php 
				$faIconClass = !empty($action->faIcon) ? 'fa fa-'.$action->faIcon : '';	
				$btnClass = !empty($action->btnClass) ? 'btn btn-'.$action->btnClass : 'btn btn-default';	
				$btnClass .= !$action->enabled ? ' disabled' : '';
			@endphp

			<form action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@action')}}" class="crudkit-action" method="post">
				<input type="hidden" name="_token" value="{{ csrf_token() }}" />
				<input type="hidden" name="page-id" value="{{ $pageId }}" />
				<input type="hidden" name="action-name" value="{{ $actionName }}" />
				@foreach ($primaryKeyValues as $index => $primaryKeyValue)
				<input type="hidden" name="pk-{!! $index !!}" value="{{ $primaryKeyValue }}" />
				@endforeach
				<button type="submit" class="{{$btnClass}} pull-right crudkit-action-button">
					@if($faIconClass !== '')
					<i class="{{$faIconClass}}"></i> &nbsp;
					@endif
					{{ $action->label}}
				</button>
			</form>
		@endif
	@endforeach
	</span>
@endsection
@section('page-content')
	<dl class="dl-horizontal">
		@foreach($record as $columnName => $value)
			@php
				$column = $columns[$columnName];
				$suffix = empty($column->options['suffix']) ? '' : $column->options['suffix'];
				$manyToOneUrl = !empty($manyToOneUrls[$columnName]) ? $manyToOneUrls[$columnName] : '';
				$oneToManyUrl = !empty($oneToManyUrls[$columnName]) ? $oneToManyUrls[$columnName] : '';
			@endphp
			<!-- Section Start -->
			@foreach($sections as $section)
				@if($section->from === $columnName)
					<div class="panel panel-primary">
						<div class="panel-heading">
							<div class="panel-title"><a data-toggle="collapse" href="#collapse-{{ $loop->index }}">{{ $section->title }}</a></div>
						</div>
						<div id="collapse-{{ $loop->index }}" class="panel-collapse collapse in">
							<div class="panel-body">
				@endif
			@endforeach
			@if(!$column->isHidden('card'))
				<div class="form-group" style="vertical-align: top;">
					<!-- Tooltip -->
					@if(!empty($column->options['tooltip']))
						<a class="crudkit-tooltip" href="#" data-toggle="tooltip" title="{{ $column->options['tooltip'] }}">
							<span class="glyphicon glyphicon-info-sign"> </span>
						</a>&nbsp;
					@endif
					<!-- Lable -->
					<label>{{ $column->label }}</label>
					<!-- Description -->
					@if(!empty($column->options['description']))
						<div class="crudkit-description">{{$column->options['description']}}</div>
					@endif
					<!-- Data -->
					@if($column->type === 'text')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
							@else
								{{$value}} {{$suffix}}
							@endif
						</div>
					@endif				
					@if($column->type === 'textlong')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
							@else
								{{$value}} {{$suffix}}
							@endif
						</div>
					@endif
					@if($column->type === 'email')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
							@else
								<a href="mailto:{{$value}}">{{$value}}</a>
							@endif
						</div>
					@endif
					@if($column->type === 'integer')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
							@else
								{{$value}} {{$suffix}}
							@endif
						</div>
					@endif		
					@if($column->type === 'decimal')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
							@else
								{{$value}} {{$suffix}}
							@endif
						</div>
					@endif					
					@if($column->type === 'enum')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$column->options['enum'][$value]}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$column->options['enum'][$value]}} {{$suffix}} </a>
							@else
								{{$column->options['enum'][$value]}} {{$suffix}}
							@endif
						</div>
					@endif				
					@if($column->type === 'datetime')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
							@else
								{{$value}} {{$suffix}}
							@endif
						</div>
					@endif					
					@if($column->type === 'date')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
							@else
								{{$value}} {{$suffix}}
							@endif
						</div>
					@endif
					@if($column->type === 'time')
						<div class="well well-sm">
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} {{$suffix}} </a>
							@else
								{{$value}} {{$suffix}}
							@endif
						</div>
					@endif										
					@if($column->type === 'boolean')
						<div>
							@if($manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}" class="btn btn-default"> {{$value}} </a> 
							@elseif($oneToManyUrl != '')
								<a href="{!! $oneToManyUrl !!}" class="btn btn-default"> {{$value}} </a>
							@else
								@if($value === $texts['yes'])
									<code class="bg-success text-success">{{$value}}</code>
								@else
									<code class="bg-danger text-danger">{{$value}}</code>
								@endif
							@endif
						</div>
					@endif
					@if($column->type === 'image')
						@if(!empty($value))
							<div class="well well-sm">
								<img src="data:image;base64,{{$value}}" />
							</div>
						@else
							<div>
								<span><code class="bg-warning text-primary">0 KB</code></span>
							</div>
						@endif
					@endif
					@if($column->type === 'blob')
						@if(!empty($value))
							<div>
								<code class="bg-warning text-primary">{{$value}}</code>
							</div>
						@else
							<div>
								<code class="bg-warning text-primary">0 KB</code>
							</div>
						@endif
					@endif
				</div>
			@endif
			@foreach($sections as $section)
				@if($section->to === $columnName)
							</div>
						</div>
					</div>
				@endif
			@endforeach
		@endforeach
		<!-- Actions Bottom -->
		<div id="crudkit-actions-bottom">
		@foreach ($actions as $actionName => $action)
			@if($action->onCard && ($action->position === 'bottom' || $action->position === 'both'))
				@php 
					$faIconClass = !empty($action->faIcon) ? 'fa fa-'.$action->faIcon : '';	
					$btnClass = !empty($action->btnClass) ? 'btn btn-'.$action->btnClass : 'btn btn-default';	
					$btnClass .= !$action->enabled ? ' disabled' : '';
				@endphp
				<form action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@action')}}" method="post" class="pull-right crudkit-action">
					<input type="hidden" name="_token" value="{{ csrf_token() }}" />
					<input type="hidden" name="page-id" value="{{ $pageId }}" />
					<input type="hidden" name="action-name" value="{{ $actionName }}" />
					@foreach ($primaryKeyValues as $index => $primaryKeyValue)
						<input type="hidden" name="pk-{!! $index !!}" value="{{ $primaryKeyValue }}" />
					@endforeach
					<button type="submit" class="{{$btnClass}} pull-right crudkit-action-button">
						@if($faIconClass != '')<i class="{{$faIconClass}}"></i> &nbsp;@endif
						{{ $action->label}}
					</button>
				</form>
			@endif
		@endforeach
		</div>
	</dl>
@endsection