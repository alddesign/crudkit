@php
$currentValue = [$fieldvalue,''];
foreach($manyToOneValues[$column->name] as $manyToOneValue)
{
	if($fieldvalue === $manyToOneValue[0])
	{
		$currentValue = $manyToOneValue;
		break;
	}
}
@endphp
@if($column->ajax)
	<select id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" data-manual-input="{{$column->manualInput}}" data-current-value0="{{ $currentValue[0] }}" data-current-value1="{{ $currentValue[1] }}" data-input-timeout="{{$column->getAjaxOptions()->inputTimeout}}" data-min-input-len="{{ $column->getAjaxOptions()->minInputLength }}" class="form-control crudkit-ajax-select crudkit-ajax-select-manytoone"{!! $inputAttributes !!}>
		<option value=""></option> {{-- Always have an empty value --}}
		@if(strval($fieldvalue) !== '')
		<option value="{{$currentValue[0]}}" class="crudkit-selected-option" selected>{{$currentValue[0]}} {{ $currentValue[1] }}</option> {{-- Always include the current value if not empty --}}
		@endif 
	</select>
@else
	<select id="crudkit-field-{{ $column->name }}" data-manual-input="{{$column->manualInput}}" name="{{ $column->name }}" class="form-control crudkit-select2"{!! $inputAttributes !!}>
		<option value=""></option> {{-- Always have an empty value --}}
		@if(strval($fieldvalue) !== '')
		<option value="{{$currentValue[0]}}" class="crudkit-selected-option" selected>{{$currentValue[0]}} {{ $currentValue[1] }}</option>
		@endif {{-- Always include the current value if not empty --}}
		@foreach($manyToOneValues[$column->name] as $manyToOneValue)
			@if($fieldvalue !== $manyToOneValue[0])<option value="{{ $manyToOneValue[0] }}">{{ $manyToOneValue[0] }} {{ $manyToOneValue[1] }}</option> @endif
		@endforeach
	</select>
@endif
