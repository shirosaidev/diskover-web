/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * d3 filetree for diskover-web
 */

 $(document).ready(function() {

     $('#changepath').click(function () {
         console.log('changing paths');
         var newpath = encodeURIComponent($('#pathinput').val());
         setCookie('path', newpath);
         location.href = "filetree.php?index=" + index +"&index2=" + index2 + "&path=" + newpath + "&filter=" + filter + "&mtime=" + mtime + "&use_count=" + use_count + "&show_files=" + show_files;
         return false;
     });

     d3.select("#size").on("click", function() {
         use_count = 0;
         setCookie('use_count', 0);
         console.log("removing json data on local storage because size/count clicked");
 		 sessionStorage.removeItem("diskover-filetree");
         location.href = "filetree.php?index=" + index +"&index2=" + index2 + "&path=" + encodeURIComponent(path) + "&filter=" + filter + "&mtime=" + mtime + "&use_count=" + use_count + "&show_files=" + show_files;
     });

     d3.select("#count").on("click", function() {
         use_count = 1;
         setCookie('use_count', 1);
         console.log("removing json data on local storage because size/count clicked");
 		 sessionStorage.removeItem("diskover-filetree");
         location.href = "filetree.php?index=" + index +"&index2=" + index2 + "&path=" + encodeURIComponent(path) + "&filter=" + filter + "&mtime=" + mtime + "&use_count=" + use_count + "&show_files=" + show_files;
     });

     d3.select("#showfiles").on("change", function() {
         var sf = document.getElementById('showfiles').checked;
         (sf) ? show_files = 1 : show_files = 0;
         setCookie('show_files', show_files)
         console.log("removing json data on local storage because show files changed");
 		 sessionStorage.removeItem("diskover-filetree");
         location.href="filetree.php?index=" + index +"&index2=" + index2 + "&path=" + encodeURIComponent(path) + "&filter=" + filter + "&mtime=" + mtime + "&use_count=" + use_count + "&show_files=" + show_files;
     });

     getJSON();

     // set cookies
     setCookie('path', encodeURIComponent(path));
     setCookie('filter', filter);
     setCookie('mtime', mtime);
     setCookie('hide_thresh', hide_thresh);
     setCookie('use_count', use_count);
     setCookie('show_files', show_files);

 });


function showHidden(root) {
	// data is loaded so let's show hidden elements on the page
	// update path field
	document.getElementById('pathinput').value = root.name;
	// show path input
	document.getElementById('path-container').style.display = 'inline-block';
    // show chart buttons div
	document.getElementById('chart-buttons').style.display = 'inline-block';
    // show filetree div
	document.getElementById('tree-wrapper').style.display = 'block';
	// show chart div
	document.getElementById('chart-container').style.display = 'block';
}

