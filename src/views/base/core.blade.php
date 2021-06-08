@extends('crudkit::base.html')
@section('core')
<div class="wrapper">
    <header class="main-header" id="crudkit-header">
        <a href="{{ url(config('crudkit.app_name_url', 'app')) }}" class="logo">{{ config('crudkit.app_name', 'CRUDKit') }}</a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <div class="navbar-custom-menu">
            </div>
        </nav>
    </header>

	{{-- MENU --}}
	@if(!empty($pageMap))
		<aside class="main-sidebar" id="crudkit-menu">
			<section class="sidebar">
				<ul class="sidebar-menu" data-widget="tree">
					{{-- Pages --}}
					@if(!empty($pageMap['pages']))
						<li class="header">
							<i class="fa fa-lg fa-book"></i> 
							&nbsp;{{ $texts['pages'] }}
						</li>
						@foreach ($pageMap['pages'] as $item) 
						<li role="presentation" @if($item['id'] === $pageId)class="active"@endif>
							<a href="{{ $item['url'] }}"> &nbsp;{{ $item['name'] }}</a>
						</li>
						@endforeach
					@endif
					{{-- Category Pages --}}
					@foreach ($pageMap['category-pages'] as $category => $categoryPages) 
					<li class="treeview">
						<a href="#">
							<i class="fa fa-lg fa-{{$pageMap['category-icons'][$category]}}"></i>
							&nbsp; {{$category}}
							<i class="fa fa-angle-left pull-right"></i>
						</a>
						<ul class="treeview-menu">
						@foreach($categoryPages as $item)
							<li role="presentation" @if($item['id'] === $pageId)class="active"@endif>
								<a href="{{ $item['url'] }}"> &nbsp;{{ $item['name'] }}</a>
							</li>
						@endforeach
						</ul>
					</li>
					@endforeach
					{{-- User --}}
					@if(session('crudkit-logged-in', false) === true)
						<li class="header">
							@if(session('crudkit-admin-user', false))
								<i class="fa fa-lg fa-user-circle"></i>
							@else
								<i class="fa fa-lg fa-user"></i>
							@endif
							&nbsp;
							<span id="">{{session('crudkit-userid', '')}}</span>
						</li>
						<li role="presentation">
							<a href="{{ URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@logout') }}"> <i class="fa fa-lg fa-sign-out"></i> &nbsp;Logout</a>
						</li>
					@endif
					{{-- Theme --}}
					@if(config('crudkit.theme_selector', false))
						<li class="header">
							<i class="fa fa-lg fa-paint-brush"></i> &nbsp;Theme
						</li>
						<form id="crudkit-theme-form" method="post" action="{{action('\Alddesign\Crudkit\Controllers\CrudkitController@setTheme')}}" enctype="multipart/form-data" style="visibility:hidden;">
							<input type="hidden" name="_token" value="{{ csrf_token() }}" />
							<li role="presentation">
								<a href="#" style="padding: 0;">
								<select class="form-control crudkit-theme-change" name="skin" id="crudkit-skin-select" autocomplete="off">
									<option value="{{config('crudkit.skin','')}}" selected>{{config('crudkit.skin','')}}</option>
									<option value="blue">blue</option>
									<option value="blue-light">blue-light</option>
									<option value="yellow">yellow</option>
									<option value="yellow-light">yellow-light</option>
									<option value="green">green</option>
									<option value="green-light">green-light</option>
									<option value="purple">purple</option>
									<option value="purple-light">purple-light</option>
									<option value="red">red</option>
									<option value="red-light">red-light</option>
									<option value="black">black</option>
									<option value="black-light">black-light</option>
								</select>
								</a>
							</li>
							<li role="presentation">
								<a href="#" style="padding: 0;">
								<select class="form-control crudkit-theme-change" name="accent" id="crudkit-accent-select" autocomplete="off">
									<option value="{{config('crudkit.accent','')}}" selected>{{config('crudkit.accent','')}}</option>
									<option value="blue">blue</option>
									<option value="yellow">yellow</option>
									<option value="green">green</option>
									<option value="purple">purple</option>
									<option value="red">red</option>
									<option value="black">black</option>
									<option value="pink">pink</option>
								</select>
								</a>
							</li>	
						</form>
					@endif
					{{-- Style Selector --}}
					
				</ul>
			</section>
		</aside>
	@endif
    <div class="content-wrapper" id="curdkit-content-wrapper">
        @yield('content')
    </div>
    <footer class="main-footer" id="crudkit-footer">
	  {!! $texts['footer_html'] !!}
  	</footer>
</div>

@endsection