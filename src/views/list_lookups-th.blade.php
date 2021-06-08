@if(isset($lookups[0]))
	@foreach ($lookups[0] as $lookup)
		@if($lookup->fieldname === $column->name && $lookup->position === $position && $lookup->onList && $lookup->visible)
			<th>
				<i>{{$lookup->label}}</i>
			</th>	
		@endif
	@endforeach
@endif