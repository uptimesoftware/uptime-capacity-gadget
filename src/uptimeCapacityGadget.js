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
		var dailyVal = null;
		var queryType = null;
		var timeFrame = null;
		var chartTimer = null;
		var capacityBuffer = 100;
		var api = new apiQueries();
		var baseGadgetPath = null;

		var textStyle = {
			fontFamily : "Verdana, Arial, Helvetica, sans-serif",
			fontSize : "9px",
			lineHeight : "11px",
			color : "#565E6C"
		};

		if (typeof options == "object") {
			dimensions = options.dimensions;
			chartDivId = options.chartDivId;
			dailyVal = options.dailyVal;
			queryType = options.queryType;
			elementId = options.elementId;
			timeFrame = options.timeFrame;
			capacityBuffer = options.capacityBuffer;
			baseGadgetPath = options.baseGadgetPath;
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
            	yAxis: {
            	    title: {enabled: false,
                    text: ""}},
            	plotOptions: {spline: {marker: {enabled: false}},
                    areaspline: {marker: {enabled: false}}},
            	series: [],
		});

		function requestData() {

			var firstPoint = null;
			var lastPoint = null;

            //find the beginning part of the queryType
            queryType_split = queryType.split("-");

            var my_url = baseGadgetPath;
            if ( queryType_split[0] == 'osperf')
            {
			    my_url = my_url + 'getmetrics.php' + '?uptime_offset=' + 14400 + '&query_type=' + queryType + '&dailyVal='  + dailyVal + "&element=" + elementId + "&time_frame=" + timeFrame;
		    }
            else if ( queryType_split[0] == 'vmware')
            {
                my_url = my_url + 'getvmwaremetrics.php' + '?uptime_offset=' + 14400 + '&query_type=' + queryType + '&dailyVal='  + dailyVal + "&element=" + elementId + "&time_frame=" + timeFrame;
            }
			else if ( queryType_split[0] == 'hyperv')
            {
                my_url = my_url + 'gethypervmetrics.php' + '?uptime_offset=' + 14400 + '&query_type=' + queryType + '&dailyVal='  + dailyVal + "&element=" + elementId + "&time_frame=" + timeFrame;
            }
            else if ( queryType_split[0] == 'xenserver')
            {
                my_url = my_url + 'getxenmetrics.php' + '?uptime_offset=' + 14400 + '&query_type=' + queryType + '&dailyVal='  + dailyVal + "&element=" + elementId + "&time_frame=" + timeFrame;
            }
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

						addCapacityLines(value);
					});

		        	clearStatusBar();
					dataLabelsEnabled = true;
					chart.hideLoading();
		        },
		        'error': function () {
		        	$("#countDownTillDoomsDay").html("No Data");
		        	chart.hideLoading();
		        }
	    	});	
		}

		function addCapacityLines(data) {
            //draw the various capacity and estimated usage lines



            //get the first and last points from the time series data
            timeseries = data['series'];
            firstPoint = timeseries[0];
            valueLength = timeseries.length - 1;
            lastPoint = timeseries[valueLength];

            dataname = data['name'];

            last_Xvalue = lastPoint[0];
            last_Yvalue = lastPoint[1];




            xDeltaTotal = 0;
            yDeltaTotal = 0;
            //total up the difference between all the daily points
            $.each(timeseries, function(index, value) {
                if (index >= 1)
                {
                    xDelta = value[0] - timeseries[index - 1][0];
                    yDelta = value[1] - timeseries[index - 1][1];
                    xDeltaTotal = xDeltaTotal + xDelta;
                    yDeltaTotal = yDeltaTotal + yDelta;
                }
            });

            //get the average delta for both X and Y
            xDelta = xDeltaTotal / (timeseries.length -1);//should be one day in ms
            yDelta = yDeltaTotal / (timeseries.length -1);

            //get the total capacity value from our json data and figure out the buffered capacity cap
            capacityCap = data['capacity'];
            capacityCapBuffered = data['capacity'] * ( capacityBuffer / 100);


            //setup the various lines we'll need to draw
            CapacityLine = [[firstPoint[0], capacityCap]];

            BufferedCapacityLine = [[firstPoint[0], capacityCapBuffered]];

            LineOfBestFitForEstimatedMetrics = [lastPoint];

            LineOfBestFitForRealMetrics = [firstPoint, lastPoint];


            //setup some empty points as well
            bufferedcapacityWithNewVms = null;
            CapacityPoint = null;
            BufferedCapacityPoint = null;

            //we only need to figure out the capacity points if things are actualy trending upwards
            if ( yDelta > 0 )
            {
                //if the starting point for our estimated usage line is below our capacity Cap
                if( capacityCap > last_Yvalue)
                {
                    CapacityPoint = figureOutCapacity(capacityCap, last_Xvalue, last_Yvalue, xDelta, yDelta);
                    CapacityLine.push(CapacityPoint);
                    
                    BufferedCapacityPoint = figureOutCapacity(capacityCapBuffered, last_Xvalue, last_Yvalue, xDelta, yDelta);
                    BufferedCapacityLine.push(BufferedCapacityPoint);

                     
                    //pass all these points along, so that we can populate the info panel.
                    fillInInfoPanel(lastPoint, CapacityPoint, BufferedCapacityPoint, yDelta, data['unit'], data['name']);

                    //fill out the rest of the capacity Lines
                    if (BufferedCapacityPoint[1] > CapacityPoint[1])
                    {
                        //if the BufferedCapacity is greater then our real capacity
                        //then the CapacityPoint will naturely come first on the LineOfBestFit
                        LineOfBestFitForEstimatedMetrics.push(CapacityPoint);
                        LineOfBestFitForEstimatedMetrics.push(BufferedCapacityPoint);
                        //this also means that the BufferedCapcityLine is longer
                        //so we need to add another point to the real Capacity Line
                        CapacityLine.push([BufferedCapacityPoint[0], CapacityPoint[1]]);

                    }
                    else
                    {
                        //otherwise if buffered capacity is less then real capacity
                        //then the buffered capacity point comes first
                        LineOfBestFitForEstimatedMetrics.push(BufferedCapacityPoint);
                        LineOfBestFitForEstimatedMetrics.push(CapacityPoint);
                        //add an extra point to the BufferedCapacityLine
                        BufferedCapacityLine.push([CapacityPoint[0], BufferedCapacityPoint[1]]);
                    }
                }
            }
            //if things aren't trending upwards then just extend our capacity Lines and fill out the info panel
            else
            {
                CapacityLine.push([lastPoint[0], capacityCap]);
                BufferedCapacityLine.push([lastPoint[0], capacityCapBuffered]);
                justAddTitletoDoomsday(yDelta, data['unit'], data['name']);
            }


            //draw the actual lines on the chart

            chart.addSeries({
                name: dataname + " - Usage",
                zindex: 2,
                data: LineOfBestFitForRealMetrics
            });

            chart.addSeries({
                name: dataname + " - Est",
                zindex: 2,
                data: LineOfBestFitForEstimatedMetrics
            });

            chart.addSeries({
                name: "Capacity",
                zindex: 1,
                data: CapacityLine
            });

            //only draw the buffered Capacity Line if it's different then the real capacity
            if (capacityBuffer != 100)
            {
                chart.addSeries({
                    name: capacityBuffer + "% Capacity",
                    zindex: 1,
                    data: BufferedCapacityLine
                });
            }

            chart.setSize(Math.max(100, dimensions.width - 10), Math.max(100, dimensions.height - 80));


        }


        function fillInInfoPanel(startpoint, capPoint, bufcapPoint, Delta, unit, seriesName)
        {
            $("#countDownTillDoomsDay").html("");
            starttime = startpoint[0];

            overview_string = "";
            overview_string += '<div id="infoTitle">' + seriesName + " over the past " + timeFrame + " months</div><br>";
            overview_string += '<div class="infoText">Average Daily Growth: ' + Delta.toFixed(2) + " " + unit + "</br></br>";


            //real capacity at current growth
            if (capPoint)
            {
                endtime = capPoint[0];
                time_left =  (endtime - starttime);
                time_left_in_days_till_Cap = Math.round(time_left / 1000 / 60 / 60 / 24);
                overview_string += 'Days left until capacity: ' + time_left_in_days_till_Cap + "<br>";

            }

            //buffered capacity at current growth
            if (bufcapPoint && capacityBuffer != 100)
            {
                endtime = bufcapPoint[0];
                time_left =  (endtime - starttime);
                time_left_in_days_till_BuffedCap = Math.round(time_left / 1000 / 60 / 60 / 24);
                overview_string += "Days left until " + capacityBuffer + "% capacity: " + time_left_in_days_till_BuffedCap + "<br>";
            }


            $("#countDownTillDoomsDay").html(overview_string);
        }

        function justAddTitletoDoomsday(Delta, unit, seriesName)
        {
            $("#countDownTillDoomsDay").html("");

            overview_string = '<div id="infoTitle">' + seriesName + " over the past " + timeFrame + " months</div><br>";
            overview_string +=  '<div id="infoText">Daily Usage Trending Downwards at ' + Delta.toFixed(2) + " " + unit + " per day</div>";
            $("#countDownTillDoomsDay").html(overview_string);

        }

		function figureOutCapacity( targetCapacity, startX, startY, deltaX, deltaY )
		{

            CapacityLeft = targetCapacity - startY;
            timeToGo = CapacityLeft / deltaY;
            timeToGoInMS = timeToGo * deltaX;
            actualTime = timeToGoInMS + startX;

            return [actualTime, targetCapacity ];

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
