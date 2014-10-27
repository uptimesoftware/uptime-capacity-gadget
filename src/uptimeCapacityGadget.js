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
		var chartType = null;
		var refreshInterval = 30;
		var chartTimer = null;
		var api = new apiQueries();

		var textStyle = {
			fontFamily : "Verdana, Arial, Helvetica, sans-serif",
			fontSize : "9px",
			lineHeight : "11px",
			color : "#565E6C"
		};

		if (typeof options == "object") {
			dimensions = options.dimensions;
			chartDivId = options.chartDivId;
			chartType = options.chartType;
			elementId = options.elementId;
			refreshInterval = options.refreshInterval;
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
		    $.ajax({
		        'async': true,
		        'global': false,
		        'url': '/dummydata.json',
		        'dataType': "json",
		        'success': function (data) {

		        	$.each(data, function(index, value) {
		            	chart.addSeries({
		            		name: value[0],
		            		data: value[1]
						});
						
						firstPoint = value[1][0];

						valueLength = value[1].length - 1;
						lastPoint = value[1][valueLength];
		        	});

		        	timeseries = data[0][1];


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

		        	capacityCap = 100;


		        	LineOfBestFit = [];

		        	current_Xvalue = firstPoint[1];
		        	current_Yvalue = firstPoint[0];

		        	while(current_Xvalue < capacityCap)
		        	{
		        		current_Yvalue = current_Yvalue + yDelta;
		        		current_Xvalue = current_Xvalue + xDelta;
		        		LineOfBestFit.push([current_Yvalue, current_Xvalue]);
		        	}



		        	doomsday = LineOfBestFit[LineOfBestFit.length - 1];//last point
		        	
		        	CapacityLine = [
		        					[firstPoint[0], capacityCap],
									[lastPoint[0], capacityCap],
									[doomsday[0], capacityCap]

								];





		        	chart.addSeries({
		        		name: "Capacity",
		        		data: CapacityLine
		        	});

					chart.addSeries({
		        		name: "Usage",
		        		data: LineOfBestFit
		        	});

		        	clearStatusBar();
					dataLabelsEnabled = true;
					chart.hideLoading();
		        }
	    	});	
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