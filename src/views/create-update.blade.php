@extends('crudkit::base.content')
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
					@php
						$inputAttributes = $htmlInputAttributes[$column->name];
						$fieldvalue = isset($record[$column->name]) ? $record[$column->name] : '';
						$options = $column->options;
						$validateEmail = isset($options['email']) && $options['email'] ? ' validate-email' : '';
						$customAjaxValue = ($column->isCustomAjax && isset($customAjaxValues[$column->name])) ? $customAjaxValues[$column->name] : [$fieldvalue,''];
					@endphp
					@foreach($sections as $section)
						@if($section->from === $column->name)
							<div class="panel panel-primary">
								<div class="panel-heading" data-toggle="collapse" href="#collapse-{{ $loop->index }}">
									<div class="panel-title">{{ $section->title }}</div>
								</div>
								<div id="collapse-{{ $loop->index }}" class="panel-collapse collapse @if(!$section->collapsed) {{'in'}} @endif">
									<div class="panel-body">
						@endif
					@endforeach
					@include('crudkit::card-create-update_lookups-field', ['position' => 'before-field'])
					@if(!$column->isHidden($pageType))
						<div class="form-group" class="{{ $column->name }}">
							{{-- Tooltip --}}
							@if(!empty($options['tooltip']))
								<a href="#" data-toggle="tooltip" title="{{ $options['tooltip'] }}">
									<span class="glyphicon glyphicon-info-sign"> </span>
								</a>&nbsp;
							@endif
							<label for="crudkit-field-{{ $column->name }}">{{ $column->label }}</label>
							{{-- Required --}}
							@if(!empty($options['required']) && $options['required'] == true) 
								<span class="error" style="color:red">*</span> 
							@endif
							{{-- Description --}}
							@if(!empty($options['description']))
								<div class="crudkit-description">{{$options['description']}}</div>
							@endif
							<div>
								@include('crudkit::card-create-update_lookups-field', ['position' => 'to-field'])
								@if($column->isCustomAjax)
									@include('crudkit::create-update_custom-ajax')
								@elseif($column->isManyToOne)
									@include('crudkit::create-update_manytoone')
								@else
									@if($column->type === 'text')
										@if(isset($options['textarea']) && $options['textarea'])
											<textarea id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-textarea{{$validateEmail}}" rows="5"{!! $inputAttributes !!}>{{ $fieldvalue }}</textarea>
										@else
											<input type="text" value="{{ $fieldvalue }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-text{{$validateEmail}}"{!! $inputAttributes !!}/>
										@endif
									@endif												
									@if($column->type === 'integer')
										<input type="number" value="{{ str_replace(',','.',$fieldvalue) }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-integer"{!! $inputAttributes !!}/>
									@endif	
									@if($column->type === 'decimal')
										<input type="number" value="{{ str_replace(',','.',$fieldvalue) }}" id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control validate-decimal"{!! $inputAttributes !!}/>
									@endif									
									@if($column->type === 'enum')
										<select id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control"{!! $inputAttributes !!}>
											@foreach($options['enum'] as $key => $value)
												<option value="{{$key}}" @if(strval($key) == strval($fieldvalue)){{'selected'}}@endif>{{ $value }}</option>
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
										<select id="crudkit-field-{{ $column->name }}" name="{{ $column->name }}" class="form-control"{!! $inputAttributes !!}>
											<option value="0" @if($fieldvalue[0] === false) {{'selected'}}@endif>{{ $texts['no'] }}</option>
											<option value="1" @if($fieldvalue[0] === true) {{'selected'}}@endif>{{ $texts['yes'] }}</option>
										</select>
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
											<img src="{!! $fieldvalue !!}" />
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
						@include('crudkit::card-create-update_lookups-field', ['position' => 'after-field'])
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
			{{-- Save/Cancel Button --}}
			<div class="form-group pull-right">
			@if($pageType === 'create')
				<button type="submit" form="create-form" class="btn btn-primary btn-lg crudkit-button"><i class="fa fa-save"></i> &nbsp;{{$texts['save']}}</button>
			@endif
			@if($pageType === 'update')
				<button type="submit" form="update-form" class="btn btn-primary btn-lg crudkit-button"><i class="fa fa-save"></i> &nbsp;{{$texts['save']}}</button>
			@endif
			<a href="{{ url()->previous() }}" class="btn btn-danger btn-lg crudkit-button"><i class="fa fa-times"></i> &nbsp;{{$texts['cancel']}}</a>
			</div>
		</form>
@endsection