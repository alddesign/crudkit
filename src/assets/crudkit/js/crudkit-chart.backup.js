var crudkitChart = null;
$(document).ready(crudkitChartInit);

function crudkitChartInit()
{
	var crudkitChartLoad = $('#crudkit-chart-load');
	if(crudkitChartLoad.length === 1)
	{
		$('#crudkit-chart-load').click(function(){loadChart();});
	}
}

function loadChart()
{
	var chart = $('#crudkit-chart');
	if(chart.length == 0)
	{
		return;
	}
	
	if(crudkitChart !== null)
	{
		crudkitChart.destroy();
	}
	
	var data = 	loadChartData();
	var colors = loadChartColors(data[1].length);
	var axisLabels = loadChartAxisLabels();
	
	crudkitChart = new Chart(chart,
	{
		type : 'bar',
		data :
		{
			labels : data[0],
			datasets: 
			[{
				label: 'CRUDKit Digram View',
				data: data[1],
				backgroundColor : colors[0],
				borderColor : colors[1],
				borderWidth: 2
			}]
		},
		options :
		{
			responsive : true,
			scales: 
			{ 
				xAxes : 
				[{
					scaleLabel : 
					{
						display : true,
						fontSize : 16,
						labelString : axisLabels[0]
					}
				}],
				yAxes : 
				[{ 
					scaleLabel : 
					{
						display : true,
						fontSize : 16,
						labelString : axisLabels[1]
					},
					ticks : 
					{ 
						beginAtZero : true
					} 
				}] 
			}
		}
	}
	);
}

function loadChartAxisLabels()
{
	var chartXText = $('#crudkit-chart-x').find(':selected').text();
	var chartYText = $('#crudkit-chart-y').find(':selected').text();
	var charAggregation = $('#crudkit-chart-aggregation').val();
	var charAggregationText = $('#crudkit-chart-aggregation').find(':selected').text();
	
	switch(charAggregation)
	{
		case 'count': 
			chartYText = charAggregationText;
			break;
		case 'sum' 	: 
			chartYText = charAggregationText + ' (' + chartYText + ')';
			break;
		case 'avg' 	: 
			chartYText = charAggregationText + ' (' + chartYText + ')';
			break;
		case 'min' 	: 
			chartYText = charAggregationText + ' (' + chartYText + ')';
			break;
		case 'max' 	: 
			chartYText = charAggregationText + ' (' + chartYText + ')';
			break;
	}
	
	return [chartXText, chartYText];
}

function loadChartData()
{
	var labels = [];
	var values = [];
	
	var chartX = $('#crudkit-chart-x').val();
	var chartY = $('#crudkit-chart-y').val();
	var charAggregation = $('#crudkit-chart-aggregation').val();

	var avgHelper = [];
	var minHelper = [];
	var maxHelper = [];
	var record;
	var chartXValue;
	var chartYValue;
	var chartYValueIsNumber;
	var chartYValueFloat;
	var index;
	
	for(var c = 0; c < crudkitRecords.length; c++)
	{
		record = $(crudkitRecords[c]);
		chartXValue = record.attr(chartX);
		chartYValue = record.attr(chartY);
		
		if(!labels.includes(chartXValue))
		{
			labels.push(chartXValue);
			values.push(0.0);
			avgHelper.push(0);
			minHelper.push(null);
			maxHelper.push(null);
		}
		index = labels.indexOf(chartXValue);
		
		chartYValueIsNumber = !isNaN(chartYValue);
		chartYValueFloat = parseFloat(chartYValue);
		switch(charAggregation)
		{
			case 'count': 
				values[index] += 1.0; 
				break;
			case 'sum' 	: 
				if(chartYValueIsNumber){ values[index] += chartYValueFloat;}
				break;
			case 'avg' 	: 
				avgHelper[index] += 1;
				if(chartYValueIsNumber){ values[index] += chartYValueFloat;}
				if(c == (crudkitRecords.length - 1)) //Last round, calculate average value
				{ 
					for(var c2 = 0; c2 < values.length; c2++)
					{
						values[c2] /= avgHelper[c2];
					}
				}
				break;
			case 'min' 	: 
				if(chartYValueIsNumber && (chartYValueFloat < minHelper[index] || minHelper[index] === null))
				{ 
					values[index] = chartYValueFloat;
					minHelper[index] = chartYValueFloat;
				}
				break;
			case 'max' 	: 
				if(chartYValueIsNumber && (chartYValueFloat > maxHelper[index] || maxHelper[index] === null))
				{ 
					values[index] = chartYValueFloat;
					maxHelper[index] = chartYValueFloat;
				}
				break;
		}
		
	}
	
	return [labels, values];
}

function loadChartColors(numberOfGroups)
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