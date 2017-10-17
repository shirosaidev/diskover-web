/*
 * d3 Pie chart for diskover-web
 */

$(document).ready(function () {

	// move sunburst on scroll
	$(window).scroll(function () {
		$("#chart-container").stop().animate({
			"marginTop": ($(window).scrollTop()) + "px",
			"marginLeft": ($(window).scrollLeft()) + "px"
		}, "slow");
	});

	d3.select("#size").on("click", function () {
		setCookie('use_count', 0);
		use_count = 0;

		changePie(node);
		changePieFileExt(node.name);
		changeBarMtime(node.name);

		d3.select("#size").classed("active", true);
		d3.select("#count").classed("active", false);
	});

	d3.select("#count").on("click", function () {
		setCookie('use_count', 1);
		use_count = 1;

		changePie(node);
		changePieFileExt(node.name);
		changeBarMtime(node.name);

		d3.select("#size").classed("active", false);
		d3.select("#count").classed("active", true);
	});

});

var use_count = getCookie('use_count');
(use_count == '') ? use_count = false: "";
(use_count == 1) ? $('#count').addClass('active'): $('#size').addClass('active');

console.log("USECOUNT:" + use_count);

var hide_thresh = getCookie('hide_thresh');
(hide_thresh == '') ? hide_thresh = 0.9: "";
// add hide thresh to statustext
document.getElementById('statushidethresh').innerHTML = ' hide_thresh:' + hide_thresh;

console.log("HIDETHRESH:" + hide_thresh);

function changeThreshold(a) {
	hide_thresh = a;
	setCookie('hide_thresh', hide_thresh);
	document.getElementById('statushidethresh').innerHTML = ' hide_thresh:' + hide_thresh;
	changePie(node);
	changePieFileExt(node.name);
	changeBarMtime(node.name);
}

var svg = d3.select("#piechart")
	.append("svg")
	.append("g")

svg.append("g")
	.attr("class", "slices");
svg.append("g")
	.attr("class", "labels");
svg.append("g")
	.attr("class", "lines");

var width = 960,
	height = 400,
	radius = Math.min(width, height) / 2;

var pie = d3.layout.pie()
	.sort(null)
	.value(function (d) {
		return d.value;
	});

var arc = d3.svg.arc()
	.outerRadius(radius * 0.8)
	.innerRadius(radius * 0.6);

var outerArc = d3.svg.arc()
	.innerRadius(radius * 0.9)
	.outerRadius(radius * 0.9);

svg.attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

var key = function (d) {
	return d.data.label;
};

//var color = d3.scale.category20c();

var color = d3.scale.ordinal()
	.range(["#98abc5", "#8a89a6", "#7b6888", "#6b486b", "#a05d56", "#d0743c", "#ff8c00"]);

function pieData(data) {

	var labels = [];

	data.children.forEach(addLabels)

	function addLabels(item) {
		var val = (use_count == true) ? (item.count) ? item.count : 0 : item.size;
		var rootval = (use_count == true) ? (node || root).count : (node || root).size;
		var percent = (val / rootval * 100).toFixed(1);
		if (percent > hide_thresh) {
			labels.push({
				'label': item.name.split('/').pop(),
				'value': val
			});
		}
	}

	return labels;
}


/* ------- TOOLTIP -------*/

var tip = d3.tip()
	.attr('class', 'd3-tip')
	.html(function (d) {

		var rootval = (use_count == true) ? (node || root).count : (node || root).size;
		var percent = (d.value / rootval * 100).toFixed(1) + '%';
		var sum = (use_count == true) ? d.value : format(d.value);

		return "<span style='font-size:12px;color:white;'>" + d.data.label + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
	});

svg.call(tip);

d3.select("#piechart").append("div")
	.attr("class", "tooltip")
	.style("opacity", 0);

