@extends('crudkit::base.core')
@section('content')
<section class="content-header" id="crudkit-content-header">
	<div class="row">
		<div class="col-md-4">
			<h2 id="crudkit-page-title">
				@if(in_array($pageType, ['card', 'chart', 'create', 'update']))
				<a href="{{ url()->previous() }}" class="btn btn-accent btn-sm" style="margin-top: -5px;"><i class="fa fa-chevron-left"></i></a>
				@endif
				<span id="crudkit-page-title-text">{{$pageName}}</span>
				<!-- QR Code -->
				@if(config('crudkit.show_qrcode', false))
				<span id="crudkit-qrcode-tooltip-container">
					<a id="crudkit-qrcode-tooltip" class="fg-accent" href="#" title="">
						<span class="fa fa-qrcode"></span>
					</a>
				</span>
				@endif
			</h2>
		</div>
		<div class="col-md-8">
			<div class="pull-right">
				@yield('action-buttons')
			</div>
		</div>
	</div>
	@if($pageTitleText !== '')
	<div class="row">
		<div class="col-md-6">
			<h3 id="crudkit-page-subtitle">
				<small>{!!$pageTitleText!!}</small>
			</h3>
		</div>
		<div class="col-md-6">
		</div>
	</div>
	@endif
</section>
<section class="content" id="crudkit-content">
	<div class="box bd-{{config('crudkit.accent', 'blue')}}" style='padding-top:15px'>
		<div class="box-body" >
			@yield('page-content')
		</div>
	</div> 
</section>
@endsection