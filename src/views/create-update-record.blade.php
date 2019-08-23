@extends('crudkit::core-admin-panel')
@section('page-content')
	@php
		$texts = Alddesign\Crudkit\Classes\DataProcessor::getTexts();
	@endphp
	@if($pageType === 'create')                                                                                
		<form id="create-form" class="create-update-form" name="create-form" method="post" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@createRecord')}}" enctype="multipart/form-data" novalidate="novalidate">
	@endif
	@if($pageType === 'update')
		<form id="update-form" class="create-update-form" name="update-form" method="post" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@updateRecord')}}" enctype="multipart/form-data" novalidate="novalidate">
		@foreach($primaryKeyColumns as $index => $primaryKeyColumn)
			<input type="hidden" name="pk-{{$index}}" value="{{$record[$primaryKeyColumn]}}" />
		@endforeach
	@endif
			<input type="hidden" name="_token" value="{{ csrf_token() }}" />
			<input type="hidden" name="page-id" value="{{ $pageId }}" />
			<dl class="dl-horizontal">
				@foreach ($columns as $column)
					@foreach($sections as $section)
						@if($section->from === $column->name)
							<div class="panel panel-primary">
								<div class="panel-heading">
									<div class="panel-title"><a data-toggle="collapse" href="#collapse-{{ $loop->index }}">{{ $section->title }}</a></div>
								</div>
								<div id="collapse-{{ $loop->index }}" class="panel-collapse collapse in">
									<div class="panel-body">
						@endif
					@endforeach
					@if(!$column->isHidden($pageType))
						<div class="form-group" class="{{ $column->name }}">
							<!-- Tooltip -->
							@if(!empty($column->options['tooltip']))
								<a href="#" data-toggle="tooltip" title="{{ $column->options['tooltip'] }}">
									<span class="glyphicon glyphicon-info-sign"> </span>
								</a>&nbsp;
							@endif
							<label for="crudkit-field-{{ $column->name }}">{{ $column->label }}</label>
							<!-- Required -->
							@if(!empty($column->options['required']) && $column->options['required'] == true) 
								<span class="error" style="color:red">*</span> 
							@endif
							<!-- Description -->
							@if(!empty($column->options['description']))
								<div class="crudkit-description">{{$column->options['description']}}</div>
							@endif
							@php
								$inputAttributes = $htmlInputAttributes[$column->name];
								$fieldvalue = empty($record[$column->name]) ? '' : $record[$column->name];
							@endphp
							<div>
								@if(isset($manyToOneValues[$column->name]))
									<select id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control"{!! $inputAttributes !!}>
										@foreach($manyToOneValues[$column->name] as $manyToOneValue)
											<option value="{{ $manyToOneValue }}" @if($fieldvalue === $manyToOneValue) {{'selected'}} @endif>{{ $manyToOneValue }}</option>
										@endforeach
									</select>
								@else
									@if($column->type === 'text')
										<input type="text" value="{{ $fieldvalue }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-text"{!! $inputAttributes !!}/>
									@endif
									@if($column->type === 'textlong')
										<textarea id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-textarea" rows="5"{!! $inputAttributes !!}>{{ $fieldvalue }}</textarea>
									@endif						
									@if($column->type === 'email')
										<input type="text" value="{{ $fieldvalue }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-email"{!! $inputAttributes !!}/>
									@endif 						
									@if($column->type === 'integer')
										<input type="number" value="{{ str_replace(',','.',$fieldvalue) }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-integer"{!! $inputAttributes !!}/>
									@endif	
									@if($column->type === 'decimal')
										<input type="number" value="{{ str_replace(',','.',$fieldvalue) }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-decimal"{!! $inputAttributes !!}/>
									@endif									
									@if($column->type === 'enum')
										<select id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control"{!! $inputAttributes !!}>
											@foreach($column->options['enum'] as $key => $value)
												<option value="{{ $key }}" @if($key === $fieldvalue) {{'selected'}}@endif>{{ $value }}</option>
											@endforeach
										</select>
									@endif						
									@if($column->type === 'datetime')
										<input type="text" value="{{ $fieldvalue }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-datetime"{!! $inputAttributes !!}/>
									@endif					
									@if($column->type === 'date')
										<input type="text" value="{{ $fieldvalue }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-date"{!! $inputAttributes !!}/>
									@endif					
									@if($column->type === 'time')
										<input type="text" value="{{ $fieldvalue }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-time"{!! $inputAttributes !!}/>
									@endif												
									@if($column->type === 'boolean')
										<input type="checkbox" @if($fieldvalue === $texts['yes']) {{ 'checked' }} @endif id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="crudkit-checkbox checkbox validate-boolean"{!! $inputAttributes !!}/>
										<label for="crudkit-field-{{ $column->name }}" class="crudkit-checkbox-label"></label>
									@endif
									@if($column->type === 'image')
										<p>
											<input type="file" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="validate-image"{!! $inputAttributes !!}/>
										</p>
										@if($pageType === 'update')
											<p>	
												<input type="checkbox" id="crudkit-field-{{ $column->name }}___DELETEBLOB" name="{{ $column->name }}___DELETEBLOB" class="checkbox validate-boolean" style="display:inline;"/>
												<span for="crudkit-field-{{ $column->name }}___DELETEBLOB">Daten löschen</span>
											</p>
										@endif
										@if(!empty($fieldvalue))
											<img src="data:image;base64,{{$fieldvalue}}" />
										@else
											<span><code class="bg-warning text-primary">0 KB</code></span>
										@endif
									@endif
									@if($column->type === 'blob')
										<p>
											<input type="file" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="validate-blob"{!! $inputAttributes !!}/>
										</p>
										@if($pageType === 'update')	
											<p>
												<input type="checkbox" id="crudkit-field-{{ $column->name }}___DELETEBLOB" name="{{ $column->name }}___DELETEBLOB" class="checkbox validate-boolean" style="display:inline;"/>
												<span for="crudkit-field-{{ $column->name }}___DELETEBLOB">Daten löschen</span>
											</p>
										@endif
										@if(!empty($fieldvalue))<span><i>Aktuelle Daten:</i> <code class="bg-warning text-primary">{{$fieldvalue}}</code></span>@endif
									@endif
								@endif
							</div>
						</div>
					@endif
					@foreach($sections as $section)
						@if($section->to === $column->name)
									</div>
								</div>
							</div>
						@endif
					@endforeach
				@endforeach
			</dl>
			<!-- Save / Cancel Button -->
			<div class="form-group pull-right">
			@if($pageType === 'create')
				<button type="submit" form="create-form" class="btn btn-primary btn-lg crudkit-button"><i class="fa fa-save"></i> &nbsp;{{$texts['save']}}</button>
			@endif
			@if($pageType === 'update')
				<button type="submit" form="update-form" class="btn btn-primary btn-lg crudkit-button"><i class="fa fa-save"></i> &nbsp;{{$texts['save']}}</button>
			@endif
			<button class="btn btn-danger btn-lg crudkit-button"><i class="fa fa-cancel"></i> &nbsp;{{$texts['cancel']}}</button>
			</div>
		</form>
@endsection