function changePie(data) {
	
	node = data;

	data = pieData(data);

	/* ------- PIE SLICES -------*/
	
	var slice = svg.select(".slices").selectAll("path.slice")
		.data(pie(data), key);

	slice.enter()
		.insert("path")
		.style("fill", function (d) {
			return color(d.data.label);
		})
		.on("mouseover", function (d) {
			tip.show(d)
		})
		.on('mouseout', function (d) {
			tip.hide(d)
		})
		.on('mousemove', function () {
			return tip
				.style("top", (d3.event.pageY - 10) + "px")
				.style("left", (d3.event.pageX + 10) + "px");
		})
		.attr("class", "slice");

	slice
		.transition().duration(1000)
		.attrTween("d", function (d) {
			this._current = this._current || d;
			var interpolate = d3.interpolate(this._current, d);
			this._current = interpolate(0);
			return function (t) {
				return arc(interpolate(t));
			};
		})

	slice.exit()
		.remove();
	
	/* ------- TITLE LABEL -------*/
	
	svg.select(".label").remove();
	svg.select(".label-percent").remove();
	
	var label = svg.append("text")
		.attr("dy", "-1em")
		.attr("class", "label")
		.text(node.name.split('/').pop())
	
	var percent = svg.append("text")
		.attr("dy", "1.5em")
		.attr("class", "label-percent")
		.text((node.size / root.size * 100).toFixed(1) + '%')

	/* ------- TEXT LABELS -------*/

	var text = svg.select(".labels").selectAll("text")
		.data(pie(data), key);

	text.enter()
		.append("text")
		.attr("dy", ".35em")
		.text(function (d) {
			return d.data.label;
		});

	function midAngle(d) {
		return d.startAngle + (d.endAngle - d.startAngle) / 2;
	}

	text.transition().duration(1000)
		.attrTween("transform", function (d) {
			this._current = this._current || d;
			var interpolate = d3.interpolate(this._current, d);
			this._current = interpolate(0);
			return function (t) {
				var d2 = interpolate(t);
				var pos = outerArc.centroid(d2);
				pos[0] = radius * (midAngle(d2) < Math.PI ? 1 : -1);
				return "translate(" + pos + ")";
			};
		})
		.styleTween("text-anchor", function (d) {
			this._current = this._current || d;
			var interpolate = d3.interpolate(this._current, d);
			this._current = interpolate(0);
			return function (t) {
				var d2 = interpolate(t);
				return midAngle(d2) < Math.PI ? "start" : "end";
			};
		});

	text.exit()
		.remove();

	/* ------- SLICE TO TEXT POLYLINES -------*/

	var polyline = svg.select(".lines").selectAll("polyline")
		.data(pie(data), key);

	polyline.enter()
		.append("polyline");

	polyline.transition().duration(1000)
		.attrTween("points", function (d) {
			this._current = this._current || d;
			var interpolate = d3.interpolate(this._current, d);
			this._current = interpolate(0);
			return function (t) {
				var d2 = interpolate(t);
				var pos = outerArc.centroid(d2);
				pos[0] = radius * 0.95 * (midAngle(d2) < Math.PI ? 1 : -1);
				return [arc.centroid(d2), outerArc.centroid(d2), pos];
			};
		});

	polyline.exit()
		.remove();

};


/*
 * d3 File Extension Pie chart for diskover-web
 */

var root2,
		id = 0;

var svg2 = d3.select("#piechart-ext")
.append("svg")
.append("g")

svg2.append("g")
	.attr("class", "slices");
svg2.append("g")
	.attr("class", "labels");
svg2.append("g")
	.attr("class", "lines");

var width2 = 420,
		height2 = 300,
		radius2 = Math.min(width2, height2) / 2;

var color2 = d3.scale.category20c();

var pie2 = d3.layout.pie()
	.sort(null)
	.value(function (d) {
		return d.value;
	});

var arc2 = d3.svg.arc()
	.outerRadius(radius2 * 0.8)
	.innerRadius(radius2 * 0);

var outerArc2 = d3.svg.arc()
	.innerRadius(radius2 * 0.9)
	.outerRadius(radius2 * 0.9);

svg2.attr("transform", "translate(" + width2 / 2 + "," + height2 / 2 + ")");

/* ------- TOOLTIP -------*/

var tip2 = d3.tip()
	.attr('class', 'd3-tip')
	.html(function (d) {

		var rootval = (use_count == true) ? (node2 || root2).count : (node2 || root2).size;
		var percent = (d.value / rootval * 100).toFixed(1) + '%';
		var sum = (use_count == true) ? d.value : format(d.value);
		var label = (d.data.label == '') ? 'NULLEXT' : d.data.label;

		return "<span style='font-size:12px;color:white;'>" + label + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
	});

svg2.call(tip2);

d3.select("#piechart-ext").append("div")
	.attr("class", "tooltip")
	.style("opacity", 0);


