@if(count($records['records']) < $records['total'])
	@php
		$showFirst = ($pageNumber > 1);
		$showLast = ($pageNumber * $recordsPerPage) < $records['total'];
	@endphp
	<ul class="pagination">
		<!-- First / Previous -->
		@if($showFirst)
			<li><a href="{{$paginationUrls['first']}}">{{$texts['first']}}</a></li>
			<li><a href="{{$paginationUrls['previous']}}">{{$texts['prev']}}</a></li>
		@else 
			<li class="disabled"><span>{{$texts['first']}}</span></li>
			<li class="disabled"><span class="disabled">{{$texts['prev']}}</span></li>
		@endif
		
		<!-- Pages -->
		@if(isset($paginationUrls['predot'])) <li class="disabled"><span>...</span></li> @endif
		@foreach($paginationUrls['pages'] as $index => $url)
			<li class="@if($index == $pageNumber){{'active'}} @endif"><a href="{{$url}}">{{ $index }}</a></li>
		@endforeach
		@if(isset($paginationUrls['afterdot'])) <li class="disabled"><span>...</span></li> @endif
		<!-- Last / Next -->
		@if($showLast)
			<li><a href="{{$paginationUrls['next']}}">{{$texts['next']}}</a></li>
			<li><a href="{{$paginationUrls['last']}}">{{$texts['last']}}</a></li>
		@else
			<li class="disabled"><span>{{$texts['next']}}</span></li>
			<li class="disabled"><span>{{$texts['last']}}</span></li>
		@endif
	</ul>
@endif