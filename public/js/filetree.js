/*
 * d3 filetree for diskover-web
 */

$('#submit').click(function () {
	console.log('changing paths');
	var newdir = $('#path').val();
	var filter = $_GET('filter');
	var maxdepth = $_GET('maxdepth');
	location.href = "/sunburst.php?path=" + newdir + "&filter=" + filter + '&maxdepth=' + maxdepth;
	return false;
});

function showHidden(data) {
	// display warning if no files or some other error
	if (data == null || data.error) {
		console.warn("nothing found in Elasticsearch");
		document.getElementById('error').style.display = 'block';
		return false;
	}
	// data is loaded so let's show hidden elements on the page
	// update path field
	document.getElementById('path').value = jsondata.name;
	// show path input
	document.getElementById('path-container').style.display = 'inline-block';
	// show filetree buttons
	document.getElementById('buttons-container').style.display = 'inline-block';
	// show sunburst div
	document.getElementById('sunburst-container').style.display = 'block';
	return true;
}

function getJSON() {

	console.time('loadtime')

	// check if json data stored in session storage
	data = JSON.parse(sessionStorage.getItem("diskover-filetree"));
	root = JSON.parse(sessionStorage.getItem("diskover-filetree-root"));

	// get data from Elasticsearh if no json in session storage
	if (!data && !root) {
		getESJsonData(nodata = true);
		return true;
	} else if (!data) {
		getESJsonData();
		return true;
	}

	// get new json data if filter cookies are different than current url params
	if ($_GET('maxdepth') != '' && $_GET('filter') != '') {
		if ($_GET('maxdepth') != getCookie('maxdepth') || $_GET('filter') != getCookie('filter')) {
			console.log("removing json data on local storage because filters changed");
			sessionStorage.removeItem("diskover-filetree");
			sessionStorage.removeItem("diskover-filetree-root");
			getESJsonData(nodata = true);
			return true;
		}
	}

	// if path is root, get root json data
	if (root && path == root.name) {
		console.log("path is root so using json root data");
		data = root;
		sessionStorage.setItem('diskover-filetree', JSON.stringify(data));
		loadVisualizations();
		return true;
	}

	// get new json data from ES if path changed
	if (data.name != path) {
		console.log("removing json data on local storage because path changed");
		sessionStorage.removeItem("diskover-filetree");
		getESJsonData();
		return true;
	} else if (data.name == path) {
		// json data on local storage is same as path so lets show the visuals
		loadVisualizations();
		return true;
	}

	function getESJsonData(nodata = false) {
		// get json data from Elasticsearch using php data grabber
		console.log("no json data in storage, grabbing from Elasticsearch");

		// trigger loader
		var spinner = new Spinner(opts).spin(target);

		// load json data and trigger callback
		d3.json(chartConfig.data_url, function (error, json) {

			// display error if data has error message
			if ((data && data.error) || error) {
				spinner.stop();
				console.warn("nothing found in Elasticsearch: " + error);
				document.getElementById('error').style.display = 'block';
				return false;
			}

			// store json from Elasticsearch into data
			data = json;

			if (nodata == true && path == "/") {
				console.log("storing root path json data in storage");
				// store in session Storage
				sessionStorage.setItem('diskover-filetree-root', JSON.stringify(data));
			}

			console.log("storing path json data in storage");
			// store in session Storage
			sessionStorage.setItem('diskover-filetree', JSON.stringify(data));

			// stop spin.js loader
			spinner.stop();

			loadVisualizations();

		});
	}

	function loadVisualizations() {

		// return error if we haven't found any json data
		if (!data || data.error) {
			console.warn("path not found in json data");
			document.getElementById('error').style.display = 'block';
			return false;
		}

		// jsondata for sunburst
		jsondata = JSON.parse(JSON.stringify(data));
		// make copy
		jsondata0 = JSON.parse(JSON.stringify(data));

		console.log("JSON:");
		console.log(jsondata);

		showHidden(data);

		// store cookies
		setCookie('path', $('#path').val()); // decodeURIComponent($_GET('path'))
		($_GET('filter')) ? setCookie('filter', $_GET('filter')): setCookie('filter', 1048576);
		($_GET('maxdepth')) ? setCookie('maxdepth', $_GET('maxdepth')): setCookie('maxdepth', 3);

		// update file tree link
		changeFileTreeLink();

		// load file tree
		updateTree(data, data, firstrun = true);
		// load Sunburst
		onJson(null, jsondata);
	}

	console.timeEnd('loadtime');

}

