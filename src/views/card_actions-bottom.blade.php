<div id="crudkit-actions-bottom">
@foreach ($actions as $actionName => $action)
	@if($action->onCard && ($action->position === 'bottom' || $action->position === 'both'))
		@php 
			$faIconClass = !empty($action->faIcon) ? 'fa fa-'.$action->faIcon : '';	
			$btnClass = !empty($action->btnClass) ? 'btn btn-'.$action->btnClass : 'btn btn-default';	
			$btnClass .= !$action->enabled ? ' disabled' : '';
		@endphp
		<form id="crudkit-action-{{ $actionName }}" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@action')}}" method="post" class="pull-right crudkit-action crudkit-action-{{ $actionName }}">
			<input type="hidden" name="_token" value="{{ csrf_token() }}" />
			<input type="hidden" name="page-id" value="{{ $pageId }}" />
			<input type="hidden" name="action-name" value="{{ $actionName }}" />
			@foreach ($primaryKeyValues as $index => $primaryKeyValue)
				<input type="hidden" name="pk-{!! $index !!}" value="{{ $primaryKeyValue }}" />
			@endforeach
			<button type="submit" class="{{$btnClass}} pull-right crudkit-action-button">
				@if($faIconClass != '')<i class="{{$faIconClass}}"></i> &nbsp;@endif
				{{ $action->value}}
			</button>
		</form>
	@endif
@endforeach
</div>