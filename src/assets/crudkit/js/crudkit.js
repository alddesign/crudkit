var select2input = '';
$(document).ready(function()
{
	crudkitInitSelect2();
	crudkitResetUpdateForm();
	crudkitInitFilters();
	crudkitValidateCreateUpdateForm(); //Jquery Validation Plugin on create/update form
	crudkitInitDatepicker(); //Bootstrap Datepicker
	crudkitInitQrCodeTooltip();
	curdkitThemeChange();

	function curdkitThemeChange()
	{
		$('select.crudkit-theme-change').change(function()
		{
			var form = $('#crudkit-theme-form');

			form.submit();
		});
	}
	
	function crudkitInitSelect2()
	{
		$('.crudkit-select2').each(function(index)
		{
			var select = $(this);
			var manualInput = select.attr('data-manual-input') === '1';

			select.select2(
			{
				placeholder: 'Tippen zum suchen...',
				minimumInputLength: 1,
				allowClear : true,
				templateResult: crudkitFormatResult,
				templateSelection: crudkitFormatSelection,
				language : crudkit.language,
				tags: manualInput,
			});	
		});

		$('#crudkit-menu .crudkit-theme-change').each(function(index)
		{
			var select = $(this);
			select.select2(
			{
				dropdownCssClass: 'crudkit-menu-select-dropdown',
				minimumResultsForSearch: Infinity
			});
		});

		$('#crudkit-theme-form').css('visibility', '');
	}

	function crudkitFormatResult(data)
	{
		if(!data.element) //no data yet
		{
			select2input = data.text;
			return $(`<span><i>${data.text}</i></span>`);
		}

		var text = data.text.replace(new RegExp('('+escapeRegExp(select2input)+')', 'ig'), '<span class="bg-yellow">$1</span>');
		var output = `<span>${text}</span>`;
	
		return $(output);
	}

	function crudkitFormatSelection(data)
	{
		var output = data.selected ? `<span>${data.text}</span>` : `<i>${data.text}</i>`;
	
		return $(output);
	}

	function crudkitInitQrCodeTooltip()
	{
		var destination = $('#crudkit-qrcode-tooltip');
	
		if(destination.length !== 1)
		{
			return;
		}
	
		var value = window.location.href;
		var f = Math.floor(value.length * 2);
		var min = 90;
		var size = f < min ? min : f;

		var qr = new QRious(
		{
			value: value,
			size : size,
			padding: null
		}).toDataURL('image/png');
	
	
		destination.attr('title', `<img src='${qr}'/>`);
	
		destination.tooltip(
		{
			html : true,
			container : '#crudkit-qrcode-tooltip-container',
			placement : 'bottom'
		});
	}
	
	function crudkitInitFilters()
	{
		if($('#crudkit-filters').length !== 1)
		{
			return;
		}
		
		$(document).on('click', '.crudkit-filter-add-button', function(e)
		{
			e.preventDefault();
	
			var newFilter = $('#crudkit-filters #crudkit-filter-reference').clone();
			
			//Prepare new filter
			newFilter.removeAttr('id');
			newFilter.addClass('crudkit-filter');
			newFilter.appendTo($('#crudkit-filters'));
			
		}).on('click', '.crudkit-filter-remove-button', function(e)
		{
			$(this).parent().parent().parent().parent('div.crudkit-filter').remove();
	
			e.preventDefault();
			return false;
		});
	}
	
	function crudkitResetUpdateForm()
	{
		var updateForm = $('#update-form');
		var createForm = $('#create-form');
			
		if(updateForm.length === 1)
		{
			updateForm[0].reset();
		}
		
		if(createForm.length === 1)
		{
			createForm[0].reset();
		}
	}
	
	function crudkitValidateCreateUpdateForm()
	{
		var form = $('.create-update-form');
		if(form.length !== 1)
		{
			return;
		}
		
		crudkit.validator = form.validate
		(
			{
				debug : false,
				errorClass : 'alert alert-danger', //alert-danger is the bootstrap class
				highlight : function(element, errorClass)
				{
					$(element).removeClass(errorClass); //this looks ugly
					$(element).parent().addClass('has-error');
				},
				unhighlight : function(element, errorClass, validClass)
				{
					$(element).parent().removeClass('has-error');
				},
				submitHandler : function(form)
				{
					$(form).find('div.panel-title a.collapsed').click(); //We need to expand all collapsed panels and revalidate the form
					if($(form).valid())
					{
						form.submit(); //dont use $(form).submit();
					}
				}
				
			}
		); 
		
		$.validator.addMethod
		(
			'dateDE',
			function(value, element)
			{
				return this.optional(element) || /^(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]))\1|(?:(?:29|30)(\/|-|\.)(?:0?[1,3-9]|1[0-2])\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)0?2\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9])|(?:1[0-2]))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})$/.test(value);
			},
			'Geben Sie bitte ein gültiges Datum im Format tt.mm.yyyy ein.'
		);
		
		$.validator.addClassRules
		(
			{
				'validate-email':{email:true},
				'validate-time':{time:true},
				'validate-decimal':{number:true},
				'validate-integer':{digits:true}
			}
		);	
	}
	
	function crudkitInitDatepicker()
	{
		$('form .validate-date:not([readonly])').datepicker(
		{
			language : crudkit.language
		});
	}
});

function crudkitModal(title, text, accent = '')
{
	console.log("modal", text);
	var modal = `
	<div class="modal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-${accent} modal-content">
		<div class="modal-header">
			<h3 class="modal-title">${title}</h3>
		</div>
		<div class="modal-body">
			<p>${text}</p>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
		</div>
		</div>
	</div>
	</div>
	`;

	$(modal).modal();
}
	
function escapeRegExp(txt) 
{
	return txt.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
}