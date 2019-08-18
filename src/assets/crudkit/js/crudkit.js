$(document).ready(crudkitInit);

function crudkitInit()
{
	crudkitResetUpdateForm();
	crudkitInitFilters();
	crudkitValidateCreateUpdateForm(); //Initialising Jquery Validation Plugin on the form
	crudkitInitDatepicker(); //Initialising Jquery UI Datepicker on certain fields
	crudkitInitQrCodeTooltip();
}

function crudkitInitQrCodeTooltip()
{
	var tooltip = $('#crudkit-qrcode-tooltip');

	if(tooltip.length !== 1)
	{
		return;
	}

	tooltip.tooltip(
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
	validationForm = $('.create-update-form');
	if(validationForm.length !== 1)
	{
		return;
	}
	
	validationForm.validate
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
		'Geben Sie bitte ein gültiges Datum im Format tt.mm.yyy ein.'
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
	var datepickerFields = $('form .validate-date');
	if(datepickerFields.length !== 1)
	{
		return;
	}
	
	datepickerFields.datepicker
	(
		{
			dateFormat : 'dd.mm.yy'
		}
	);
}