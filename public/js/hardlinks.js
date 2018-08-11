/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * d3 hardlinks analytics for diskover-web
 */

$(document).ready(function() {

     $('#changeminhardlinksbutton').click(function () {
         sessionStorage.removeItem('diskover-hardlinks');
         console.log('changing min hard links');
         var minhardlinks = $('#minhardlinks').val();
         setCookie('minhardlinks', minhardlinks);
         location.href = "hardlinks.php?index=" + index +"&index2=" + index2 + "&path=" + path + "&filter=" + filter + "&mtime=" + mtime + "&minhardlinks=" + minhardlinks;
         return false;
     });

    // set min hardlinks input value
    $('#minhardlinks').val(minhardlinks);

    // set cookies
    setCookie('path', encodeURIComponent(path));
    setCookie('minhardlinks', minhardlinks);

});

function getESJsonData() {

     // config references
     var chartConfig = {
         target: 'mainwindow',
         data_url: 'd3_data_hardlinks.php?path=' + path + '&filter=' + filter + '&mtime=' + mtime + '&minhardlinks=' + minhardlinks
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
        }

        console.log("storing json data in session storage");
        // store in session Storage
        sessionStorage.setItem('diskover-hardlinks', JSON.stringify(data));

        // stop spin.js loader
        spinner.stop();

        renderHardLinksCharts(data);

     });
}

