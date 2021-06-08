@if($hasFilters || $hasSearch)
	<div class="crudkit-filter-search-warning crudkit-row"> 
		@if($hasFilters)
			<div class="alert alert-warning">
				{!!$texts['filter_warning_html']!!}
			</div>	
		@endif
		@if($hasSearch)
			<div class="alert alert-warning">
				{!!$texts['search_warning_html']!!}
			</div>	
		@endif
	</div>
@endif