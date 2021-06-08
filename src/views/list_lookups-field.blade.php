@foreach ($lookups[$index] as $lookupName => $lookup)
	@if($lookup->fieldname === $column->name && $lookup->position === $position && $lookup->onList && $lookup->visible)
	@php
		$faIcon = !empty($lookup->faIcon) ? '<i class="fa fa-'.$lookup->faIcon.'"></i>&nbsp;' : '';	
		$btnClass = !empty($lookup->btnClass) ? 'btn btn-sm btn-'.$lookup->btnClass : '';	
		$btnClass .= !$lookup->enabled ? ' disabled' : '';
	@endphp
		@if($position != 'to-field')
			<td>
				<div id="crudkit-lookup-{{$lookupName}}" class="crudkit-lookup crudkit-before-after-field">
					@if(!empty($lookup->drillDownLink))
						<a href="{!! $lookup->drillDownLink !!}" class="crudkit-lookup-value {{$btnClass}}" target="{{$lookup->drillDownTarget}}">{!! $faIcon!!}{{$lookup->value}}</a>
					@else
						<span class="crudkit-lookup-value">{!! $faIcon!!}{{$lookup->value}}</span>
					@endif
				</div>
			</td>			
		@else
			<span id="crudkit-lookup-{{$lookupName}}" class="crudkit-lookup crudkit-to-field">
			@if(!empty($lookup->drillDownLink))
				<a href="{!! $lookup->drillDownLink !!}" class="crudkit-lookup-value {{$btnClass}}" target="{{$lookup->drillDownTarget}}">{!! $faIcon!!}{{$lookup->value}}</a>
			@else
				<span class="crudkit-lookup-value {{$btnClass}}">{!! $faIcon!!}{{$lookup->value}}</span>
			@endif
			</span>
		@endif
	@endif
@endforeach