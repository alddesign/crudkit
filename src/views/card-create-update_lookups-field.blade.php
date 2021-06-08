@if($pageType !== 'create')
@foreach ($lookups as $lookupName => $lookup)
	@if($lookup->fieldname === $column->name && $lookup->position === $position && $lookup->onCard && $lookup->visible)
		@php
			$faIcon = !empty($lookup->faIcon) ? '<i class="fa fa-'.$lookup->faIcon.'"></i>&nbsp;' : '';	
			$btnClass = !empty($lookup->btnClass) ? 'btn btn-'.$lookup->btnClass : '';	
			$btnClass .= !$lookup->enabled ? ' disabled' : '';
			$btnClass .= ($position === 'to-field') ? ' btn-sm' : '';
			$withBtnClass = !empty($lookup->btnClass) ? 'crudkit-with-btn' : '';
		@endphp
		@if($position != 'to-field')
			<div id="crudkit-lookup-{{$lookupName}}" class="form-group crudkit-lookup crudkit-before-after-field {{$withBtnClass}}" style="vertical-align: top;">
				<label>{{ $lookup->label }}</label>
				@if(!empty($lookup->drillDownLink))
					<div><a href="{!! $lookup->drillDownLink !!}" class="crudkit-lookup-value {{$btnClass}}" target="{{$lookup->drillDownTarget}}">{!! $faIcon!!}{{$lookup->value}}</a></div>
				@else
					<div><span class="crudkit-lookup-value {{$btnClass}}">{!! $faIcon!!}{{$lookup->value}}</span></div>
				@endif
			</div>	
		@else
			<span id="crudkit-lookup-{{$lookupName}}" class="crudkit-lookup crudkit-to-field {{$withBtnClass}}">
			<b>{{ $lookup->label }}:&nbsp;</b>
			@if(!empty($lookup->drillDownLink))
				<a href="{!! $lookup->drillDownLink !!}" class="crudkit-lookup-value {{$btnClass}}" target="{{$lookup->drillDownTarget}}">{!! $faIcon!!}{{$lookup->value}}</a>
			@else
				<span class="crudkit-lookup-value {{$btnClass}}">{!! $faIcon!!}{{$lookup->value}}</span>
			@endif	
			</span>
		@endif
	@endif
@endforeach
@endif