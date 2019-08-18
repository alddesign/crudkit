var crudkitChart = null;
$(document).ready(crudkitChartInit);

function crudkitChartInit()
{
	var loadButton = $('#crudkit-chart-load-button');
	if(loadButton.length !== 1)
	{
		return;
	}
	
	loadButton.click(function(){crudkitLoadChart();});
}

function crudkitLoadChart()
{
	//Destroy old Chart
	if(crudkitChart !== null)
	{
		crudkitChart.destroy();
	}
	$('#crudkit-chart-loader').removeClass('hidden');
	$('#crudkit-save-chart-button').addClass('disabled');

	//Build Parameters
	var xAxisColumn = $('#crudkit-chart-x').val();
	var yAxisColumn = $('#crudkit-chart-y').val();
	var yAxisAggregation = $('#crudkit-chart-aggregation').val();
	var chartParameters = 
	{
		'x-axis-column' : xAxisColumn,
		'y-axis-column' : yAxisColumn,
		'y-axis-aggregation' : yAxisAggregation
	}
	
	//Build Filter
	var filterParameters = {};
	var filters = $('.crudkit-filter');
	var filterField = '';
	var filterOperator = '';
	var filterValue = '';
	$.each(filters, function(key, filter)
	{
		filter = $(filter);
		filterField = filter.find('.crudkit-filter-field').val();
		filterOperator = filter.find('.crudkit-filter-operator').val();
		filterValue = filter.find('.crudkit-filter-value').val();
		if(filterField !== '')
		{
			filterParameters['ff-' + key] = filterField;
			filterParameters['fo-' + key] = filterOperator;
			filterParameters['fv-' + key] = filterValue;
		}
	});

	var postData = $.extend({}, _crudkitGetChartDataUrlParameters, chartParameters, filterParameters); //Concats these two objects into one
	var result = $.post(_crudkitGetChartDataUrl, postData);
	result.done(function(data, statusText) //Success
	{
		crudkitDrawChart(JSON.parse(data));
	});
	result.fail(function(data, statusText) //Failed
	{
		$('#crudkit-chart-error').find('.modal-title').html('Error...');
		$('#crudkit-chart-error').find('.modal-body').html(data.responseText);
		$('#crudkit-chart-error').modal('show');
	});
}

function crudkitDrawChart(data)
{
	var chart = document.getElementById('crudkit-chart').getContext('2d');
	if(chart.length == 0)
	{
		return;
	}
	
	var colors = crudkitLoadChartColors(data.labels.length);
	//	$("html, body").animate({ scrollTop: $(document).height() }, 1000); //Scroll to bottom. If you dont do this, ChartJs will not fade in the cart nicely
	
	crudkitChart = new Chart(chart,
	{
		type : 'bar',
		data :
		{
			labels : data.labels,
			datasets: 
			[{
				label: data.title,
				data: data.values,
				backgroundColor : 'rgba(54, 127, 169, 0.7)'/*colors[0]*/,
				borderColor : 'rgba(54, 127, 169, 1)'/*colors[1]*/,
				borderWidth: 2
			}]
		},
		options :
		{
			responsive : true,
			animation :
			{
				duration : 1500,
				easing : 'linear',
				onComplete : function()
				{
					$('#crudkit-save-chart-button').attr('href', crudkitChart.toBase64Image());
					$('#crudkit-save-chart-button').removeClass('disabled');
				}
			},
			scales: 
			{ 
				xAxes : 
				[{
					scaleLabel : 
					{
						display : true,
						fontSize : 16,
						fontColor : '#3c8dbc',
						labelString : data.axisLabels.x
					}
				}],
				yAxes : 
				[{ 
					scaleLabel : 
					{
						display : true,
						fontSize : 16,
						fontColor : '#3c8dbc',
						labelString : data.axisLabels.y
					},
					ticks : 
					{ 
						beginAtZero : true
					} 
				}] 
			}
		}
	});
	
	$('#crudkit-chart-loader').addClass('hidden');

}

function crudkitLoadChartColors(numberOfGroups)
{
	var bgColors = [];
	var borderColors = [];
	
	var opacity = 0.5;
	var availableBgColors = 
	[
		'rgba(27, 188, 155, ' + opacity + ')',
		'rgba(45, 204, 112, ' + opacity + ')',
		'rgba(53, 152, 219, ' + opacity + ')',
		'rgba(155, 88, 181, ' + opacity + ')',
		'rgba(52, 73, 94, ' + opacity + ')',
		'rgba(231, 196, 15, ' + opacity + ')',
		'rgba(231, 126, 35, ' + opacity + ')',
		'rgba(232, 76, 61, ' + opacity + ')',
		'rgba(236, 240, 241, ' + opacity + ')',
		'rgba(149, 165, 165, ' + opacity + ')'
	];
	
	var availableBorderColors = 
	[
		'rgba(22, 160, 134, ' + opacity + ')',
		'rgba(39, 174, 97, ' + opacity + ')',
		'rgba(42, 128, 185, ' + opacity + ')',
		'rgba(143, 68, 173, ' + opacity + ')',
		'rgba(45, 62, 80, ' + opacity + ')',
		'rgba(243, 156, 17, ' + opacity + ')',
		'rgba(210, 83, 0, ' + opacity + ')',
		'rgba(192, 57, 43, ' + opacity + ')',
		'rgba(190, 195, 199, ' + opacity + ')',
		'rgba(126, 140, 141, ' + opacity + ')'
	];
	
	for(var c = 0; c <= numberOfGroups; c++)
	{
		bgColors.push(availableBgColors[c % (availableBgColors.length - 1)]);
		borderColors.push(availableBorderColors[c % (availableBorderColors.length - 1)]);
	}
	
	return [bgColors, borderColors];
}


