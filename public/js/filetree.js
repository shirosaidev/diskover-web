/*
 * d3 filetree for diskover-web
 */

$(document).ready(function () {

	$('#submit').click(function () {
		console.log('changing paths');
		var newdir = $('#path').val();
		var filter = parseInt($_GET('filter'));
		var mtime = $_GET('mtime');
		location.href = "/filetree.php?path=" + newdir + "&filter=" + filter + "&mtime=" + mtime;
		return false;
	});

});

function showHidden(root) {
	// data is loaded so let's show hidden elements on the page
	// update path field
	document.getElementById('path').value = root.name;
	// show path input
	document.getElementById('path-container').style.display = 'inline-block';
	// show filetree buttons
	document.getElementById('buttons-container').style.display = 'inline-block';
	// show chart div
	document.getElementById('chart-container').style.display = 'block';
}

function getChildJSON(d) {
	// get json data from Elasticsearch using php data grabber
	console.log("getting children from Elasticsearch:"+d.name);

	var target = document.getElementById('tree-container');
	// trigger loader
	var spinner = new Spinner(opts).spin(target);

	// url for d3.json
	var url = '/d3_data.php?path=' + encodeURIComponent(d.name) + '&filter=' + filter + '&mtime=' + mtime;

	// load json data and trigger callback
	d3.json(url, function (error, data) {

		// display error if data has error message
		if ((data && data.error) || error) {
			spinner.stop();
			console.warn("nothing found in Elasticsearch: " + error);
			document.getElementById('error').style.display = 'block';
			return false;
		}

		if (data.children.length > 0) {
			// update children in root
			d._children = [];
			d._children = data.children;
		}

		// stop spin.js loader
		spinner.stop();

	});

}

function getJSON() {

	console.time('loadtime')

	// check if json data stored in session storage
	root = JSON.parse(sessionStorage.getItem("diskover-filetree"));

	// get data from Elasticsearh if no json in session storage
	if (!root) {
		getESJsonData();
		return true;
	}

	// get new json data if filter cookies are different than current url params
	if ($_GET('filter') != '' || $_GET('mtime') != '') {
		if ($_GET('filter') != getCookie('filter') || $_GET('mtime') != getCookie('mtime')) {
			console.log("removing json data on local storage because filters changed");
			sessionStorage.removeItem("diskover-filetree");
			getESJsonData();
			return true;
		}
	}

	// get new json data from ES if path changed
	if (root.name != path) {
		console.log("removing json data on local storage because path changed");
		sessionStorage.removeItem("diskover-filetree");
		getESJsonData();
		return true;
	} else if (root.name == path) {
		// json data on local storage is same as path so lets show the visuals
		console.log("json data in storage same as path, load visuals");
		loadVisualizations();
		return true;
	}

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

			root = data;

			console.log("storing json data in session storage");
			// store in session Storage
			sessionStorage.setItem('diskover-filetree', JSON.stringify(data));

			// stop spin.js loader
			spinner.stop();
			
			console.timeEnd('loadtime');

			// load d3 visuals
			loadVisualizations();

		});
	}

	function loadVisualizations() {

		// show hidden elements on page
		showHidden(root);

		// store cookies
		setCookie('path', $('#path').val());
		($_GET('filter')) ? setCookie('filter', $_GET('filter')): setCookie('filter', 1048576);
		($_GET('mtime')) ? setCookie('mtime', $_GET('mtime')): setCookie('mtime', 0);

		// update file tree link
		changeFileTreeLink();

		// load file tree
		updateTree(root, root);
		
		// load pie chart
		changePie(root);
	}

}

function updateTree(data, parent) {

	function toggleChildren(d) {
		if (d.children) {
			d._children = d.children;
			d.children = null;
		} else if (d._children) {
			d.children = d._children;
			d._children = null;
		}
	}
	
	var nodes = tree.nodes(data),
			duration = 250;

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
			if (d.count > 0 && !d.children && !d._children) {
				// check if there are any children in Elasticsearch
				getChildJSON(d);
			} else if (d._children) {
				toggleChildren(d);
				updateTree(data, d);
				changePie(d);
			} else if (d.children) {
				toggleChildren(d);
				updateTree(data, d);
			} else if (!d.count) {
				// display file in search results
				window.location.href = '/advanced.php?submitted=true&p=1&filename=' + encodeURIComponent(d.name) +'&path_parent=' + encodeURIComponent(node.name);
			}
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
			return d.depth == 0 ? d.name : d.name.split('/').pop();
		});
	//add filesize
	entered.append("span").attr("class", function (d) {
			var percent = (d.size / (d.parent || root).size * 100).toFixed(0);
			if (percent >= 90) {
				var fileclass = "filesize-red";
			} else if (percent >= 75) {
				var fileclass = "filesize-orange";
			} else if (percent >= 50) {
				var fileclass = "filesize-yellow";
			} else {
				var fileclass = "filesize-gray";
			}
			return fileclass;
		})
		.html(function (d) {
			return format(d.size);
		});
	// add percent
	entered.append("span").attr("class", "percent")
		.style("width", function (d) {
			var percent = (d.size / (d.parent || root).size * 100).toFixed(0);
			return percent + "%";
		});
	// add file count
	entered.append("span").attr("class", "filecount")
		.html(function (d) {
			return d.count > 0 ? "(" + d.count + ")" : "";
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

var root,
	id = 0;

var tree = d3.layout.treelist()
	.childIndent(15)
	.nodeHeight(18);
var ul = d3.select("#tree-container").append("ul").classed("treelist", "true");

var filter = parseInt($_GET('filter'));
var mtime = $_GET('mtime');
var path = decodeURIComponent($_GET('path'));
// remove any trailing slash
if (path != '/') {
	path = path.replace(/\/$/, "");
}

console.log("PATH:" + path);
console.log("SIZE_FILTER:" + filter);
console.log("MTIME_FILTER:" + mtime);

// add filters and maxdepth to statustext
var status_filter = ($_GET('filter')) ? 'size:' + format($_GET('filter')) : 'size:>1 MB';
var status_mtime = ($_GET('mtime')) ? ' mtime:' + $_GET('mtime') : ' mtime:0';
document.getElementById('statusfilters').append(status_filter);
document.getElementById('statusfilters').append(status_mtime);

// config references
var chartConfig = {
	target: 'mainwindow',
	data_url: '/d3_data.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime
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


// get json data
getJSON();
