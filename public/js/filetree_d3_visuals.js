/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * d3 filetree visuals for diskover-web
 */


// set toggles
(use_count === 1) ? $('#count').addClass('active') : $('#size').addClass('active');
(show_files === 1) ? $('#showfiles').prop('checked', true) : $('#showfiles').prop('checked', false);

var hide_thresh = (getCookie('hide_thresh')) ? parseFloat(getCookie('hide_thresh')) : HIDE_THRESH;

// add filtersto statustext
var status_filter = 'minsize:' + format(filter) + ', ';
var status_mtime = ' mtime:' + mtime + ', ';
document.getElementById('statusfilters').append(status_filter);
document.getElementById('statusfilters').append(status_mtime);
document.getElementById('statushidethresh').innerHTML = ' hide_thresh:' + hide_thresh;

console.log("PATH:" + path);
console.log("SIZE_FILTER:" + filter);
console.log("MTIME_FILTER:" + mtime);
console.log("USECOUNT:" + use_count);
console.log("SHOWFILES:" + show_files);
console.log("HIDETHRESH:" + hide_thresh);

var root,
    node;

// chart animation duration (ms)
var duration = 250;

function changeThreshold(a) {
    hide_thresh = a;
    setCookie('hide_thresh', hide_thresh);
    document.getElementById('statushidethresh').innerHTML = ' hide_thresh:' + hide_thresh;
    changePie(node);
    changePieFileExt(node);
    changeBarMtime(node);
}

function getMtime() {
    if (mtime === '0' || mtime === 'now') {
        var last_mod_time_high = 'now';
    } else if (mtime === '1d') {
        var last_mod_time_high = 'now-1d/d';
    } else if (mtime === '1w') {
        var last_mod_time_high = 'now-1w/d';
    } else if (mtime === '1m') {
        var last_mod_time_high = 'now-1M/d';
    } else if (mtime === '3m') {
        var last_mod_time_high = 'now-3M/d';
    } else if (mtime === '6m') {
        var last_mod_time_high = 'now-6M/d';
    } else if (mtime === '1y') {
        var last_mod_time_high = 'now-1y/d';
    } else if (mtime === '2y') {
        var last_mod_time_high = 'now-2y/d';
    } else if (mtime === '3y') {
        var last_mod_time_high = 'now-3y/d';
    } else if (mtime === '5y') {
        var last_mod_time_high = 'now-5y/d';
    }

    return '* TO ' + last_mod_time_high;
}

/*
 * d3 File size/count Pie chart for diskover-web
 */

var svg = d3.select("#piechart")
    .append("svg")
    .append("g");

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
    .value(function(d) {
        return d.value;
    });

var arc = d3.svg.arc()
    .outerRadius(radius * 0.8)
    .innerRadius(radius * 0.6);

var outerArc = d3.svg.arc()
    .innerRadius(radius * 0.9)
    .outerRadius(radius * 0.9);

svg.attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

var key = function(d) {
    return d.data.label;
};

var color = d3.scale.category20b();

