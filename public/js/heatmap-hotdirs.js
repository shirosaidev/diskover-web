/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * d3 Heat map for diskover-web
 */

$(document).ready(function() {

    getJSONHeatmap();

    // set cookies
     setCookie('path', encodeURIComponent(path));
     setCookie('filter', filter);
     setCookie('mtime', mtime);
     setCookie('min_change_percent', min_change_percent);
     setCookie('show_new_dirs', show_new_dirs);

});

// add filtersto statustext
var min_change_percent = ($_GET('min_change_percent')) ? parseFloat($_GET('min_change_percent')) : getCookie('min_change_percent');
if (min_change_percent === "") { min_change_percent = 1.0 };
var status_filter = 'minsize:' + format(filter) + ', ';
var status_mtime = ' mtime:' + mtime + ', ';
document.getElementById('statusfilters').append(status_filter);
document.getElementById('statusfilters').append(status_mtime);
document.getElementById('statusminchangepercent').innerHTML = ' min_change_percent:' + min_change_percent + '%';

function getESJsonDataHeatMap() {

    // config references
    var chartConfig = {
        target: 'heatmap-container',
        data_url: 'd3_data_hm.php?path=' + path + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + '&use_count=' + use_count + '&show_files=' + show_files + '&min_change_percent=' + min_change_percent
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

        // display error if data has error message or no index2 selected
        if ((data && data.error) || error) {
            spinner.stop();
            console.warn("Elasticsearch data fetch error: " + error);
            return false;
        }

        console.log("storing json data in session storage");
        // store in session Storage
        sessionStorage.setItem('diskover-heatmap', JSON.stringify(data));

        // stop spin.js loader
        spinner.stop();

        renderTreeMap(data);

        console.timeEnd('loadtime-treemap');

    });
}

function changeTreeMap(node) {
    var path = node.name;

    // config references
    var chartConfig = {
        target: 'heatmap-container',
        data_url: 'd3_data_hm.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + '&use_count=' + use_count + '&show_files=' + show_files + '&min_change_percent=' + min_change_percent
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

        root = data;

        // stop spin.js loader
        spinner.stop();

        // load d3 visual
        renderTreeMap(data);

    });
}

function renderTreeMap(data) {

    svg.selectAll('.cell').remove();
    svg.selectAll('.celllabel').remove();

    var data_index = data[0],
        data_index2 = data[1];

    var treemap = d3.layout.treemap()
        .round(false)
        .size([w, h])
        .sticky(false)
        .value(function(d) {
            var val = (use_count) ? d.count : d.size;
            return val;
        });

    node = root = data_index;
    node2 = root2 = data_index2;

    var nodes = treemap.nodes(root)
        .filter(function(d) {
            return !d.children;
        });

    var nodes2 = treemap.nodes(root2)
        .filter(function(d) {
            return !d.children;
        });

    // add index2 data (value) to nodes as value2 and add change_percent
    nodes.forEach(function(d) {
        var result = nodes2.filter(function(a) {
            return (a.name === d.name);
        });
        d.value2 = (result[0] !== undefined) ? result[0].value : 0;
        if (d.value2 !== 0 && d.value !== 0) {
            d.change_percent = changePercent(d.value, d.value2);
        } else if (d.value2 === 0 && d.value === 0) {
            d.change_percent = 0;
        } else {
            d.change_percent = 100.0;
        }
        if (d.change_percent < min_change_percent) {
            nodes.slice(d,1);
        }
    });
    //console.log(nodes);

    // add node data to heatmap data list
    heatmapdata = [];
    nodes.forEach(function(d) {
        var x = d.x + (d.dx / 2);
        var y = d.y + (d.dy / 2);
        if (d.change_percent !== 0) {
            heatmapdata.push([x, y, d.change_percent]);
        }
    });
    //console.log(heatmapdata)

    var cell = svg.selectAll("g").data(nodes);

    min = d3.min(nodes, function(d) { return d.change_percent; });
    max = d3.max(nodes, function(d) { return d.change_percent; });

    // draw heatmap
    get('maxs').min = min;
    get('maxs').max = max;
    if (sortorder == 'desc') {
        get('maxs').value = max;
    } else if (sortorder == 'asc') {
        get('maxs').value = min;
    } else {
        get('maxs').value = max;
    }
    draw();

    var color = d3.scale.linear()
                .domain([min, 0, max])
                .range(["#29FE2F", "#444444", "#FD0D1B"]);

    cell.enter().append("g")
        .attr("class", "cell")
        .attr("transform", function(d) {
            return "translate(" + d.x + "," + d.y + ")";
        })
        .on("click", function(d) {
            location.href = 'hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + encodeURIComponent(d.parent.name) + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + '&use_count=' + use_count + '&show_files=' + show_files + '&sort=' + sort + '&sortdorder=' + sortorder;
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
            return color(d.change_percent);
        })
        .attr("rx", 4);

    cell
        .style("fill", function(d) {
            return color(d.change_percent);
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
        //zoom(root);
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

            var rootval = (use_count) ? (node || root).count : (node || root).size;
            var rootval2 = (use_count) ? (node2 || root2).count : (node2 || root2).size;
            var percent = (d.value / rootval * 100).toFixed(1) + '%';
            var percent2 = (d.value2 / rootval2 * 100).toFixed(1) + '%';
            var sum = (use_count) ? d.value : format(d.value);
            var sum2 = (use_count) ? d.value2 : format(d.value2);
            var change_percent = d3.round(d.change_percent, 2);
            var color = "white";
            var increase_decrease = "";
            if (change_percent > 0) {
                increase_decrease = '<i class="glyphicon glyphicon-chevron-up"></i> +';
                color = "#FD0D1B";
            } else if (change_percent < 0) {
                increase_decrease = '<i class="glyphicon glyphicon-chevron-down"></i>';
                color = "#29FE2F";
            }

            return "<span style='font-size:12px;color:white;'>" + d.name + "</span><br>current: <span style='color:#FD0D1B;'>" + sum + " (" + percent + ")</span><br>previous: <span style='color:white;'>" + sum2 + " (" + percent2 + ")</span><br></span>change: <span style='color:" + color + ";'>" + increase_decrease + change_percent + "%</span></span>";
        });

    svg.call(tip);

    d3.select("#heatmap-container").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);
}

