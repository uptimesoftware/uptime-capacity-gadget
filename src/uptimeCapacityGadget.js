if (typeof UPTIME == "undefined") {
	var UPTIME = {};
}

if (typeof UPTIME.UptimeCapacityGadget == "undefined") {
	UPTIME.UptimeCapacityGadget = function(options, displayStatusBar, clearStatusBar) {
		Highcharts.setOptions({
			global : {
				useUTC : false
			}
		});

		var dimensions = new UPTIME.pub.gadgets.Dimensions(100, 100);
		var chartDivId = null;
		var elementId = null;
		var metricType = null;
		var queryType = null;
		var chartTimer = null;
		var api = new apiQueries();
		var getMetricsPath = null;

		var textStyle = {
			fontFamily : "Verdana, Arial, Helvetica, sans-serif",
			fontSize : "9px",
			lineHeight : "11px",
			color : "#565E6C"
		};

		if (typeof options == "object") {
			dimensions = options.dimensions;
			chartDivId = options.chartDivId;
			metricType = options.metricType;
			queryType = options.queryType;
			elementId = options.elementId;
			getMetricsPath = options.getMetricsPath;
		}

		var dataLabelsEnabled = false;
		var chart = new Highcharts.Chart({
			chart : {
				renderTo: 'widgetChart',
                type: 'line',
                style: {fontFamily: 'Arial',
                    fontSize: '9px'},
                spacingTop: 10,
                spacingBottom: 10},
            	title: {text: ""},
            	credits: {enabled: false},
            	xAxis: {type: 'datetime',
    	            title: {enabled: true,
        	        text: ""}},
            	yAxis: {min: 0,
            	    title: {enabled: false,
                    text: ""}},
            	plotOptions: {spline: {marker: {enabled: false}},
                    areaspline: {marker: {enabled: false}}},
            	series: [],
		});

		function requestData() {

			var firstPoint = null;
			var lastPoint = null;
			var my_url = getMetricsPath + '&query_type=' + queryType + '&metricType='  + metricType + "&element=" + elementId;
		    $.ajax({
		        'async': true,
		        'global': false,
		        'url': my_url,
		        'dataType': "json",
		        'success': function (data) {

		        	$.each(data, function(index, value) {
		            	chart.addSeries({
		            		name: value['name'],
		            		data: value['series']
						});
						
						firstPoint = data[0]['series'][0];

						valueLength = data[0]['series'].length - 1;
						lastPoint = data[0]['series'][valueLength];


			        	timeseries = data[0]['series'];


			        	xDeltaTotal = 0;
			        	yDeltaTotal = 0;

			        	$.each(timeseries, function(index, value) {
			        		if (index >= 1)
			        		{
			        			xDelta = value[1] - timeseries[index - 1][1];
			        			yDelta = value[0] - timeseries[index - 1][0];
			        			xDeltaTotal = xDeltaTotal + xDelta;
			        			yDeltaTotal = yDeltaTotal + yDelta;
			        		}
			        	});


			        	xDelta = xDeltaTotal / timeseries.length;
			        	yDelta = yDeltaTotal / timeseries.length;

			        	capacityCap = data[0]['capacity'];

			        	LineOfBestFitForRealMetrics = [firstPoint, lastPoint];


			     		current_Xvalue = lastPoint[1];
			        	current_Yvalue = lastPoint[0];
			        	LineOfBestFitForEstimatedMetrics = [[current_Yvalue, current_Xvalue]];

			        	if ( current_Xvalue < capacityCap && xDelta > 0)
			        	{
				        	while(current_Xvalue < capacityCap)
				        	{
				        		current_Yvalue = current_Yvalue + yDelta;
				        		current_Xvalue = current_Xvalue + xDelta;
				        		if (current_Xvalue >= capacityCap)
				        		{
				        			LineOfBestFitForEstimatedMetrics.push([current_Yvalue, current_Xvalue]);
				        		}
				        	} 

			        	}
			        	doomsday = LineOfBestFitForEstimatedMetrics[LineOfBestFitForEstimatedMetrics.length - 1];//last point
			        	
			        	CapacityLine = [
			        					[firstPoint[0], capacityCap],
										[lastPoint[0], capacityCap],
										[doomsday[0], capacityCap]

									];



						countDowntillDoomsday(lastPoint, doomsday);

			        	chart.addSeries({
			        		name: "Capacity",
			        		data: CapacityLine
			        	});

						chart.addSeries({
			        		name: "Usage",
			        		data: LineOfBestFitForRealMetrics
			        	});

			        	chart.addSeries({
			        		name: "Estimated Usage",
			        		data: LineOfBestFitForEstimatedMetrics
			        	});


					});

		        	clearStatusBar();
					dataLabelsEnabled = true;
					chart.hideLoading();
		        }
	    	});	
		}


		function countDowntillDoomsday(startpoint, endpoint)
		{
			$("#countDownTillDoomsDay").html("");
			starttime = startpoint[0];
			endtime = endpoint[0];
			time_left =  (endtime - starttime);
			time_left_in_days = Math.round(time_left / 1000 / 60 / 60 / 24);
			$("#countDownTillDoomsDay").html("Days left till doomsday: " + time_left_in_days);
		}

		// public functions for this function/class
		var publicFns = {
			render : function() {
				chart.showLoading();
				requestData();
			},
			resize : function(dimensions) {
				chart.setSize(dimensions.width, dimensions.height);
			},
			stopTimer : function() {
				if (chartTimer) {
					window.clearTimeout(chartTimer);
				}
			},
			destroy : function() {
				chart.destroy();
			}
		};
		return publicFns; // Important: we need to return the public
		// functions/methods

	};
}