function loadPieFileExt(data) {
	
	function pieExtData(data) {

		var labels = [];

		data.children.forEach(addLabels)

		function addLabels(item) {
			var val = (use_count == true) ? (item.count) ? item.count : 0 : item.size;
			var rootval = (use_count == true) ? (node2 || root2).count : (node2 || root2).size;
			var percent = (val / rootval * 100).toFixed(1);
			if (percent > hide_thresh) {
				labels.push({
					'label': item.name,
					'value': val
				});
			}
		}

		return labels;
	}
	
	node2 = data;

	data = pieExtData(data);

	/* ------- PIE SLICES -------*/

	var slice2 = svg2.select(".slices").selectAll("path.slice")
		.data(pie2(data), key);

	slice2.enter()
		.insert("path")
		.style("fill", function (d) {
			return color2(d.data.label);
		})
		.on("mouseover", function (d) {
			tip2.show(d)
		})
		.on('mouseout', function (d) {
			tip2.hide(d)
		})
		.on('mousemove', function () {
			return tip2
				.style("top", (d3.event.pageY - 10) + "px")
				.style("left", (d3.event.pageX + 10) + "px");
		})
		.attr("class", "slice");

	slice2
		.transition().duration(1000)
		.attrTween("d", function (d) {
			this._current = this._current || d;
			var interpolate = d3.interpolate(this._current, d);
			this._current = interpolate(0);
			return function (t) {
				return arc2(interpolate(t));
			};
		})

	slice2.exit()
		.remove();

	/* ------- TEXT LABELS -------*/

	var text2 = svg2.select(".labels").selectAll("text")
		.data(pie2(data), key);

	text2.enter()
		.append("text")
		.attr("dy", ".35em")
		.text(function (d) {
		return (d.data.label == '') ? 'NULLEXT' : d.data.label;
		});

	function midAngle(d) {
		return d.startAngle + (d.endAngle - d.startAngle) / 2;
	}

	text2.transition().duration(1000)
		.attrTween("transform", function (d) {
			this._current = this._current || d;
			var interpolate = d3.interpolate(this._current, d);
			this._current = interpolate(0);
			return function (t) {
				var d2 = interpolate(t);
				var pos = outerArc2.centroid(d2);
				pos[0] = radius2 * (midAngle(d2) < Math.PI ? 1 : -1);
				return "translate(" + pos + ")";
			};
		})
		.styleTween("text-anchor", function (d) {
			this._current = this._current || d;
			var interpolate = d3.interpolate(this._current, d);
			this._current = interpolate(0);
			return function (t) {
				var d2 = interpolate(t);
				return midAngle(d2) < Math.PI ? "start" : "end";
			};
		});

	text2.exit()
		.remove();

	/* ------- SLICE TO TEXT POLYLINES -------*/

	var polyline2 = svg2.select(".lines").selectAll("polyline")
		.data(pie2(data), key);

	polyline2.enter()
		.append("polyline");

	polyline2.transition().duration(1000)
		.attrTween("points", function (d) {
			this._current = this._current || d;
			var interpolate = d3.interpolate(this._current, d);
			this._current = interpolate(0);
			return function (t) {
				var d2 = interpolate(t);
				var pos = outerArc2.centroid(d2);
				pos[0] = radius2 * 0.95 * (midAngle(d2) < Math.PI ? 1 : -1);
				return [arc2.centroid(d2), outerArc2.centroid(d2), pos];
			};
		});

	polyline2.exit()
		.remove();

}

function changePieFileExt(path) {
	
	// config references
	var chartConfig = {
		target: 'piechart-ext',
		data_url: '/d3_data_pie_ext.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime
	};

	// loader settings
	var opts = {
		lines: 12, // The number of lines to draw
		length: 6, // The length of each line
		width: 3, // The line thickness
		radius: 7, // The radius of the inner circle
		color: '#EE3124', // #rgb or #rrggbb or array of colors
		speed: 1.9, // Rounds per second
		trail: 40, // Afterglow percentage
		className: 'spinner', // The CSS class to assign to the spinner
	};

	// loader settings
	var target = document.getElementById(chartConfig.target);
	
	// trigger loader
	var spinner = new Spinner(opts).spin(target);

	// load json data from Elasticsearch
	d3.json(chartConfig.data_url, function (error, data) {

		root2 = data;
		
		// stop spin.js loader
		spinner.stop();
		
		// load d3 visual
		loadPieFileExt(data);

	});
}