function nodeDepthColor(p) {
    d = (p.match(/\//g) || []).length;
    var color = d3.scale.linear()
        .domain([1, 10])
        .range(['#000', '#333']);
    return color(d);
}

function renderHardLinksCharts(data) {

    // display charts container
    document.getElementById('hardlinkscharts-wrapper').style.display = 'block';

    // split data into bar chart and force-directed graph
    var dataset = data[0];
    var links = data[1];

    // Bar chart (hardlinks counts)

    var valueLabelWidth = 20; // space reserved for value labels (right)
    var barHeight = 10; // height of one bar
    var barLabelWidth = 80; // space reserved for bar labels
    var barLabelPadding = 10; // padding between bar and bar labels (left)
    var gridChartOffset = 0; // space between start of grid and first bar
    var maxBarWidth = 500; // width of the bar with the max value

    var totalcount = d3.sum(dataset, function(d) {
        return d.count;
    });

    var color2 = d3.scale.category20b();

    var min = d3.min(dataset, function(d) {
        return d.count;
    });

    var max = d3.max(dataset, function(d) {
        return d.count;
    });

    var color = d3.scale.linear()
            .domain([min, max])
            .range(['#33376B', '#8C8FC6']);

    // svg container element
    var svg = d3.select('#hardlinkscountbarchart').append("svg")
        .attr('width', 600)
        .attr('height', 600);

    svg.append("g")
        .attr("class", "bars");
    svg.append("g")
        .attr("class", "barvaluelabel");
    svg.append("g")
        .attr("class", "barlabel");

    /* ------- TOOLTIP -------*/

    var tip = d3.tip()
        .attr('class', 'd3-tip')
        .html(function(d) {
            var percent = (d.count / totalcount * 100).toFixed(1) + '%';
            var files = '';
            for (var i = 0; i < d.files.length; i++) {
            files += d.files[i] + '<br>\n';
                if (i === 20){
                  files += "... " + '<br>\n';
                  break;
                }
            }
            return "<span style='font-size:10px;color:lightgray;'>" + files + "</span><br><span style='font-size:12px; color:white;'>inode: " + d.label + "</span><br><span style='font-size:12px; color:white;'>size: " + format(d.size) + "</span><br><span style='font-size:12px; color:red;'>count: " + d.count + " (" + percent + ")</span>";
        });

    svg.call(tip);

    d3.select("#hardlinkscountbarchart").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);

    /* ------- BARS -------*/

    // accessor functions
    var barLabel = function(d) {
        return d['label'];
    };
    var barValue = function(d) {
        return d['count'];
    };

    // scales
    var yScale = d3.scale.ordinal().domain(d3.range(0, dataset.length)).rangeBands([0, dataset.length * barHeight]);
    var y = function(d, i) {
     return yScale(i);
    };
    var yText = function(d, i) {
     return y(d, i) + yScale.rangeBand() / 2;
    };
    var x = d3.scale.linear().domain([0, d3.max(dataset, barValue)]).range([0, maxBarWidth]);

    // bars
    var bar = svg.select(".bars").selectAll("rect")
        .data(dataset.sort(function(a, b) { return d3.descending(a.count, b.count); }));

    bar.enter().append("rect")
        .attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')')
        .attr('height', yScale.rangeBand())
        .attr('y', y)
        .attr('class', 'bars')
        .style('fill', function(d) {
            return color(d.count);
        })
        .attr('width', function(d) {
            return x(barValue(d));
        })
        .on("click", function(d) {
            window.open('advanced.php?&submitted=true&p=1&inode=' + d.label + '&doctype=file','_blank');
        })
        .on("mouseover", function(d) {
            tip.show(d)
            d3.selectAll("circle").filter('.inode' + d.label).transition()
              .duration(250)
              .attr("r", 8)
              .style("stroke", "red");
        })
        .on('mouseout', function(d) {
            tip.hide(d)
            d3.selectAll("circle").filter('.inode' + d.label).transition()
              .duration(250)
              .attr("r", function(d) { if (d.inode) { return 5; } else { return 6; } })
              .style("stroke", "black");
        })
        .on('mousemove', function() {
            return tip
            .style("top", (d3.event.pageY - 10) + "px")
            .style("left", (d3.event.pageX + 10) + "px");
        });

    bar
        .transition().duration(750)
        .attr("width", function(d) {
            return x(barValue(d));
        });

    bar.exit().remove();

    // bar labels
    var barlabel = svg.select(".barlabel").selectAll('text').data(dataset);

    barlabel.enter().append('text')
        .attr('transform', 'translate(' + (barLabelWidth - barLabelPadding) + ',' + gridChartOffset + ')')
        .attr('y', yText)
        .attr("dy", ".35em") // vertical-align: middle
        .attr("class", "barlabel")
        .text(barLabel);

    barlabel.exit().remove();

    // bar value labels
    var barvaluelabel = svg.select(".barvaluelabel").selectAll('text').data(dataset);

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
            return barValue(d);
        });

    barvaluelabel.exit().remove();


    // force-directed graph

    var width = 960,
    height = 600;

    var svg = d3.select("#hardlinkscountgraph").append("svg")
        .attr("width", width)
        .attr("height", height);

    var nodes = {};

    links.forEach(function(link) {
        link.source = nodes[link.source] ||
            (nodes[link.source] = {name: link.source, target: link.target, inode: link.inode, count: link.count});
        link.target = nodes[link.target] ||
            (nodes[link.target] = {name: link.target});
    });

    var force = d3.layout.force()
        //.size([document.getElementById("hardlinkscountgraph").offsetWidth, document.getElementById("hardlinkscountgraph").offsetHeight])
        .size([width, height])
        .nodes(d3.values(nodes))
        .links(links)
        //.gravity(0.1)
        //.charge(-120)
        //.linkDistance(30)
        .start();

    var link = svg.selectAll(".link")
        .data(links)
        .enter().append("line")
        .attr("class", "link")
        .style("stroke-width", function(d) { return Math.sqrt(d.value); })
        .style("stroke", function(d) { return color(d.count); });

    var node = svg.selectAll(".node")
        .data(force.nodes())
        .enter().append("circle")
        //.attr("class", "node")
        .attr("class", function(d) { return 'node inode' + d.inode; })
        .attr("r", function(d) { if (d.inode) { return 5; } else { return 6; } })
        //.style("fill", function(d) { if(d.count) { return color(d.count); } else { return color2(d.name); } })
        .style("fill", function(d) { if(d.count) { return color(d.count); } else { return nodeDepthColor(d.name); } })
        .style("stroke", function(d) { if(!d.count) { return "#222"; } })
        .call(force.drag);

    //node.append("title")
    //  .text(function(d) { return d.name; });

    node
        .on("click", function(d) {
            if (d.inode) {
                window.open('advanced.php?&submitted=true&p=1&inode=' + d.inode + '&doctype=file','_blank');
            } else {
                document.location.href = "hardlinks.php?index=" + index + "&index2=" + index2 + "&path=" + d.name + "&filter=" + filter + "&mtime=" + mtime + "&minhardlinks=" + minhardlinks;
            }
        })
        .on("mouseover", function(d) {
            tip2.show(d)
            d3.select(this).transition()
              .duration(250)
              .attr("r", 8)
              .style('fill', 'white')
            if (d.inode) {
                d3.selectAll("circle").filter('.inode' + d.inode).transition()
                .duration(250)
                .attr("r", 8)
                .style("stroke", "red");
            }
        })
        .on('mouseout', function(d) {
            tip2.hide(d)
            d3.select(this).transition()
              .duration(250)
              .attr("r", function(d) { if (d.inode) { return 5; } else { return 6; } })
              .style("fill", function(d) { if(d.count) { return color(d.count); } else { return nodeDepthColor(d.name); } })
            if (d.inode) {
                d3.selectAll("circle").filter('.inode' + d.inode).transition()
                .duration(250)
                .attr("r", 5)
                .style("stroke", "black");
            }
        })
        .on('mousemove', function() {
            return tip2
            .style("top", (d3.event.pageY - 10) + "px")
            .style("left", (d3.event.pageX + 10) + "px");
        });

    force.on("tick", function() {
        link.attr("x1", function(d) { return d.source.x; })
            .attr("y1", function(d) { return d.source.y; })
            .attr("x2", function(d) { return d.target.x; })
            .attr("y2", function(d) { return d.target.y; });

        node.attr("cx", function(d) { return d.x; })
            .attr("cy", function(d) { return d.y; });
    });

    var tip2 = d3.tip()
         .attr('class', 'd3-tip')
         .html(function(d) {
             if (d.inode) {
                return "<span style='font-size:11px;color:lightgray;'>" + d.name + "</span><br><span style='font-size:11px;color:white;'>inode: " + d.inode + "</span>";
             } else {
                return "<span style='font-size:11px;color:lightgray;'>" + d.name + "</span>";
             }
         });

     svg.call(tip2);

     d3.select("#hardlinkscountgraph").append("div")
         .attr("class", "tooltip")
         .style("opacity", 0);

}

console.time('loadtime')
// check if json data stored in session storage
root = JSON.parse(sessionStorage.getItem("diskover-hardlinks"));

// minimum hard links
var minhardlinks = $('#minhardlinks').val();

console.log('MINHARDLINKS:'+minhardlinks)

// get data from Elasticsearh if no json in session storage
if (!root) {
    getESJsonData();
} else if ($_GET('minhardlinks') != getCookie('minhardlinks') || decodeURIComponent($_GET('path')) != getCookie('path')) {
    getESJsonData();
} else {
    console.log("using cached json data in session storage");
    renderHardLinksCharts(root);
}

console.timeEnd('loadtime');
