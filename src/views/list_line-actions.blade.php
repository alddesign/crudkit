@foreach ($actions as $actionName => $action)
	@if($action->onList)
		@php 
			$faIconClass = !empty($action->faIcon) ? 'fa fa-'.$action->faIcon : '';	
			$btnClass = !empty($action->btnClass) ? 'btn btn-'.$action->btnClass: 'btn btn-default';
			$btnClass .= !$action->enabled ? ' disabled' : '';							
		@endphp
		<td>
			<form id="crudkit-action-{{ $actionName }}" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@action')}}" method="post" class="crudkit-line-action crudkit-action-{{ $actionName }}">
				<input type="hidden" name="_token" value="{{ csrf_token() }}" />
				<input type="hidden" name="page-id" value="{{ $pageId }}" />
				<input type="hidden" name="action-name" value="{{ $actionName }}" />
				@foreach ($primaryKeyColumns as $primaryKeyColumnName => $primaryKeyColumn)
					<input type="hidden" name="pk-{!! $loop->index !!}" value="{{ $record[$primaryKeyColumnName] }}" />
				@endforeach
				<button type="submit" class="{{$btnClass}} crudkit-line-action-button">
					@if($faIconClass != '')<i class="{{$faIconClass}}"></i> &nbsp;@endif
					{{ $action->value }}
				</button>
			</form>
		</td>	
	@endif
@endforeach