function pieData(data) {

    var labels = [];

    data.children.forEach(addLabels)

    function addLabels(item) {
        var val = (use_count) ? (item.count) ? item.count : 0 : item.size;
        var rootval = (use_count) ? (node || root).count : (node || root).size;
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

function getFileTreeItem(a) {
    var item;
    node.children.forEach(function(d) {
        if (a === d.name) {
            item = d;
        }
    });
    //console.log(item)
    return item;
}

/* ------- TOOLTIP -------*/

var tip = d3.tip()
    .attr('class', 'd3-tip')
    .html(function(d) {

        var rootval = (use_count) ? (node || root).count : (node || root).size;
        var percent = (d.value / rootval * 100).toFixed(1) + '%';
        var sum = (use_count) ? d.value : format(d.value);

        return "<span style='font-size:12px;color:white;'>" + d.data.label + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
    });

svg.call(tip);

var tip_button = d3.tip()
    .attr('class', 'd3-tip')
    .html(function() {
        return "<span style='font-size:12px;color:gray;'>go back</span>";
    });

svg.call(tip_button);

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
        .style("fill", function(d) {
            return color(d.data.label);
        })
        .on("mouseover", function(d) {
            tip.show(d);
            var item = getFileTreeItem(d.data.label);
            if (item.count > 0 && !item.children && !item._children) {
                // check if there are any children in Elasticsearch
                getChildJSON(item);
            }
        })
        .on('mouseout', function(d) {
            tip.hide(d);
        })
        .on('mousemove', function() {
            return tip
                .style("top", (d3.event.pageY - 10) + "px")
                .style("left", (d3.event.pageX + 10) + "px");
        })
        .on('click' ,function(d) {
            var item = getFileTreeItem(d.data.label);
            click(item);
        })
        .attr("class", "slice");

    slice
        .transition().duration(duration)
        .attrTween("d", function(d) {
            this._current = this._current || d;
            var interpolate = d3.interpolate(this._current, d);
            this._current = interpolate(0);
            return function(t) {
                return arc(interpolate(t));
            };
        })

    slice.exit()
        .remove();

    /* ------- TITLE LABEL -------*/

    svg.select(".label").remove();
    svg.select(".label-percent").remove();

    var button = svg.append("circle")
        .style("stroke", "#060606")
        .style("fill", "#1B1D23")
        .style("cursor", "pointer")
        .attr("r", 118)
        .attr("cx", 0)
        .attr("cy", 0)
        .on("mouseover", function() {
            tip_button.show();
            d3.select(this).style("fill", "#121415");
        })
        .on("mouseout", function() {
            tip_button.hide();
            d3.select(this).style("fill", "#1B1D23");
        })
        .on('mousemove', function() {
            return tip_button
                .style("top", (d3.event.pageY - 10) + "px")
                .style("left", (d3.event.pageX + 10) + "px");
        })
        .on('click' ,function() {
            click(node);
        });

    var percent = svg.append("text")
        .attr("dy", "-.5em")
        .attr("class", "label-percent")
        .text(function() {
            var value = (use_count) ? node.count : node.size;
            var parent_value = (use_count) ? (node.parent) ? node.parent.count : root.count : (node.parent) ? node.parent.size : root.size;
            var percent = (value / parent_value * 100).toFixed(1) + '%';
            return percent;
        });

    var info = svg.append("text")
        .attr("dy", "1.5em")
        .attr("class", "label-info")
        .text(function() {
            return node.count + ' items' + ', ' + format(node.size);
        });

    /* ------- TEXT LABELS -------*/

    var text = svg.select(".labels").selectAll("text")
        .data(pie(data), key);

    text.enter()
        .append("text")
        .attr("dy", ".35em")
        .text(function(d) {
            return d.data.label.split('/').pop();
        });

    function midAngle(d) {
        return d.startAngle + (d.endAngle - d.startAngle) / 2;
    }

    text.transition().duration(duration)
        .attrTween("transform", function(d) {
            this._current = this._current || d;
            var interpolate = d3.interpolate(this._current, d);
            this._current = interpolate(0);
            return function(t) {
                var d2 = interpolate(t);
                var pos = outerArc.centroid(d2);
                pos[0] = radius * (midAngle(d2) < Math.PI ? 1 : -1);
                return "translate(" + pos + ")";
            };
        })
        .styleTween("text-anchor", function(d) {
            this._current = this._current || d;
            var interpolate = d3.interpolate(this._current, d);
            this._current = interpolate(0);
            return function(t) {
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

    polyline.transition().duration(duration)
        .attrTween("points", function(d) {
            this._current = this._current || d;
            var interpolate = d3.interpolate(this._current, d);
            this._current = interpolate(0);
            return function(t) {
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
    .append("g");

svg2.append("g")
    .attr("class", "slices");
svg2.append("g")
    .attr("class", "labels");
svg2.append("g")
    .attr("class", "lines");

var width2 = 320,
    height2 = 300,
    radius2 = Math.min(width2, height2) / 2.5;

var color2 = d3.scale.category20b();

var pie2 = d3.layout.pie()
    .sort(null)
    .value(function(d) {
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
    .html(function(d) {

        var rootval = (use_count) ? (node2 || root2).count : (node2 || root2).size;
        var percent = (d.value / rootval * 100).toFixed(1) + '%';
        var sum = (use_count) ? d.value : format(d.value);
        var label = (!d.data.label) ? 'NULLEXT (none)' : d.data.label;

        return "<span style='font-size:12px;color:white;'>extension: " + label + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
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
            var val = (use_count) ? (item.count) ? item.count : 0 : item.size;
            var rootval = (use_count) ? (node2 || root2).count : (node2 || root2).size;
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
        .style("fill", function(d) {
            return color2(d.data.label);
        })
        .on("mouseover", function(d) {
            tip2.show(d)
        })
        .on('mouseout', function(d) {
            tip2.hide(d)
        })
        .on('mousemove', function() {
            return tip2
                .style("top", (d3.event.pageY - 10) + "px")
                .style("left", (d3.event.pageX + 10) + "px");
        })
        .on('click', function(d) {
            var path_parent = node.name;
            var extension = d.data.label;
            if (!d.data.label) {
                extension = '""';
            }
            window.location.href = 'simple.php?submitted=true&p=1&q=extension:' + encodeURIComponent(escapeHTML(extension)) +
            ' AND (path_parent:' + encodeURIComponent(escapeHTML(path_parent)) + 
            ' OR path_parent:' + encodeURIComponent(escapeHTML(path_parent + '/*')) + ') AND filesize:>=' + filter +
            ' AND last_modified:[' + getMtime() + '] AND _type:file&doctype=file';
        })
        .attr("class", "slice");

    slice2
        .transition().duration(duration)
        .attrTween("d", function(d) {
            this._current = this._current || d;
            var interpolate = d3.interpolate(this._current, d);
            this._current = interpolate(0);
            return function(t) {
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
        .text(function(d) {
            return (!d.data.label) ? 'NULLEXT' : d.data.label;
        });

    function midAngle(d) {
        return d.startAngle + (d.endAngle - d.startAngle) / 2;
    }

    text2.transition().duration(duration)
        .attrTween("transform", function(d) {
            this._current = this._current || d;
            var interpolate = d3.interpolate(this._current, d);
            this._current = interpolate(0);
            return function(t) {
                var d2 = interpolate(t);
                var pos = outerArc2.centroid(d2);
                pos[0] = radius2 * (midAngle(d2) < Math.PI ? 1 : -1);
                return "translate(" + pos + ")";
            };
        })
        .styleTween("text-anchor", function(d) {
            this._current = this._current || d;
            var interpolate = d3.interpolate(this._current, d);
            this._current = interpolate(0);
            return function(t) {
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

    polyline2.transition().duration(duration)
        .attrTween("points", function(d) {
            this._current = this._current || d;
            var interpolate = d3.interpolate(this._current, d);
            this._current = interpolate(0);
            return function(t) {
                var d2 = interpolate(t);
                var pos = outerArc2.centroid(d2);
                pos[0] = radius2 * 0.95 * (midAngle(d2) < Math.PI ? 1 : -1);
                return [arc2.centroid(d2), outerArc2.centroid(d2), pos];
            };
        });

    polyline2.exit()
        .remove();

}

function changePieFileExt(node) {
    var path = node.name;

    // config references
    var chartConfig = {
        target: 'piechart-ext',
        data_url: 'd3_data_pie_ext.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime
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

        root2 = data;

        // stop spin.js loader
        spinner.stop();

        // load d3 visual
        loadPieFileExt(data);

    });
}

/*
 * d3 bar chart settings for diskover-web
 */

var valueLabelWidth = 40; // space reserved for value labels (right)
var barHeight = 15; // height of one bar
var barLabelWidth = 80; // space reserved for bar labels
var barLabelPadding = 10; // padding between bar and bar labels (left)
var gridChartOffset = 0; // space between start of grid and first bar
var maxBarWidth = 200; // width of the bar with the max value

/*
 * d3 Mtime bar chart for diskover-web
 */

var root3;

// svg container element
var svg3 = d3.select('#barchart-mtime').append("svg")
    .attr('width', maxBarWidth + valueLabelWidth + barLabelPadding + barLabelWidth);

var color3 = d3.scale.category20b();

svg3.append("g")
    .attr("class", "bars");
svg3.append("g")
    .attr("class", "barvaluelabel");
svg3.append("g")
    .attr("class", "barlabel");

/* ------- TOOLTIP -------*/

var tip3 = d3.tip()
    .attr('class', 'd3-tip')
    .html(function(d) {

        var rootval = (use_count) ? (node3 || root3).count : (node3 || root3).size;
        var percent = (d.value / rootval * 100).toFixed(1) + '%';
        var sum = (use_count) ? d.value : format(d.value);
        var label = d.label;

        return "<span style='font-size:12px;color:white;'>modified: " + label + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
    });

svg3.call(tip3);

d3.select("#barchart-mtime").append("div")
    .attr("class", "tooltip")
    .style("opacity", 0);

function loadBarMtime(data) {

    function barData(data) {

        var labels = [];

        data.children.forEach(addLabels)

        function addLabels(item) {
            var val = (use_count) ? (item.count) ? item.count : 0 : item.size;
            labels.push({
                'label': item.mtime,
                'value': val
            });
        }

        return labels;
    }

    node3 = data;

    data = barData(data);

    /* ------- BARS -------*/

    // accessor functions
    var barLabel = function(d) {
        return d['label'];
    };
    var barValue = function(d) {
        return d['value'];
    };

    // scales
    var yScale = d3.scale.ordinal().domain(d3.range(0, data.length)).rangeBands([0, data.length * barHeight]);
    var y = function(d, i) {
        return yScale(i);
    };
    var yText = function(d, i) {
        return y(d, i) + yScale.rangeBand() / 2;
    };
    var x = d3.scale.linear().domain([0, d3.max(data, barValue)]).range([0, maxBarWidth]);

    // bars
    var bar = svg3.select(".bars").selectAll("rect").data(data);

    bar.enter().append("rect")
        .attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')')
        .attr('height', yScale.rangeBand())
        .attr('y', y)
        .attr('class', 'bars')
        .style('fill', function(d) {
            return color3(d.label);
        })
        .attr('width', function(d) {
            return x(barValue(d));
        })
        .on("mouseover", function(d) {
            tip3.show(d)
        })
        .on('mouseout', function(d) {
            tip3.hide(d)
        })
        .on('mousemove', function() {
            return tip3
                .style("top", (d3.event.pageY - 10) + "px")
                .style("left", (d3.event.pageX + 10) + "px");
        })
        .on('click', function(d) {
            var path_parent = node3.name;
            if (d.label === 'today') {
                var last_mod_time_high = 'now';
                var last_mod_time_low = 'now/d';
            } else if (d.label === 'yesterday') {
                var last_mod_time_high = 'now/d';
                var last_mod_time_low = 'now-1d/d';
            } else if (d.label === '1-7days') {
                var last_mod_time_high = 'now-1d/d';
                var last_mod_time_low = 'now-1w/d';
            } else if (d.label === '8-30days') {
                var last_mod_time_high = 'now-1w/d';
                var last_mod_time_low = 'now-1M/d';
            } else if (d.label === '31-90days') {
                var last_mod_time_high = 'now-1M/d';
                var last_mod_time_low = 'now-3M/d';
            } else if (d.label === '91-180days') {
                var last_mod_time_high = 'now-3M/d';
                var last_mod_time_low = 'now-6M/d';
            } else if (d.label === '181-365days') {
                var last_mod_time_high = 'now-6M/d';
                var last_mod_time_low = 'now-1y/d';
            } else if (d.label === '1-2years') {
                var last_mod_time_high = 'now-1y/d';
                var last_mod_time_low = 'now-2y/d';
            } else if (d.label === '2-3years') {
                var last_mod_time_high = 'now-2y/d';
                var last_mod_time_low = 'now-3y/d';
            } else if (d.label === '3-5years') {
                var last_mod_time_high = 'now-3y/d'
                var last_mod_time_low = 'now-5y/d';
            } else if (d.label === '5-10years') {
                var last_mod_time_high = 'now-5y/d';
                var last_mod_time_low = 'now-10y/d';
            } else if (d.label === 'over 10 years') {
                var last_mod_time_high = 'now-10y/d';
                var last_mod_time_low = '*';
            }
            window.location.href = 'simple.php?submitted=true&p=1&q=(path_parent:' + encodeURIComponent(escapeHTML(path_parent)) + 
            ' OR path_parent:' + encodeURIComponent(escapeHTML(path_parent + '/*')) + 
            ') AND last_modified:[' + last_mod_time_low + ' TO ' + last_mod_time_high + '} AND filesize:>=' + filter + 
            ' AND _type:file&doctype=file';
        });

    bar
        .transition().duration(duration)
        .attr("width", function(d) {
            return x(barValue(d));
        });

    bar.exit().remove();

    // bar labels
    var barlabel = svg3.select(".barlabel").selectAll('text').data(data);

    barlabel.enter().append('text')
        .attr('transform', 'translate(' + (barLabelWidth - barLabelPadding) + ',' + gridChartOffset + ')')
        .attr('y', yText)
        .attr("dy", ".35em") // vertical-align: middle
        .attr("class", "barlabel")
        .text(barLabel);

    barlabel.exit().remove();

    // bar value labels
    var barvaluelabel = svg3.select(".barvaluelabel").selectAll('text').data(data);

    barvaluelabel.enter().append("text")
        .attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')')
        .attr("dx", 3) // padding-left
        .attr("dy", ".35em") // vertical-align: middle
        .attr("class", "barvaluelabel");

    barvaluelabel
        .attr("x", function(d) {
            return x(barValue(d));
        })
        .attr("y", yText)
        .text(function(d) {
            return (use_count) ? (barValue(d)) ? barValue(d) : 0 : format(barValue(d));
        });

    barvaluelabel.exit().remove();

}

function changeBarMtime(node) {

    var path = node.name;

    // config references
    var chartConfig = {
        target: 'barchart-mtime',
        data_url: 'd3_data_bar_mtime.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime
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

        root3 = data;

        // stop spin.js loader
        spinner.stop();

        // load d3 visual
        loadBarMtime(data);

    });
}


/*
 * d3 File Size bar chart for diskover-web
 */

var root4;

// svg container element
var svg4 = d3.select('#barchart-filesizes').append("svg")
    .attr('width', maxBarWidth + barLabelWidth + valueLabelWidth + barLabelPadding);

var color4 = d3.scale.category20b();

svg4.append("g")
    .attr("class", "bars");
svg4.append("g")
    .attr("class", "barvaluelabel");
svg4.append("g")
    .attr("class", "barlabel");

/* ------- TOOLTIP -------*/

var tip4 = d3.tip()
    .attr('class', 'd3-tip')
    .html(function(d) {

        var rootval = (use_count) ? (node4 || root4).count : (node4 || root4).size;
        var percent = (d.value / rootval * 100).toFixed(1) + '%';
        var sum = (use_count) ? d.value : format(d.value);
        var label = d.label;

        return "<span style='font-size:12px;color:white;'>filesize: " + label + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
    });

svg4.call(tip4);

d3.select("#barchart-mtime").append("div")
    .attr("class", "tooltip")
    .style("opacity", 0);

function loadBarFileSizes(data) {

    function barData(data) {

        var labels = [];

        data.children.forEach(addLabels)

        function addLabels(item) {
            var val = (use_count) ? (item.count) ? item.count : 0 : item.size;
            labels.push({
                'label': item.filesize,
                'value': val
            });
        }
        labels.reverse()
        return labels;
    }

    node4 = data;

    data = barData(data);

    /* ------- BARS -------*/

    // accessor functions
    var barLabel = function(d) {
        return d['label'];
    };
    var barValue = function(d) {
        return d['value'];
    };

    // scales
    var yScale = d3.scale.ordinal().domain(d3.range(0, data.length)).rangeBands([0, data.length * barHeight]);
    var y = function(d, i) {
        return yScale(i);
    };
    var yText = function(d, i) {
        return y(d, i) + yScale.rangeBand() / 2;
    };
    var x = d3.scale.linear().domain([0, d3.max(data, barValue)]).range([0, maxBarWidth]);

    // bars
    var bar2 = svg4.select(".bars").selectAll("rect").data(data);

    bar2.enter().append("rect")
        .attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')')
        .attr('height', yScale.rangeBand())
        .attr('y', y)
        .attr('class', 'bars')
        .style('fill', function(d) {
            return color4(d.label);
        })
        .attr('width', function(d) {
            return x(barValue(d));
        })
        .on("mouseover", function(d) {
            tip4.show(d)
        })
        .on('mouseout', function(d) {
            tip4.hide(d)
        })
        .on('mousemove', function() {
            return tip4
                .style("top", (d3.event.pageY - 10) + "px")
                .style("left", (d3.event.pageX + 10) + "px");
        })
        .on('click', function(d) {
            var path_parent = node4.name;
            if (d.label === '0KB-1KB') {
                var filesize_high = 1024;
                var filesize_low = 0;
            } else if (d.label === '1KB-4KB') {
                var filesize_high = 4096;
                var filesize_low = 1024;
            } else if (d.label === '4KB-16KB') {
                var filesize_high = 16384;
                var filesize_low = 4096;
            } else if (d.label === '16KB-64KB') {
                var filesize_high = 65536;
                var filesize_low = 16384;
            } else if (d.label === '64KB-256KB') {
                var filesize_high= 262144;
                var filesize_low = 65536;
            } else if (d.label === '256KB-1MB') {
                var filesize_high = 1048576;
                var filesize_low = 262144;
            } else if (d.label === '1MB-4MB') {
                var filesize_high = 4194304;
                var filesize_low = 1048576;
            } else if (d.label === '4MB-16MB') {
                var filesize_high = 16777216;
                var filesize_low = 4194304;
            } else if (d.label === '16MB-64MB') {
                var filesize_high = 67108864;
                var filesize_low = 16777216;
            } else if (d.label === '64MB-256MB') {
                var filesize_high = 268435456;
                var filesize_low = 67108864;
            } else if (d.label === '256MB-1GB') {
                var filesize_high = 1073741824;
                var filesize_low = 268435456;
            } else if (d.label === '1GB-4GB') {
                var filesize_high = 4294967296;
                var filesize_low = 1073741824;
            } else if (d.label === '4GB-16GB') {
                var filesize_high = 17179869184;
                var filesize_low = 4294967296;
            } else if (d.label === 'over 16GB') {
                var filesize_high = '*';
                var filesize_low = 17179869184;
            }
            window.location.href = 'simple.php?submitted=true&p=1&q=(path_parent:' + encodeURIComponent(escapeHTML(path_parent)) + 
            ' OR path_parent:' + encodeURIComponent(escapeHTML(path_parent + '/*')) + 
            ') AND filesize:[' + filesize_low + ' TO ' + filesize_high + '} AND last_modified:[' + getMtime() + '] AND _type:file&doctype=file';
        });

    bar2
        .transition().duration(duration)
        .attr("width", function(d) {
            return x(barValue(d));
        });

    bar2.exit().remove();

    // bar labels
    var barlabel2 = svg4.select(".barlabel").selectAll('text').data(data);

    barlabel2.enter().append('text')
        .attr('transform', 'translate(' + (barLabelWidth - barLabelPadding) + ',' + gridChartOffset + ')')
        .attr('y', yText)
        .attr("dy", ".35em") // vertical-align: middle
        .attr("class", "barlabel")
        .text(barLabel);

    barlabel2.exit().remove();

    // bar value labels
    var barvaluelabel2 = svg4.select(".barvaluelabel").selectAll('text').data(data);

    barvaluelabel2.enter().append("text")
        .attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')')
        .attr("dx", 3) // padding-left
        .attr("dy", ".35em") // vertical-align: middle
        .attr("class", "barvaluelabel");

    barvaluelabel2
        .attr("x", function(d) {
            return x(barValue(d));
        })
        .attr("y", yText)
        .text(function(d) {
            return (use_count) ? (barValue(d)) ? barValue(d) : 0 : format(barValue(d));
        });

    barvaluelabel2.exit().remove();

}

function changeBarFileSizes(node) {

    var path = node.name;

    // config references
    var chartConfig = {
        target: 'barchart-filesizes',
        data_url: 'd3_data_bar_fs.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime
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

        root4 = data;

        // stop spin.js loader
        spinner.stop();

        // load d3 visual
        loadBarFileSizes(data);

    });
}
