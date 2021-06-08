<select id="crudkit-field-{{$column->name}}" name="{{$column->name}}" data-current-value0="{{$customAjaxValue[0]}}" data-current-value1="{{$customAjaxValue[1]}}" data-input-timeout="{{$column->getAjaxOptions()->inputTimeout}}" data-min-input-len="{{$column->getAjaxOptions()->minInputLength}}" class="form-control crudkit-ajax-select crudkit-ajax-select-custom"{!! $inputAttributes !!}>
	<option value=""></option> {{-- Always have an empty value --}}
	@if(strval($customAjaxValue[0]) !== '')
	<option value="{{$customAjaxValue[0]}}" class="crudkit-selected-option" selected>{{$customAjaxValue[0]}} {{$customAjaxValue[1]}}</option> {{-- Always include the current value if not empty --}}
	@endif 
</select>

