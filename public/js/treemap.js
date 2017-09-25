/*
 * d3 Tree map for diskover-web
 */

function getESJsonData() {
	// get json data from Elasticsearch using php data grabber
	console.log("no json data in session storage, grabbing from Elasticsearch");

	// trigger loader
	var spinner = new Spinner(opts).spin(target);

	// load json data from Elasticsearch
	d3.json(chartConfig.data_url, function (error, data) {

		// display error if data has error message
		if ((data && data.error) || error) {
			spinner.stop();
			console.warn("nothing found in Elasticsearch: " + error);
			document.getElementById('error').style.display = 'block';
			return false;
		}

		console.log("storing json data in session storage");
		// store in session Storage
		sessionStorage.setItem('diskover-spaceexplorer', JSON.stringify(data));

		// stop spin.js loader
		spinner.stop();

		loadVisual(data);

	});
}

function loadVisual(data) {
	node = root = data;

	var w = window.innerWidth - 40,
		h = window.innerHeight - 140,
		x = d3.scale.linear().range([0, w]),
		y = d3.scale.linear().range([0, h]),
		//color = d3.scale.category20c();
		color = d3.scale.ordinal()
		.range(["#98abc5", "#8a89a6", "#7b6888", "#6b486b", "#a05d56", "#d0743c", "#ff8c00"]);

	var treemap = d3.layout.treemap()
		.round(false)
		.size([w, h])
		.sticky(true)
		.value(function (d) {
			return d.size;
		});

	var svg = d3.select("#treemap-container").append("div")
		.attr("class", "chart")
		.style("width", w + "px")
		.style("height", h + "px")
		.append("svg:svg")
		.attr("width", w)
		.attr("height", h)
		.append("svg:g")
		.attr("transform", "translate(.5,.5)");

	var nodes = treemap.nodes(root)
		.filter(function (d) {
			return !d.children;
		});

	var cell = svg.selectAll("g")
		.data(nodes)
		.enter().append("svg:g")
		.attr("class", "cell")
		.attr("transform", function (d) {
			return "translate(" + d.x + "," + d.y + ")";
		})
		.on("click", function (d) {
			return zoom(node == d.parent ? root : d.parent);
		})
		.on("mouseover", function (d) {
			tip.show(d);
		})
		.on("mouseout", function (d) {
			tip.hide(d);
		})
		.on('mousemove', function () {
			if (d3.event.pageY > window.innerHeight-50) {
				// change tip for bottom of screen
				return tip
				.style("top", (d3.event.pageY - 40) + "px")
				.style("left", (d3.event.pageX + 10) + "px");
			} else if (d3.event.pageX > window.innerWidth-200) {
				// change tip for right side of screen
				return tip
				.style("top", (d3.event.pageY + 10) + "px")
				.style("left", (d3.event.pageX - 200) + "px");
			} else {
				return tip
				.style("top", (d3.event.pageY - 10) + "px")
				.style("left", (d3.event.pageX + 10) + "px");
			}
		});

	cell.append("svg:rect")
		.attr("width", function (d) {
			return d.dx - 1;
		})
		.attr("height", function (d) {
			return d.dy - 1;
		})
		.style("fill", function (d) {
			return color(d.parent.name);
		});

	cell.append("svg:text")
		.attr("x", function (d) {
			return d.dx / 2;
		})
		.attr("y", function (d) {
			return d.dy / 2;
		})
		.attr("dy", ".35em")
		.attr("text-anchor", "middle")
		.text(function (d) {
			return d.name.split('/').pop();
		})
		.style("opacity", function (d) {
			d.w = this.getComputedTextLength();
			return d.dx > d.w ? 1 : 0;
		});

	d3.select(window).on("click", function () {
		zoom(root);
	});

	/* ------- SIZE/COUNT BUTTONS -------*/
	
	//d3.select("select").on("change", function () {
	//	treemap.value(this.value == "size" ? size : count).nodes(root);
	//	zoom(node);
	//});

	d3.select("#size").on("click", function () {
		treemap.value(size).nodes(root);
		zoom(node);
		d3.select("#size").classed("active", true);
		d3.select("#count").classed("active", false);
	});

	d3.select("#count").on("click", function () {
		treemap.value(count).nodes(root);
		zoom(node);
		d3.select("#size").classed("active", false);
		d3.select("#count").classed("active", true);
	});

	function size(d) {
		return d.size;
	}

	function count(d) {
		return 1;
	}

	function zoom(d) {
		var kx = w / d.dx,
			ky = h / d.dy;
		x.domain([d.x, d.x + d.dx]);
		y.domain([d.y, d.y + d.dy]);

		var t = svg.selectAll("g.cell").transition()
			.duration(d3.event.altKey ? 7500 : 750)
			.attr("transform", function (d) {
				return "translate(" + x(d.x) + "," + y(d.y) + ")";
			});

		t.select("rect")
			.attr("width", function (d) {
				return kx * d.dx - 1;
			})
			.attr("height", function (d) {
				return ky * d.dy - 1;
			})

		t.select("text")
			.attr("x", function (d) {
				return kx * d.dx / 2;
			})
			.attr("y", function (d) {
				return ky * d.dy / 2;
			})
			.style("opacity", function (d) {
				return kx * d.dx > d.w ? 1 : 0;
			});

		node = d;
		d3.event.stopPropagation();
	}

	/* ------- TOOLTIP -------*/

	var tip = d3.tip()
		.attr('class', 'd3-tip')
		.html(function (d) {

			var rootval = (node || root).size;
			var percent = (d.size / rootval * 100).toFixed(1) + '%';
			var sum = format(d.size);

			return "<span style='font-size:12px;color:white;'>" + d.name + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
		});

	svg.call(tip);

	d3.select("#treemap-container").append("div")
		.attr("class", "tooltip")
		.style("opacity", 0);
	
	// show treemap buttons
	document.getElementById('buttons-container').style.display = 'inline-block';
	
	// store cookies
	setCookie('path', root.name);
	
	// update analytics links
	updateVisLinks();

}

var path = decodeURIComponent($_GET('path'));
// remove any trailing slash
if (path != '/') {
	path = path.replace(/\/$/, "");
}
var filter = 1048576, // min file size filter
	mtime = 0, // min modified time filter
	root,
	node;

console.log("PATH:" + path);
console.log("SIZE_FILTER:" + filter);
console.log("MTIME_FILTER:" + mtime);

// config references
var chartConfig = {
	target: 'mainwindow',
	data_url: '/d3_data_tm.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime
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

console.time('loadtime')

// trigger loader
var spinner = new Spinner(opts).spin(target);

// check if json data stored in session storage
root = JSON.parse(sessionStorage.getItem("diskover-spaceexplorer"));

// get data from Elasticsearh if no json in session storage or path diff
if (!root || (root && root.name != path)) {
	getESJsonData();
} else {
	loadVisual(root);
}

spinner.stop();
console.timeEnd('loadtime');