/*
 * d3 Mtime bar chart for diskover-web
 */

var root3;

var valueLabelWidth = 40; // space reserved for value labels (right)
var barHeight = 20; // height of one bar
var barLabelWidth = 50; // space reserved for bar labels
var barLabelPadding = 5; // padding between bar and bar labels (left)
var gridChartOffset = 0; // space between start of grid and first bar
var maxBarWidth = 320; // width of the bar with the max value

// svg container element
var svg3 = d3.select('#barchart-mtime').append("svg")
		.attr('width', maxBarWidth + barLabelWidth + valueLabelWidth);

function loadBarMtime(data) {
	
	function barData(data) {

		var labels = [];

		data.children.forEach(addLabels)

		function addLabels(item) {
			var val = (use_count == true) ? (item.count) ? item.count : 0 : item.size;
			labels.push({
				'label': item.mtime,
				'value': val
			});
		}

		return labels;
	}
	
	node3 = data;
	
	data = barData(data);
	
	// remove existing elements
	svg3.selectAll("g").remove();
	
	/* ------- BARS -------*/

	// accessor functions 
	var barLabel = function(d) { return d['label']; };
	var barValue = function(d) { return d['value']; };

	// scales
	var yScale = d3.scale.ordinal().domain(d3.range(0, data.length)).rangeBands([0, data.length * barHeight]);
	var y = function(d, i) { return yScale(i); };
	var yText = function(d, i) { return y(d, i) + yScale.rangeBand() / 2; };
	var x = d3.scale.linear().domain([0, d3.max(data, barValue)]).range([0, maxBarWidth]);
	
	// bar labels
	var labelsContainer = svg3.append('g')
		.attr('transform', 'translate(' + (barLabelWidth - barLabelPadding) + ',' + gridChartOffset + ')'); 
	labelsContainer.selectAll('text').data(data).enter().append('text')
		.attr('y', yText)
		.attr('stroke', 'none')
		.attr('fill', 'white')
		.attr("dy", ".35em") // vertical-align: middle
		.attr('text-anchor', 'end')
		.text(barLabel);
	// bars
	var barsContainer = svg3.append('g')
		.attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')'); 
	barsContainer.selectAll("rect").data(data).enter().append("rect")
		.attr('y', y)
		.attr('height', yScale.rangeBand())
		.attr('width', function(d) { return x(barValue(d)); })
		.attr('stroke', '#272B30')
		.attr('fill', 'steelblue');
	// bar value labels
	barsContainer.selectAll("text").data(data).enter().append("text")
		.attr("x", function(d) { return x(barValue(d)); })
		.attr("y", yText)
		.attr("dx", 3) // padding-left
		.attr("dy", ".35em") // vertical-align: middle
		.attr("text-anchor", "start") // text-align: right
		.text(function(d) { return (use_count == true) ? (barValue(d)) ? barValue(d) : 0 : format(barValue(d)); });
	// start line
	barsContainer.append("line")
		.attr("y1", -gridChartOffset)
		.attr("y2", yScale.rangeExtent()[1] + gridChartOffset)
		.style("stroke", "#272B30");

	barsContainer.exit()
		.remove();
	
	barsContainer
		.transition()
		.duration(1000)
			.attr("width", function(d) { return x(barValue(d)); });
	
}

function changeBarMtime(path) {
	
	// config references
	var chartConfig = {
		target: 'barchart-mtime',
		data_url: '/d3_data_bar_mtime.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime
	};

	// loader settings
	var opts = {
		lines: 12, // The number of lines to draw
		length: 6, // The length of each line
		width: 3, // The line thickness
		radius: 7, // The radius of the inner circle
		color: '#EE3124', // #rgb or #rrggbb or array of colors
		speed: 1.9, // Rounds per second
		trail: 40, // Afterglow percentage
		className: 'spinner', // The CSS class to assign to the spinner
	};

	// loader settings
	var target = document.getElementById(chartConfig.target);
	
	// trigger loader
	var spinner = new Spinner(opts).spin(target);
	
	// load json data from Elasticsearch
	d3.json(chartConfig.data_url, function (error, data) {

		root3 = data;
		
		// stop spin.js loader
		spinner.stop();
		
		// load d3 visual
		loadBarMtime(data);

	});
}
