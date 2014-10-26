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

			var firstdate = null;
			var lastdate = null;
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
						
						firstdate = value[1][0][0];

						valueLength = value[1].length - 1;
						lastdate = value[1][valueLength][0];
		        	});

		        	capacityCap = 100;
		        	CapacityLine = [
		        					[firstdate, capacityCap],
									[lastdate, capacityCap]
								];
					console.log(CapacityLine);
		        	chart.addSeries({
		        		name: "Capacity",
		        		data: CapacityLine
		        	});
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