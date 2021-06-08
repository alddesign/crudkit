$(document).ready(function()
{
	crudkitInitAjaxSelects();

	function crudkitInitAjaxSelects()
	{
		$('.crudkit-ajax-select-manytoone').each(function(index)
		{
			crudkitInitAjaxSelect($(this), 'api/ajax-many-to-one');
		});
	
		$('.crudkit-ajax-select-custom').each(function(index)
		{
			crudkitInitAjaxSelect($(this), 'api/ajax-custom');
		});
	}
	
	function crudkitInitAjaxSelect(select, url)
	{
		var columnName = select.attr('name');
		var selectId = select.attr('id');
		var initalValue = [select.attr('data-current-value0'), select.attr('data-current-value1')];
		var minInputLength = parseInt(select.attr("data-min-input-len"));
		var inputTimeout = parseInt(select.attr("data-input-timeout"));
		var manualInput = select.attr('data-manual-input') === '1';
		select.initialized = false;
	
		var crudkitFormatAjaxSelectionWrapper = crudkitFormatAjaxSelection.bind({select : select, selectId : selectId, initialValue : initalValue, initial: true});
	
		select.select2(
		{
			ajax: 
			{
				url: url,
				method: "POST",
				delay: inputTimeout,
				data: function(params)
				{
					return {pageId : crudkit.pageId, columnName : columnName, input: params.term, _token : crudkit.token};
				},
				processResults: function (data, params) 
				{
					return crudkitParseAjaxResults(data, params, manualInput);
				},
				cache: true
			},
			placeholder: 'Tippen zum suchen...',
			minimumInputLength: minInputLength,
			templateResult: crudkitFormatAjaxResult,
			templateSelection: crudkitFormatAjaxSelectionWrapper,
			allowClear: true,
			language: crudkit.language
		});	
	}
	
	function crudkitFormatAjaxResult(data)
	{
		if(data.loading) //no data yet
		{
			return null;
		}

		if(data.newTag)
		{
			return $(`<span><i>${data.id}</i></span>`);
		}
	
		var img = '';
		if(data.img)
		{
			img = `<img src="${data.img}"/>`;
		}
		var id = data.id.replace(new RegExp('('+escapeRegExp(data.input)+')', 'ig'), '<span class="bg-yellow">$1</span>');
		var text = data.text.replace(new RegExp('('+escapeRegExp(data.input)+')', 'ig'), '<span class="bg-yellow">$1</span>');
		var output = `<div class="crudkit-ajax-result">${img} &nbsp;&nbsp;<b>${id}</b> ${text}</div>`;
	
		return $(output);
	}
	
	function crudkitFormatAjaxSelection(data)
	{
		if(!data.input) //no data yet
		{
			if(this.initial)
			{
				//"this" has been assigned a speical value beforeon
				this.initial = false;
				return $(`<span><b>${this.initialValue[0]}</b> &nbsp;&nbsp;${this.initialValue[1]}</span>`);
			}
			else
			{
				//If its not the initial call its the clear
				return $(`<span>&nbsp;</span>`);
			}
		}

		if(data.newTag)
		{
			return $(`<span><i>${data.id}</i></span>`);
		}
	
		return $(`<span><b>${data.id}</b> &nbsp;&nbsp;${data.text}</span>`);
	}
	
	function crudkitParseAjaxResults(data, params, manualInput)
	{
		console.log(manualInput);
		if(!data)
		{
			return null;
		}
	
		try
		{data = JSON.parse(data); }
		catch
		{ return null; }
	
		if(data.type === 'error')
		{
			console.log("Ajax error: ", data.message, data.data);
			crudkitModal("Ajax error", data.message, 'danger'); 
			return {results : []};
		}
	
		if(data.type === 'result')
		{
			data.data.results.forEach(e => 
			{
				e.input = params.term;
			});

			if(manualInput)
			{
				data.data.results.push({id: params.term, text: params.term, input: params.term, newTag: true});
			}
			return data.data;
		}
	
		return null;
	}
});