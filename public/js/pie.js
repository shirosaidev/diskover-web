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

		d3.select("#size").classed("active", true);
		d3.select("#count").classed("active", false);
	});

	d3.select("#count").on("click", function () {
		setCookie('use_count', 1);
		use_count = 1;

		changePie(node);

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
	height = 500,
	radius = Math.min(width, height) / 2;

var pie = d3.layout.pie()
	.sort(null)
	.value(function (d) {
		return d.value;
	});

var arc = d3.svg.arc()
	.outerRadius(radius * 0.7)
	.innerRadius(radius * 0.5);

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
	
	svg.selectAll("text").remove();
	
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
