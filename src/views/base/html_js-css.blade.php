{{--CRUDKit dynamic JS --}}
<script type="text/javascript" class="crudkit-js"> 
var crudkit = 
{
	token : '{!! csrf_token() !!}', 
	language : '{!! config("crudkit.language", "") !!}', 
	pageId : '{!! $pageId !!}',
	pageType : '{!! $pageType !!}',
	record : {!! json_encode($record) !!}
};
</script>
{{--CRUDKit dynamic CSS --}}
@if(!empty(config('crudkit.fontsize',null)))
<style type="text/css">
	body.crudkit-body div.content-wrapper, 
	body.crudkit-body aside.main-sidebar, 
	body.crudkit-body aside.main-sidebar li.header 
	{
		font-size: {{ config('crudkit.fontsize') }}; 
	}
	</style>
@endif
{{-- Custom user CSS --}}
@foreach($css as $c) 
	@if(empty($c['pageTypes']) || in_array($pageType, $c['pageTypes'], true)) 
		<link href="{{ $j['url'] }}" rel="stylesheet" class="crudkit-custom-css" />
	@endif
@endforeach
{{-- Custom user JS --}}
@foreach($js as $j) 
	@if(empty($j['pageTypes']) || in_array($pageType, $j['pageTypes'], true)) 
		<script src="{{ $j['url'] }}" type="text/javascript" class="crudkit-custom-js"></script> 
	@endif
@endforeach
