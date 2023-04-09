@section('action-buttons')
	{{-- Update --}}
	@if ($updateAllowed)
		<a class="btn btn-primary pull-right crudkit-button" href="{{$updateUrl}}"><i class="fa fa-pencil"></i> &nbsp;{{$texts['edit']}}</a>
	@endif
	{{-- Delete --}}
	@if ($deleteAllowed)
		@if(!$confirmDelete)
			<a id="crudkit-delete-button" class="btn btn-danger pull-right crudkit-button" href="{{$deleteUrl}}"><i class="fa fa-trash"></i> &nbsp;{{$texts['delete']}}</a>		
		@else
			{{-- Modal?! --}}
			<button id="crudkit-confirm-delete-button" class="btn btn-danger pull-right crudkit-button" data-toggle="modal" data-target="#crudkit-confirm-delete-modal" type="button"><i class="fa fa-trash"></i> &nbsp;{{$texts['delete']}}</button>
			<div id="crudkit-confirm-delete-modal" class="modal fade"  role="dialog">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-red">
							<h4 class="modal-title">{{$texts['delete']}}</h4>
						</div>
						<div class="modal-body">
							{{$texts['delete_confirmation']}}
						</div>
						<div class="modal-footer">
							<a href="{{$deleteUrl}}" class="btn btn-danger">{{$texts['yes']}}</a>
							<button type="button" class="btn btn-default" data-dismiss="modal">{{$texts['no']}}</button>
						</div>
					</div>
				</div>
			</div>
		@endif
	@endif
	{{-- Actions Top --}}
	<span id="crudkit-actions-top">
	@foreach ($actions as $actionName => $action)
		@if($action->onCard && ($action->position === 'top' || $action->position === 'both'))
			@php 
				$faIconClass = !empty($action->faIcon) ? 'fa fa-'.$action->faIcon : '';	
				$btnClass = !empty($action->btnClass) ? 'btn btn-'.$action->btnClass : 'btn btn-default';	
				$btnClass .= !$action->enabled ? ' disabled' : '';
			@endphp

			<form id="crudkit-action-{{ $actionName }}" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@action')}}" class="crudkit-action crudkit-action-{{ $actionName }}" method="post">
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
					{{ $action->value}}
				</button>
			</form>
		@endif
	@endforeach
	</span>
@endsection