function getChildJSON(d) {
	// get json data from Elasticsearch using php data grabber
	//console.log("getting children from Elasticsearch: " + d.name);

    // config references
    chartConfig = {
        target: 'mainwindow',
        data_url: 'd3_data.php?path=' + encodeURIComponent(d.name) + '&filter=' + filter + '&mtime=' + mtime + '&use_count=' + use_count + '&show_files=' + show_files
    };

    // loader settings
    opts = {
        lines: 12, // The number of lines to draw
        length: 6, // The length of each line
        width: 3, // The line thickness
        radius: 7, // The radius of the inner circle
        color: '#EE3124', // #rgb or #rrggbb or array of colors
        speed: 1.9, // Rounds per second
        trail: 40, // Afterglow percentage
        className: 'spinner', // The CSS class to assign to the spinner
    };

    var target = document.getElementById(chartConfig.target);
    // trigger loader
    var spinner = new Spinner(opts).spin(target);
    //console.log(chartConfig.data_url)

	// load json data and trigger callback
	d3.json(chartConfig.data_url, function (error, data) {

		// display error if data has error message
		if ((data && data.error) || error) {
			spinner.stop();
			console.warn("Elasticsearch data fetch error: " + error);
			//document.getElementById('error').style.display = 'block';
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
    // get new json data from ES if filters changed
	if ($_GET('filter') !== getCookie('filter') || $_GET('mtime') !== getCookie('mtime') || $_GET('use_count') !== getCookie('use_count') || $_GET('show_files') !== getCookie('show_files')) {
		console.log("removing json data on local storage because filters changed");
		sessionStorage.removeItem("diskover-filetree");
		getESJsonData();
		return true;
    }
	// get new json data from ES if path changed
	if (root.name !== path) {
		console.log("removing json data on local storage because path changed");
		sessionStorage.removeItem("diskover-filetree");
		getESJsonData();
		return true;
	} else if (root.name === path) {
		// json data on local storage is same as path so lets show the visuals
		console.log("json data in storage same as path, load visuals");
		loadVisualizations();
		return true;
	}

	function getESJsonData() {
		// get json data from Elasticsearch using php data grabber
		console.log("no json data in session storage, grabbing from Elasticsearch");

        // config references
        chartConfig = {
            target: 'mainwindow',
            data_url: 'd3_data.php?path=' + path + '&filter=' + filter + '&mtime=' + mtime + '&use_count=' + use_count + '&show_files=' + show_files
        };

        // loader settings
        opts = {
            lines: 12, // The number of lines to draw
            length: 6, // The length of each line
            width: 3, // The line thickness
            radius: 7, // The radius of the inner circle
            color: '#EE3124', // #rgb or #rrggbb or array of colors
            speed: 1.9, // Rounds per second
            trail: 40, // Afterglow percentage
            className: 'spinner', // The CSS class to assign to the spinner
        };

        var target = document.getElementById(chartConfig.target);
		// trigger loader
		var spinner = new Spinner(opts).spin(target);
        console.log(chartConfig.data_url)
		// load json data from Elasticsearch
		d3.json(chartConfig.data_url, function (error, data) {

			// display error if data has error message
			if ((data && data.error) || error) {
				spinner.stop();
				console.warn("Elasticsearch data fetch error: " + error);
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

		// load file tree
		updateTree(root, root);

		// load file size/count pie chart
		changePie(root);

		// load file extension pie chart
		changePieFileExt(root);

        // load filesizes bar chart
		changeBarFileSizes(root);

		// load mtime bar chart
		changeBarMtime(root);
	}

}

function toggleChildren(d) {
    if (d.children) {
        d._children = d.children;
        d.children = null;
    } else if (d._children) {
        d.children = d._children;
        d._children = null;
    }
}

function click(d) {
    //console.log(d)
    if (d.name == root.name) {
        return null;
    }
    if (d.count > 1 && !d.children && !d._children) {
        // check if there are any children in Elasticsearch
        getChildJSON(d);
    } else if (d._children) {
        toggleChildren(d);
        updateTree(root, d);
        setTimeout(function() { changePie(d) },500);
        setTimeout(function() { changePieFileExt(d) },500);
        setTimeout(function() { changeBarFileSizes(d) },500);
        setTimeout(function() { changeBarMtime(d) },500);
    } else if (d.children) {
        toggleChildren(d);
        updateTree(root, d);
        setTimeout(function() { changePie(d.parent) },500);
        setTimeout(function() { changePieFileExt(d.parent) },500);
        setTimeout(function() { changeBarFileSizes(d.parent) },500);
        setTimeout(function() { changeBarMtime(d.parent) },500);
    } else if (!d.count) {
        // display file in search results
        window.open('advanced.php?submitted=true&p=1&filename=' + encodeURIComponent(d.name.split('/').pop()) +'&path_parent=' + encodeURIComponent(d.parent.name),'_blank');
    }
}

function updateTree(data, parent) {
    // update path input
    if (parent.children) {
        document.getElementById('pathinput').value = parent.name;
    } else if (parent._children) {
        document.getElementById('pathinput').value = parent.parent.name;
    }

	var nodes = tree.nodes(data),
			treeduration = 125;

	var nodeEls = ul.selectAll("li.node").data(nodes, function (d) {
		d.id = d.id || ++id;
		return d.id;
	});

	//entered nodes
	var entered = nodeEls.enter().append("li").classed("node", true)
		.style("top", parent.y + "px")
		.style("opacity", 0)
		.style("height", tree.nodeHeight() + "px");

	//add arrows if it is a folder
	entered.append("span").attr("class", function (d) {
		var icon = (d.children) ? " glyphicon-chevron-down" :
			(d._children) ? "glyphicon-chevron-right" : "";
		return "downarrow glyphicon " + icon;
	});

	//add icons for folder for file
	entered.append("span").attr("class", function (d) {
        if (s3_index && d.name === '/s3') {
            var foldericon = "glyphicon-cloud";
        }
        else if (s3_index && d.parent.name === '/s3') {
            var foldericon = "glyphicon-cloud-upload";
        } else {
            var foldericon = "glyphicon-folder-close";
        }
		var icon = (d.count > 0 || d.type === 'directory') ? foldericon : "glyphicon-file";
		return "glyphicon " + icon;
	})
    .style('cursor', 'pointer')
    .on("click", function (d) {
        click(d);
    })
    .on("mouseover", function (d) {
        if (d.count > 1 && !d.children && !d._children) {
            // check if there are any children in Elasticsearch
            getChildJSON(d);
        }
    });

    //add filesize
    entered.append("span").attr("class", function (d) {
            var value = (use_count) ? d.count : d.size;
            var parent_value = (use_count) ? (d.parent) ? d.parent.count : root.count : (d.parent) ? d.parent.size : root.size;
            var percent = (value / parent_value * 100).toFixed(0);
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

    // add percent bar
    entered.append("span").attr("class", "percent")
        .style("width", function (d) {
            var value = (use_count) ? (d.count > 0) ? d.count : 1 : d.size;
            var parent_value = (use_count) ? (d.parent) ? d.parent.count : root.count : (d.parent) ? d.parent.size : root.size;
            var percent = (value / parent_value * 100).toFixed(1);
            return percent + "%";
        });

    // add percent text
    entered.append("span").attr("class", "percent-text")
        .html(function (d) {
            var value = (use_count) ? (d.count > 0) ? d.count : 1 : d.size;
            var parent_value = (use_count) ? (d.parent) ? d.parent.count : root.count : (d.parent) ? d.parent.size : root.size;
            var percent = (value / parent_value * 100).toFixed(1);
            return "(" + percent + "%)";
        });

    // add file and directory counts
    entered.append("span").attr("class", "filecount")
        .html(function (d) {
            return (d.type === 'directory') ? "(" + d.count_files + "/" + d.count_subdirs + ")" : "";
        });

	//add text for filename
	entered.append("span").attr("class", "filename")
        .html(function (d) {
            return d.depth === 0 ? d.name : d.name.split('/').pop();
        })
        .on("click", function (d) {
            click(d);
        })
        .on("mouseover", function (d) {
            d3.select(this).classed("selected", true);
            if (d.count > 1 && !d.children && !d._children) {
                // check if there are any children in Elasticsearch
                getChildJSON(d);
            }
        })
        .on("mouseout", function (d) {
            d3.selectAll(".selected").classed("selected", false);
        });

    //add icons for search button
	entered.append("span").attr("class", "filetree-btns")
        .html(function (d) {
            if (d.count > 0) {
                return '<a target="_blank" href="simple.php?submitted=true&amp;p=1&amp;q=path_parent:' + escapeHTML(d.name) + ' OR path_parent:' + escapeHTML(d.name) + '\\/*"><label title="search" class="btn btn-default btn-xs filetree-btns"><i class="glyphicon glyphicon-search"></i></label></a>';
            }
        });

	//update caret arrow direction
	nodeEls.select("span.downarrow").attr("class", function (d) {
		var icon = d.children ? " glyphicon-chevron-down" :
			d._children || d.count > 0 ? "glyphicon-chevron-right" : "";
		return "downarrow glyphicon " + icon;
	});
	//update position with transition
	nodeEls.transition().duration(treeduration)
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
	.childIndent(20)
	.nodeHeight(20);

var ul = d3.select("#tree-container").append("ul").classed("treelist", "true");
