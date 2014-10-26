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

		var seriesData = [ {
			id : 'OK',
			name : 'OK',
			data : [ 0 ],
			color : '#67B10B'
		}, {
			id : 'WARN',
			name : 'WARN',
			data : [ 0 ],
			color : '#DAD60B'
		}, {
			id : 'CRIT',
			name : 'CRIT',
			data : [ 0 ],
			color : '#B61211'
		}, {
			id : 'MAINT',
			name : 'MAINT',
			data : [ 0 ],
			color : '#555B98'
		}, {
			id : 'UNKNOWN',
			name : 'UNKNOWN',
			data : [ 0 ],
			color : '#AEAEAE'
		} ];

		var dataLabelsEnabled = false;
		var chart = new Highcharts.Chart({
			chart : {
				renderTo : chartDivId,
				width : dimensions.width,
				height : dimensions.height,
				type : chartType,
				animation : true,
				style : textStyle
			},
			credits : {
				enabled : false
			},
			plotOptions : {
				series : {
					pointWidth : 20,
					animation : true
				}
			},
			title : {
				text : '&nbsp;',
				y : 5,
				style : $.extend({
					fontWeight : "bold"
				}, textStyle),
				useHTML : true
			},
			subtitle : {
				text : '&nbsp;',
				y : 20,
				style : textStyle,
				useHTML : true
			},
			xAxis : {
				labels : {
					enabled : false
				}
			},
			yAxis : {
				labels : {
					style : textStyle,
				},
				allowDecimals : false,
				min : 0,
				title : {
					text : ''
				}
			},
			tooltip : {
				style : textStyle,
				formatter : function() {
					if (dataLabelsEnabled) {
						return '<b>' + this.series.name + '</b> - ' + monitorCount(this.y);
					} else {
						return '';
					}
				}
			},
			legend : {
				enabled : false
			},
			series : seriesData,
			spacingTop : 5,
			spacingRight : 5,
			spacingBottom : 5,
			spacingLeft : 5
		});

		function requestData() {
			api.getElementStatus(elementId).then(
					function(result) {
						if (!result.isMonitored) {
							chart.hideLoading();
							displayStatusBar("No visible elements to monitor", "Error Loading Chart Data");						
							return;
						}						
						var statusCount = {
							'OK' : 0,
							'WARN' : 0,
							'CRIT' : 0,
							'UNKNOWN' : 0,
							'MAINT' : 0
						};
						var total = 0;
						$.each(result.monitorStatus, function(index, monitor) {
							if (monitor.isMonitored && !monitor.isHidden) {
								statusCount[monitor.status]++;
								total++;
							}
						});
						chart.setTitle({
							text : '<a href="' + uptimeGadget.getElementUrls(result.id, result.name).services
									+ '" target="_top">' + escapeHtml(result.name) + '</a>',
						}, {
							text : monitorCount(total),
						});
						$.each(statusCount, function(status, count) {
							var bar = chart.get(status);
							bar.setData([ count ]);
							if (count > 0) {
								bar.show();
							} else {
								bar.hide();
							}
						});
						clearStatusBar();
						dataLabelsEnabled = true;
						chart.hideLoading();
					}, function(error) {
						chart.hideLoading();
						displayStatusBar(error, "Error Loading Chart Data");
					});
			if (refreshInterval > 0) {
				chartTimer = setTimeout(requestData, refreshInterval * 1000);
			}
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