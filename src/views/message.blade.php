@extends('crudkit::core-admin-panel')
@section('page-content')
	<div class="alert alert-{{$type}}">
		<b>{{$title}}</b>
	</div>
	<div class="panel panel-{{$type}}">
		<div class="panel-body">
			{{$message}}
		</div>
    </div>
	<p>
		<div>
			<a href="{{URL::previous()}}" class="btn btn-default"><i class="fa fa-lg fa-arrow-left"></i> &nbsp;{{'Zurück'}}</a>
			<a href="{{URL::to(config('crudkit.app_name_url', 'app'))}}" class="btn btn-default"><i class="fa fa-lg fa-home"></i> &nbsp;{{'Startseite'}}</a>
		</div>
	</p>
@endsection