function getJSONHeatmap() {
    console.time('loadtime-treemap')

    // check if json data stored in session storage
    root = JSON.parse(sessionStorage.getItem("diskover-heatmap"));

    // get data from Elasticsearh if no json in session storage
    if (!root) {
        getESJsonDataHeatMap();
        return true;
    }
    // get new json data from ES if filters changed
    if ($_GET('filter') !== getCookie('filter') || $_GET('mtime') !== getCookie('mtime') || $_GET('use_count') !== getCookie('use_count')) {
        console.log("removing json data on local storage because filters changed");
        sessionStorage.removeItem("diskover-heatmap");
        getESJsonDataHeatMap();
        return true;
    }
    // get new json data from ES if path changed
    if (root[0].name !== path) {
        console.log("removing json data on local storage because path changed");
        sessionStorage.removeItem("diskover-heatmap");
        getESJsonDataHeatMap();
        return true;
    } else if (root[0].name === path) {
        // json data on local storage is same as path so lets show the visuals
        console.log("json data in storage same as path, load visuals");
        renderTreeMap(root);
        return true;
    }
}


// display message if no index2 selected
if (index2 === "") {
    document.getElementById('index2req').style.display = 'block';
} else {
    // show main window
    document.getElementById('mainwindow').style.display = 'block';
    var root,
        node,
        min,
        max;

    console.log("PATH:" + path);
    console.log("SIZE_FILTER:" + filter);
    console.log("MTIME_FILTER:" + mtime);
    console.log("MAXDEPTH:" + maxdepth);
    console.log("USECOUNT:" + use_count);
    console.log("SHOWNEWDIRS:" + show_new_dirs);
    console.log("MINCHANGEPERCENT:" + min_change_percent);
    console.log("SORT:" + sort);
    console.log("SORTORDER:" + sortorder);

    // d3 treemap
    var w = window.innerWidth - 40,
        h = (window.innerHeight / 2) - 83,
        x = d3.scale.linear().range([0, w]),
        y = d3.scale.linear().range([0, h]);

    var svg = d3.select("#heatmap-container")
        .append("svg")
        .attr("class", "chart")
        .style("width", w + "px")
        .style("height", h + "px")
        .attr("width", w)
        .attr("height", h)
        .append("g")
        .attr("transform", "translate(.5,.5)");


    // heatmap

    window.requestAnimationFrame = window.requestAnimationFrame || window.mozRequestAnimationFrame ||
                                   window.webkitRequestAnimationFrame || window.msRequestAnimationFrame;

    function get(id) {
        return document.getElementById(id);
    }

    get('heatmap-overlay').width = w;
    get('heatmap-overlay').height = h;

    var heatmapdata = [];

    var heat = simpleheat('heatmap-overlay'),
        frame;

    function draw() {
        console.time('draw');
        heat.clear();
        heat.data(heatmapdata);
        heat.max(+maxs.value);
        console.log('HEATMAP_MAX:'+maxs.value)
        heat.radius(+radius.value, +blur.value);
        console.log('HEATMAP_RADIUS:'+radius.value)
        console.log('HEATMAP_BLUR:'+blur.value)
        heat.draw();
        console.timeEnd('draw');
        frame = null;
    }

    var radius = get('radius'),
        blur = get('blur'),
        maxs = get('maxs'),
        changeType = 'oninput' in radius ? 'oninput' : 'onchange';

    radius[changeType] = blur[changeType] = maxs[changeType] = function (e) {
        frame = frame || window.requestAnimationFrame(draw);
    };
}
