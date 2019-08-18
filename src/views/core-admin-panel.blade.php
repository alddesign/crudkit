@extends('crudkit::layouts.core-body')
@section('core-admin-panel')
<section class="content-header">
	<div class="row">
		<div class="col-md-4">
			<h2 id="crudkit-page-title">
				@if(isset($listPageUrl) && $listPageUrl !== '')
				<a href="{{$listPageUrl}}" class="btn btn-primary btn-sm" style="margin-top: -5px;"><i class="fa fa-chevron-left"></i></a>
				@endif
				{{$pageName}}
				<!-- QR Code -->
				@if(isset($qrCode) && $qrCode !== '')
				<span id="crudkit-qrcode-tooltip-container">
					<a id="crudkit-qrcode-tooltip" href="#" title="{{$qrCode}}">
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
			<h3 style="margin-top: 0px">
				<small>{!!$pageTitleText!!}</small>
			</h3>
		</div>
		<div class="col-md-6">
		</div>
	</div>
	@endif
</section>
<section class="content">
	<div class="box box-primary" style='padding-top:15px'>
		<div class="box-body" >
			@yield('page-content')
		</div>
	</div> 
</section>
@endsection