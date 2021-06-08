@extends('crudkit::base.content')
@section('page-content')
	<div class="alert alert-{{$type}}">
		<b>{{$title}}</b>
	</div>
	<div class="panel panel-{{$type}}">
		<div class="panel-body">
			{{$message}}{!!$messageHtml!!}
		</div>
    </div>
	<p>
		<div>
			<a href="{{URL::previous()}}" class="btn btn-default"><i class="fa fa-lg fa-arrow-left"></i> &nbsp;{{$texts['back']}}</a>
			<a href="{{URL::to(config('crudkit.app_name_url', 'app'))}}" class="btn btn-default"><i class="fa fa-lg fa-home"></i> &nbsp;{{$texts['startpage']}}</a>
		</div>
	</p>
@endsection