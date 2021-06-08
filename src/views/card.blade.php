@extends('crudkit::base.content')

@include('crudkit::card_actions-top')

@section('page-content')
	<dl class="dl-horizontal">
		@foreach($columns as $columnName => $column)
			@php
				$value = $record[$columnName];
				$options = $column->options;
				$suffix = empty($options['suffix']) ? '' : $options['suffix'];
				$manyToOneUrl = ($column->isManyToOne && isset($manyToOneUrls[$columnName])) ? $manyToOneUrls[$columnName] : '';
				$valueManyToOne = ($column->isManyToOne && isset($manyToOneValues[$column->name][0])) ? $manyToOneValues[$column->name][0] : [$value,''];
				$customAjaxValue = ($column->isCustomAjax && isset($customAjaxValues[$columnName])) ? $customAjaxValues[$columnName] : [$value, ''];
				$valueEnum = isset($options['enum'][$value]) ? $options['enum'][$value] : $value;
			@endphp
			{{-- Section Start --}}
			@foreach($sections as $section)
				@if($section->from === $columnName)
					<div class="panel panel-primary">
						<div class="panel-heading" data-toggle="collapse" href="#collapse-{{ $loop->index }}">
							<div class="panel-title">{{ $section->title }}</div>
						</div>
						<div id="collapse-{{ $loop->index }}" class="panel-collapse collapse @if(!$section->collapsed) {{'in'}} @endif">
							<div class="panel-body">
				@endif
			@endforeach
			{{-- Lookups / Actions --}}
			@include('crudkit::card-create-update_lookups-field', ['position' => 'before-field'])
			@include('crudkit::card_actions-field', ['position' => 'before-field'])
			{{-- Fields --}}
			@if(!$column->isHidden('card'))
				<div class="form-group crudkit-card-field-wrapper1" id="crudkit-field-{{$columnName}}">
					{{-- Tooltip --}}
					@if(!empty($options['tooltip']))
						<a class="crudkit-tooltip" href="#" data-toggle="tooltip" title="{{ $options['tooltip'] }}">
							<span class="glyphicon glyphicon-info-sign"> </span>
						</a>&nbsp;
					@endif
					{{-- Label --}}
					<label>{{ $column->label }}</label>
					{{-- Description --}}
					@if(!empty($options['description']))
						<div class="crudkit-description">{{$options['description']}}</div>
					@endif
					{{-- Data --}}
					@if(in_array($column->type, ['text', 'textlong', 'integer', 'decimal', 'datetime', 'date', 'time'], true))
						<div class="well well-sm clearfix crudkit-card-field-wrapper2">
							@include('crudkit::card-create-update_lookups-field', ['position' => 'to-field'])
							@include('crudkit::card_actions-field', ['position' => 'to-field'])
							<span class="crudkit-card-field-wrapper3">
							@if($column->isCustomAjax)
								@if(!empty($column->link))
									<a href="{!! $column->link !!}"><b>{{$customAjaxValue[0]}}</b> {{$customAjaxValue[1]}} {{$suffix}}</a>
								@else
									<b>{{$customAjaxValue[0]}}</b> {{$customAjaxValue[1]}} {{$suffix}}
								@endif
							@elseif(!empty($column->link))
								<a href="{!! $column->link !!}"> {{$value}} {{$suffix}} </a>
							@elseif($column->isManyToOne && $manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}"> <b>{{$valueManyToOne[0]}}</b> {{$valueManyToOne[1]}} {{$suffix}} </a> 
							@elseif($column->isManyToOne && $manyToOneUrl == '')
								<b>{{$valueManyToOne[0]}}</b> {{$valueManyToOne[1]}} {{$suffix}} 
							@elseif(!empty($options['link']) && $options['link'])
								<a href="{{$value}}" target="_blank"> {{$value}} {{$suffix}} </a> 
							@elseif(!empty($options['email']) && $options['email'])
								<a href="mailto:{{$value}}" target="_blank"> {{$value}} {{$suffix}} </a>
							@elseif(!empty($options['imageUrl']) && $options['imageUrl'])
								<img src="{!! $value !!}" style="max-width: 100%;"/> 
							@else
								{{$value}} {{$suffix}}
							@endif
							</span>
						</div>
					@endif								
					@if($column->type === 'enum')
						<div class="well well-sm clearfix crudkit-card-field-wrapper2">
							@include('crudkit::card-create-update_lookups-field', ['position' => 'to-field'])
							@include('crudkit::card_actions-field', ['position' => 'to-field'])
							<span class="crudkit-card-field-wrapper3">
							@if(!empty($column->link))
								<a href="{!! $column->link !!}"> {{$valueEnum}} {{$suffix}} </a>
							@elseif($column->isManyToOne && $manyToOneUrl != '')
								<a href="{!! $manyToOneUrl !!}"> {{$valueEnum}} {{$suffix}} </a>  
							@else
								{{$valueEnum}} {{$suffix}}
							@endif
							</span>
						</div>
					@endif													
					@if($column->type === 'boolean')
						<div class="clearfix crudkit-card-field-wrapper2">
							@include('crudkit::card-create-update_lookups-field', ['position' => 'to-field'])
							@include('crudkit::card_actions-field', ['position' => 'to-field'])
							<span class="crudkit-card-field-wrapper3">
							@if(!empty($column->link))
								<a href="{!! $column->link !!}">{{$value[1]}} {{$suffix}}</a>
							@elseif($value[0] === true)
								<kbd style="background-color: #333; color: #eee; font-size: 1.7rem;">{{$value[1]}}</kbd>
							@else
								<kbd style="background-color: #eee; color: #111; font-size: 1.7rem;">{{$value[1]}}</kbd>
							@endif
							</span>
						</div>
					@endif
					@if($column->type === 'image')
						@if(!empty($value))
							<div class="well well-sm clearfix crudkit-card-field-wrapper2">
								@include('crudkit::card-create-update_lookups-field', ['position' => 'to-field'])
								@include('crudkit::card_actions-field', ['position' => 'to-field'])
								<span class="crudkit-card-field-wrapper3"><img src="{!! $value !!}" /></span>
							</div>
						@else
							<div class="clearfix crudkit-card-field-wrapper2">
								@include('crudkit::card-create-update_lookups-field', ['position' => 'to-field'])
								@include('crudkit::card_actions-field', ['position' => 'to-field'])
								<span class="crudkit-card-field-wrapper3"><code class="bg-warning text-primary">0 KB</code></span>
							</div>
						@endif
					@endif
					@if($column->type === 'blob')
						<div class="clearfix crudkit-card-field-wrapper2">
						<span class="crudkit-card-field-wrapper3">
						@if(!empty($value))	
							@include('crudkit::card-create-update_lookups-field', ['position' => 'to-field'])
							@include('crudkit::card_actions-field', ['position' => 'to-field'])
							<code class="bg-warning text-primary">{{$value}}</code>
						@else
							@include('crudkit::card-create-update_lookups-field', ['position' => 'to-field'])
							@include('crudkit::card_actions-field', ['position' => 'to-field'])
							<code class="bg-warning text-primary">0 KB</code>
						@endif
						</span>
						</div>
					@endif
				</div>
			@endif	
			{{-- Lookups / Actions --}}
			@include('crudkit::card-create-update_lookups-field', ['position' => 'after-field'])
			@include('crudkit::card_actions-field', ['position' => 'after-field'])
			{{-- Section End --}}
			@foreach($sections as $section)
				@if($section->to === $columnName)
							</div>
						</div>
					</div>
				@endif
			@endforeach
		@endforeach
		{{-- Actions Buttons Bottom --}}
		@include('crudkit::card_actions-bottom')
	</dl>
@endsection