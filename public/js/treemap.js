/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * d3 Tree map for diskover-web
 */

$(document).ready(function() {

    getJSONTreemap();

});

// add filtersto statustext
var hide_thresh = (getCookie('hide_thresh')) ? parseFloat(getCookie('hide_thresh')) : HIDE_THRESH;
var status_filter = 'minsize:' + format(filter) + ', ';
var status_mtime = ' mtime:' + mtime;
document.getElementById('statusfilters').append(status_filter);
document.getElementById('statusfilters').append(status_mtime);
document.getElementById('statushidethresh').innerHTML = hide_thresh;


function getESJsonDataTreeMap() {

    // config references
    var chartConfig = {
        target: 'treemap-container',
        data_url: 'd3_data_tm.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + '&use_count=' + use_count + '&show_files=' + show_files
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

    // get json data from Elasticsearch using php data grabber
    console.log("no json data in session storage, grabbing from Elasticsearch");

    // load json data from Elasticsearch
    d3.json(chartConfig.data_url, function(error, data) {

        // display error if data has error message
        if (data.error) {
            spinner.stop();
            console.error('Elasticsearch error: ' + JSON.stringify(data));
            document.getElementById('debugerror').innerHTML = 'Elasticsearch error: ' + JSON.stringify(data);
            document.getElementById('error').style.display = 'block';
            return false;
        // display warning if data has 0 items
        } else if (data.count === 0 && data.size === 0) {
            spinner.stop();
            console.warn('No docs found in Elasticsearch');
            document.getElementById('warning').style.display = 'block';
            return false;
        }

        console.log("storing json data in session storage");
        // store in session Storage
        sessionStorage.setItem('diskover-treemap', JSON.stringify(data));

        root2 = data

        // stop spin.js loader
        spinner.stop();

        console.timeEnd('loadtime-treemap');

        renderTreeMap(data);

    });
}

function changeTreeMap(node) {
    var path = node.name;

    // config references
    var chartConfig = {
        target: 'treemap-container',
        data_url: 'd3_data_tm.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + '&use_count=' + use_count + '&show_files=' + show_files
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
    d3.json(chartConfig.data_url, function(error, data) {

        // display error if data has error message
        if (data.error) {
            spinner.stop();
            console.error('Elasticsearch error: ' + JSON.stringify(data));
            document.getElementById('debugerror').innerHTML = 'Elasticsearch error: ' + JSON.stringify(data);
            document.getElementById('error').style.display = 'block';
            return false;
        // display warning if data has 0 items
        } else if (data.count === 0 && data.size === 0) {
            spinner.stop();
            console.warn('No docs found in Elasticsearch');
            document.getElementById('warning').style.display = 'block';
            return false;
        }

        root2 = data;

        // stop spin.js loader
        spinner.stop();

        // load d3 visual
        renderTreeMap(data);

    });
}

function changeThreshold(a) {
    hide_thresh = a;
    setCookie('hide_thresh', hide_thresh);
    document.getElementById('statushidethresh').innerHTML = hide_thresh;
    changeTreeMap(node);
}

function treeMapData(data) {
    var items = [];

    data.children.forEach(filterItems)

    function filterItems(item) {
        var val = (use_count) ? (item.count) ? item.count : 0 : item.size;
        var rootval = (use_count) ? data.count : data.size;
        var percent = (val / rootval * 100).toFixed(3);
        if (percent > hide_thresh) {
            items.push(item);
        }
    }

    data.children = items;

    return data;
}

function renderTreeMap(data) {

    // remove existing treemap elements
    svg.selectAll('.cell').remove();

    // remove existing mouse tips
    d3.selectAll(".d3-tip").remove();

    var treemap = d3.layout.treemap()
        .size([w, h])
        .sticky(true)
        .value(function(d) {
            var val = (use_count) ? d.count : d.size;
            return val;
        });

    data = treeMapData(data);

    node = root2 = data;

    var nodes = treemap.nodes(root2)
        .filter(function(d) {
            return !d.children;
        });

    var cell = svg.selectAll("g").data(nodes);

    cell.enter().append("g")
        .attr("class", "cell")
        .attr("transform", function(d) {
            return "translate(" + d.x + "," + d.y + ")";
        })
        .on("click", function(d) {
            //return zoom(node === d.parent ? root : d.parent);
            location.href = 'treemap.php?index=' + index + '&index2=' + index2 + '&path=' + encodeURIComponent(d.parent.name) + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + '&use_count=' + use_count + '&show_files=' + show_files
        })
        .on("mouseover", function(d) {
            tip.show(d);
        })
        .on("mouseout", function(d) {
            tip.hide(d);
        })
        .on('mousemove', function() {
            if (d3.event.pageY > window.innerHeight - 50) {
                // change tip for bottom of screen
                return tip
                    .style("top", (d3.event.pageY - 40) + "px")
                    .style("left", (d3.event.pageX + 10) + "px");
            } else if (d3.event.pageX > window.innerWidth - 200) {
                // change tip for right side of screen
                return tip
                    .style("top", (d3.event.pageY + 10) + "px")
                    .style("left", (d3.event.pageX - 200) + "px");
            } else {
                return tip
                    .style("top", (d3.event.pageY - 10) + "px")
                    .style("left", (d3.event.pageX + 10) + "px");
            }
        })
        .append("rect")
        .attr("left", function(d) {
            return d.dx + "px";
        })
        .attr("top", function(d) {
            return d.dy + "px";
        })
        .attr("width", function(d) {
            return Math.max(0, d.dx - 1) + "px";
        })
        .attr("height", function(d) {
            return Math.max(0, d.dy - 1) + "px";
        })
        .style("fill", function(d) {
            return color(d.parent.name);
        })
        .attr("rx", 2);

    cell
        .style("fill", function(d) {
            return color(d.parent.name);
        })
        .append("text")
        .attr("x", function(d) {
            return d.dx / 2;
        })
        .attr("y", function(d) {
            return d.dy / 2;
        })
        .attr("dy", ".35em")
        .attr("text-anchor", "middle")
        .attr("class", "celllabel")
        .text(function(d) {
            return d.name.split('/').pop();
        })
        .style("opacity", function(d) {
            d.w = this.getComputedTextLength();
            return d.dx > d.w ? 1 : 0;
        });

    cell.exit()
        .remove();

    //d3.select(window).on("click", function() {
    //   zoom(root);
    //});

    function size(d) {
        return d.size;
    }

    function count(d) {
        return d.count;
    }

    /*function zoom(d) {
        var kx = w / d.dx,
            ky = h / d.dy;
        x.domain([d.x, d.x + d.dx]);
        y.domain([d.y, d.y + d.dy]);

        var t = svg.selectAll("g.cell").transition()
            .duration(d3.event.altKey ? 7500 : 750)
            .attr("transform", function(d) {
                return "translate(" + x(d.x) + "," + y(d.y) + ")";
            });

        t.select("rect")
            .attr("width", function(d) {
                return kx * d.dx;
            })
            .attr("height", function(d) {
                return ky * d.dy;
            });

        t.select("text")
            .attr("x", function(d) {
                return kx * d.dx / 2;
            })
            .attr("y", function(d) {
                return ky * d.dy / 2;
            })
            .style("opacity", function(d) {
                return kx * d.dx > d.w ? 1 : 0;
            });

        node = d;
        d3.event.stopPropagation();
    }*/

    /* ------- TOOLTIP -------*/

    var tip = d3.tip()
        .attr('class', 'd3-tip')
        .html(function(d) {

            var rootval = (use_count) ? (node || root2).count : (node || root2).size;
            var percent = (d.value / rootval * 100).toFixed(1) + '%';
            var sum = (use_count) ? d.value : format(d.value);
            var ret = "<span style='font-size:12px;color:white;'>" + d.name + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
            if (d.type === 'directory') {
                ret += "<br><span style='font-size:11px; color:lightgray;'>Items: " + d.count + "</span>";
                ret += "<br><span style='font-size:11px; color:lightgray;'>Items (files): " + d.count_files + "</span>";
                ret += "<br><span style='font-size:11px; color:lightgray;'>Items (subdirs): " + d.count_subdirs + "</span>";
            }
            ret += "<br><span style='font-size:11px; color:lightgray;'>Modified: " + d.modified + "</span>";
            return ret;
        });

    svg.call(tip);

    d3.select("#treemap-container").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);
}

function getJSONTreemap() {
    console.time('loadtime-treemap')

    // check if json data stored in session storage
    root2 = JSON.parse(sessionStorage.getItem("diskover-treemap"));

    // get data from Elasticsearh if no json in session storage
    if (!root2) {
        getESJsonDataTreeMap();
        return true;
    }
    // get new json data from ES if filters changed
    if ($_GET('filter') !== getCookie('filter') || $_GET('mtime') !== getCookie('mtime') || $_GET('use_count') !== getCookie('use_count') || $_GET('show_files') !== getCookie('show_files')) {
        console.log("removing json data on local storage because filters changed");
        sessionStorage.removeItem("diskover-treemap");
        getESJsonDataTreeMap();
        return true;
    }
    // get new json data from ES if path changed
    if (root2.name !== path) {
        console.log("removing json data on local storage because path changed");
        sessionStorage.removeItem("diskover-treemap");
        getESJsonDataTreeMap();
        return true;
    } else if (root2.name === path) {
        // json data on local storage is same as path so lets show the visuals
        console.log("json data in storage same as path, load visuals");
        renderTreeMap(root2);
        return true;
    }
}

var root2,
    node;

(use_count === '' || use_count === 0) ? use_count = 0 : use_count = 1;
(use_count === 1) ? $('#count').addClass('active') : $('#size').addClass('active');

(show_files === '' || show_files === 1) ? show_files = 1 : show_files = 0;
(show_files === 1) ? $('#showfiles').prop('checked', true) : $('#showfiles').prop('checked', false);

console.log("PATH:" + path);
console.log("SIZE_FILTER:" + filter);
console.log("MTIME_FILTER:" + mtime);
console.log("MAXDEPTH:" + maxdepth);
console.log("USECOUNT:" + use_count);
console.log("SHOWFILES:" + show_files);
console.log("HIDETHRESH:" + hide_thresh);

// d3 treemap
var w = parseInt(d3.select('#treemap-wrapper').style('width'), 10) - 15,
    h = parseInt(d3.select('#treemap-wrapper').style('height'), 10) - 15,
    x = d3.scale.linear().range([0, w]),
    y = d3.scale.linear().range([0, h]),
    color = d3.scale.category20b();

var svg = d3.select("#treemap-container")
    .append("svg")
    .attr("class", "chart")
    .style("width", w + "px")
    .style("height", h + "px")
    .attr("width", w)
    .attr("height", h)
    .append("g")
    .attr("transform", "translate(.5,.5)");
