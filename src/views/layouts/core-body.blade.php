@extends('crudkit::layouts.core')
@section('core-body')
@php
	$texts = Alddesign\Crudkit\Classes\DataProcessor::getTexts();
@endphp
<div class="wrapper">
    <header class="main-header">
        <a href="{{ url(config('crudkit.app_name_url', 'app')) }}" class="logo">
            {{ config('crudkit.app_name', 'CRUDKit') }}
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <div class="navbar-custom-menu">
            </div>
        </nav>
    </header>
	<!-- Menu -->
	@if(!empty($pageMap))
		<aside class="main-sidebar">
			<section class="sidebar">
				<ul class="sidebar-menu">
					<li class="header">{{ $texts['pages'] }}</li>
					<!-- Pages -->
					@foreach ($pageMap['pages'] as $menuPageId => $menuPageName) 
						<li role="presentation" @if( $menuPageId === $pageId)class="active"@endif>
							<a href="{{ URL::action('\Alddesign\Crudkit\Controllers\AdminPanelController@listView', ['page-id' => $menuPageId]) }}"><i class="fa fa-lg fa-book"></i> &nbsp;{{ $menuPageName }}</a>
						</li>
					@endforeach
					<!-- Category Pages -->
					@foreach ($pageMap['category-pages'] as $category => $categoryPages) 
						<li class="treeview">
							<a href="#"><span>{{$category}}</span> <i class="fa fa-angle-left pull-right"></i></a>
							<ul class="treeview-menu">
							@foreach($categoryPages as $categoryPageId => $categoryPageName)
								<li role="presentation">
									<a href="{{ URL::action('\Alddesign\Crudkit\Controllers\AdminPanelController@listView', ['page-id' => $categoryPageId]) }}"><i class="fa fa-lg fa-book"></i> &nbsp;{{ $categoryPageName }}</a>
								</li>
							@endforeach
							</ul>
						</li>
					@endforeach
					<!-- User -->
					@if(session('crudkit-logged-in', false) === true)
						<li class="header">
							@if(session('crudkit-admin-user', false))
								<i class="fa fa-lg fa-user-circle"></i>
							@else
								<i class="fa fa-lg fa-user"></i>
							@endif
							&nbsp;
							<span id="">{{session('crudkit-username', '')}}</span>
						</li>
						<li role="presentation">
							<a href="{{ URL::action('\Alddesign\Crudkit\Controllers\AdminPanelController@logout') }}"> <i class="fa fa-lg fa-sign-out"></i> &nbsp;Logout</a>
						</li>
					@endif
				</ul>
			</section>
		</aside>
	@endif
    <div class="content-wrapper">
        @yield('core-admin-panel')
    </div>
    <footer class="main-footer">
		<div class="pull-right hidden-xs">
			<b>{{ $texts['version'] }}</b> {{ config('crudkit.version') }}
		</div>
	  {!! $texts['footer_html'] !!}
  </footer>
</div>

@endsection