function updateTree(data, parent, firstrun) {

	// setup tree if first time
	if (firstrun) setupTree(data);

	var nodes = tree.nodes(data),
		duration = 250;

	function toggleChildren(d) {
		if (d.children) {
			d._children = d.children;
			d.children = null;
		} else if (d._children) {
			d.children = d._children;
			d._children = null;
		}
	}

	function getSunChild(d) {
		// directory changed so we need to get child from sunburst json
		var sunchild;

		// if at root return
		if (d.name == jsondata.name) return jsondata;

		// loop through all the children to find child (d)
		jsondata.children.forEach(getChild);
		jsondata._children.forEach(getChild);

		function getChild(child) {
			if (child.name == d.name && child.parent.name == d.parent.name) {
				sunchild = child;
				return sunchild;
			}
			// recurse if child has children
			if (child.children) {
				child.children.forEach(getChild);
			}
		}
		return sunchild;
	}

	function updateSunburst(d) {
		loc0 = d.name;
		if (d.children || d._children) {
			// directory changed, update sunburst
			console.log("update sunburst");
			// at root
			if (d.depth == 0) {
				console.log('AT ROOT');
				var root = getSunChild(d);
				console.log(root);
				zoom(root, root);
			} else {
				var sunchild = getSunChild(d);
				console.log(sunchild);
				zoom(sunchild, sunchild);
			}
			return true;
		}
		return false;
	}

	var nodeEls = ul.selectAll("li.node").data(nodes, function (d) {
		d.id = d.id || ++id;
		return d.id;
	});

	//entered nodes
	var entered = nodeEls.enter().append("li").classed("node", true)
		.style("top", parent.y + "px")
		.style("opacity", 0)
		.style("height", tree.nodeHeight() + "px")
		.on("click", function (d) {
			toggleChildren(d);
			updateTree(data, d, firstrun = false);
			loc = d.name;
			// update sunburst
			if (loc0 != loc && d.depth <= maxdepth - 2) updateSunburst(d);
		})
		.on("mouseover", function (d) {
			d3.select(this).classed("selected", true);
		})
		.on("mouseout", function (d) {
			d3.selectAll(".selected").classed("selected", false);
		});
	//add arrows if it is a folder
	entered.append("span").attr("class", function (d) {
		var icon = d.children ? " glyphicon-chevron-down" :
			d._children ? "glyphicon-chevron-right" : "";
		return "downarrow glyphicon " + icon;
	});
	//add icons for folder for file
	entered.append("span").attr("class", function (d) {
		var icon = d.children || d._children || d.count > 0 ? "glyphicon-folder-close" :
			"glyphicon-file";
		return "glyphicon " + icon;
	});
	//add text
	entered.append("span").attr("class", "filename")
		.html(function (d) {
			return d.name;
		});
	//add filesize
	entered.append("span").attr("class", function (d) {
			if (d.size > 10737418240) {
				var fileclass = "filesize-red";
			} else if (d.size > 5368709120 && d.size < 10737418240) {
				var fileclass = "filesize-orange";
			} else if (d.size > 1073741824 && d.size < 5368709120) {
				var fileclass = "filesize-yellow";
			} else {
				var fileclass = "filesize-gray";
			}
			return fileclass;
		})
		.html(function (d) {
			return format(d.size);
		});
	// add file count
	entered.append("span").attr("class", "filecount")
		.html(function (d) {
			return d.count;
		});
	//update caret arrow direction
	nodeEls.select("span.downarrow").attr("class", function (d) {
		var icon = d.children ? " glyphicon-chevron-down" :
			d._children ? "glyphicon-chevron-right" : "";
		return "downarrow glyphicon " + icon;
	});
	//update position with transition
	nodeEls.transition().duration(duration)
		.style("top", function (d) {
			return (d.y - tree.nodeHeight()) + "px";
		})
		.style("left", function (d) {
			return d.x + "px";
		})
		.style("opacity", 1);
	nodeEls.exit().remove();
}

function setupTree(data) {

	function collapse(d) {
		if (d.children) {
			d._children = d.children;
			d._children.forEach(collapse);
			d.children = null;
		}
	}

	function expandSingle(d) {
		if (d._children) {
			if (d.depth == 0) {
				d.children = d._children;
				d._children = null;
			}
		}
	}
	data.children.forEach(collapse);
	data.children.forEach(expandSingle);
}

var data,
	jsondata,
	jsondata0,
	id = 0,
	loc0,
	loc;

var tree = d3.layout.treelist()
	.childIndent(15)
	.nodeHeight(18);
var ul = d3.select("#tree-container").append("ul").classed("treelist", "true");

var filter = $_GET('filter');
var maxdepth = $_GET('maxdepth');
var path = decodeURIComponent($_GET('path'));
// remove any trailing slash
if (path != '/') {
	path = path.replace(/\/$/, "");
}

console.log("PATH:" + path);
console.log("FILTER:" + filter);
console.log("MAXDEPTH:" + maxdepth);

// config references
var chartConfig = {
	target: 'mainwindow',
	data_url: '/d3_data.php?path=' + path + '&filter=' + filter + '&maxdepth=' + maxdepth
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

// add filter and maxdepth to statustext
var status_filter = ($_GET('filter')) ? 'filter:' + format($_GET('filter')) : 'filter:1M';
document.getElementById('statusfilters').append(status_filter);
var status_maxdepth = ($_GET('maxdepth')) ? ' maxdepth:' + $_GET('maxdepth') : ' maxdepth:3';
document.getElementById('statusfilters').append(status_maxdepth);

// get json data and load file tree and sunburst
getJSON();
