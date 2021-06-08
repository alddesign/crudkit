@foreach ($actions as $actionName => $action)
	@if($action->fieldname === $column->name && $action->onCard && $action->position === $position)	
		@php 
			$faIconClass = !empty($action->faIcon) ? 'fa fa-'.$action->faIcon : '';	
			$btnClass = !empty($action->btnClass) ? 'btn btn-'.$action->btnClass : 'btn btn-default';	
			$btnClass .= !$action->enabled ? ' disabled' : '';
		@endphp
		@if($position !== 'to-field') 
			<div class="form-group" style="vertical-align: top;">
				<form id="crudkit-action-{{ $actionName }}" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@action')}}" method="post" class="crudkit-action crudkit-action-{{ $actionName }}">
				<input type="hidden" name="_token" value="{{ csrf_token() }}" />
				<input type="hidden" name="page-id" value="{{ $pageId }}" />
				<input type="hidden" name="action-name" value="{{ $actionName }}" />
				@foreach ($primaryKeyValues as $index => $primaryKeyValue)
					<input type="hidden" name="pk-{!! $index !!}" value="{{ $primaryKeyValue }}" />
				@endforeach
				@if(!empty($action->label))
					<label>{{ $action->label }}</label>
					<hr class="crudkit-hr">
				@endif
				<button type="submit" class="{{$btnClass}} crudkit-action-button" style="margin-left: 0;">
					@if($faIconClass != '')<i class="{{$faIconClass}}"></i> &nbsp;@endif
					{{ $action->value}}
				</button>
				</form>	
			</div>
		@else
			<form id="crudkit-action-{{ $actionName }}" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@action')}}" method="post" class="crudkit-action crudkit-action-{{ $actionName }} crudkit-to-field">
				<input type="hidden" name="_token" value="{{ csrf_token() }}" />
				<input type="hidden" name="page-id" value="{{ $pageId }}" />
				<input type="hidden" name="action-name" value="{{ $actionName }}" />
				@foreach ($primaryKeyValues as $index => $primaryKeyValue)
					<input type="hidden" name="pk-{!! $index !!}" value="{{ $primaryKeyValue }}" />
				@endforeach
				<button type="submit" class="{{$btnClass}} crudkit-action-button crudkit-to-field" style="margin-bottom: 0;">
					@if($faIconClass != '')<i class="{{$faIconClass}}"></i> &nbsp;@endif
					{{ $action->value}}
				</button>
			</form>	
		@endif
	@endif
@endforeach