/*
Copyright (C) Chris Park 2017-2019
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
         if (minhardlinks == 1) {
             minhardlinks = 2;
         }
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
      
        // display hardlinks message if no results
        if (data === undefined) {
             spinner.stop();
             console.warn("No Elasticsearch results");
             document.getElementById('nohardlinks').style.display = 'block';
             deleteCookie("minhardlinks");
             return false;
        }
        // display error if data has error message
        else if (data.error) {
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


function renderHardLinksCharts(data) {

    // display charts container
    document.getElementById('hardlinkscharts-wrapper').style.display = 'block';

    // split data into bar chart and force-directed graph
    var dataset = data[0];
    var links = data[1];

    // hardlinks file count chart

    var totalcount = d3.sum(dataset, function(d) {
        return d.count;
    });

    var width = 500;
    var height = 400;
    var radius = Math.min(width, height) / 2;

    var color2 = d3.scale.category20();

    var min = d3.min(dataset, function(d) {
       return d.count;
    });

    var max = d3.max(dataset, function(d) {
       return d.count;
    });

    /*var color2 = d3.scale.linear()
           .domain([min, max])
           .range(['#555', 'steelblue']);*/

    var color = d3.scale.category20b();

    var svg = d3.select("#hardlinkscountchart")
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .append('g')
        .attr('transform', 'translate(' + width / 2 + ',' + height / 2 + ')');

    var tip = d3.tip()
        .attr('class', 'd3-tip')
        .html(function(d) {
             var files = '';
             for (var i = 0; i < d.data.files.length; i++) {
             files += d.data.files[i] + '<br>\n';
                 if (i === 20){
                   files += "... " + '<br>\n';
                   break;
                 }
             }
             var percent = (d.value / totalcount * 100).toFixed(1) + '%';
             return "<span style='font-size:10px;color:lightgray;max-height:200px;'>" + files + "</span><br><span style='font-size:12px;color:white;'>inode: " + d.data.label + "</span><br><span style='font-size:12px; color:red;'>count: " + d.value + " (" + percent + ")</span>";
        });

    svg.call(tip);

    d3.select("#hardlinkscountchart").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);

    var pie = d3.layout.pie()
        .value(function(d) {
            return d.count;
        })
        //.sort(null);
        .sort(function(a, b) { return d3.descending(a.count, b.count); });

    var path = d3.svg.arc()
        .outerRadius(radius - 10)
        .innerRadius(radius - 70);

    var label = d3.svg.arc()
        .outerRadius(radius - 40)
        .innerRadius(radius - 40);

    var arc = svg.selectAll('.arc')
        .data(pie(dataset))
        .enter().append('g')
        .attr('class', 'arc');

    arc.append('path')
        .attr('d', path)
        .attr('class', path)
        .attr('fill', function(d) {
            return color(d.data.label);
        })
        .on("click", function(d) {
            window.open('advanced.php?&submitted=true&p=1&inode=' + d.data.label + '&doctype=file','_blank');
        })
        .on("mouseover", function(d) {
            tip.show(d);
            d3.selectAll("circle").filter('.inode' + d.data.label).transition()
             .duration(250)
             .attr("r", 8)
             .style("stroke", "red");
        })
        .on("mouseout", function(d) {
            tip.hide(d);
            d3.selectAll("circle").filter('.inode' + d.data.label).transition()
             .duration(250)
             .attr("r", function(d) { if (d.inode) { return 5; } else { return 6; } })
             .style("stroke", "black");
        })
        .on('mousemove', function() {
            if (d3.event.pageY > window.innerHeight - 50) {
                // change tip for bottom of screen
                return tip
                    .style("top", (d3.event.pageY - 40) + "px")
                    .style("left", (d3.event.pageX + 10) + "px");
            } else if (d3.event.pageX > window.innerWidth - 350) {
                // change tip for right side of screen
                return tip
                    .style("top", (d3.event.pageY + 10) + "px")
                    .style("left", (d3.event.pageX - 350) + "px");
            } else {
                return tip
                    .style("top", (d3.event.pageY - 10) + "px")
                    .style("left", (d3.event.pageX + 10) + "px");
            }
        });


    // Bar chart (hardlink sizes)

    var valueLabelWidth = 40; // space reserved for value labels (right)
    var barHeight = 12; // height of one bar
    var barLabelWidth = 120; // space reserved for bar labels
    var barLabelPadding = 10; // padding between bar and bar labels (left)
    var gridChartOffset = 0; // space between start of grid and first bar
    var maxBarWidth = 400; // width of the bar with the max value

    var totalsize = d3.sum(dataset, function(d) {
        return d.size;
    });

    // svg container element
    var svg = d3.select('#filesizechart').append("svg")
        .attr('width', maxBarWidth + barLabelWidth + valueLabelWidth + barLabelPadding)
        .attr('height', '600px');

    svg.append("g")
        .attr("class", "bars");
    svg.append("g")
        .attr("class", "barvaluelabel");
    svg.append("g")
        .attr("class", "barlabel");

    /* ------- TOOLTIP -------*/

    var tip2 = d3.tip()
        .attr('class', 'd3-tip')
        .html(function(d) {
            var files = '';
            for (var i = 0; i < d.files.length; i++) {
                files += d.files[i] + '<br>\n';
                if (i === 20){
                    files += "... " + '<br>\n';
                    break;
                }
            }
            var percent = (d.size / totalsize * 100).toFixed(1) + '%';
            return "<span style='font-size:10px;color:lightgray;max-height:200px;'>" + files + "</span><br><span style='font-size:12px; color:white;'>inode: " + d.label + "</span><br><span style='font-size:12px; color:red;'>size: " + format(d.size) + " (" + percent + ")</span>";
        });

    svg.call(tip2);

    d3.select("#filesizechart").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);

    /* ------- BARS -------*/

    // accessor functions
    var barLabel = function(d) {
        return d['label'].toString();
    };
    var barValue = function(d) {
        return d['size'];
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
        .data(dataset.sort(function(x, y) { return d3.descending(x.size, y.size); }));

    bar.enter().append("rect")
        .attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')')
        .attr('height', yScale.rangeBand())
        .attr('y', y)
        .attr('class', 'bars')
        .style('fill', function(d) {
            return color(d.label);
        })
        .attr('width', function(d) {
            return x(barValue(d));
        })
        .on("click", function(d) {
            window.open('advanced.php?&submitted=true&p=1&inode=' + d.label + '&doctype=file','_blank');
        })
        .on("mouseover", function(d) {
            tip2.show(d)
            d3.selectAll("circle").filter('.inode' + d.label).transition()
              .duration(250)
              .attr("r", 8)
              .style("stroke", "red");
        })
        .on('mouseout', function(d) {
            tip2.hide(d)
            d3.selectAll("circle").filter('.inode' + d.label).transition()
              .duration(250)
              .attr("r", function(d) { if (d.inode) { return 5; } else { return 6; } })
              .style("stroke", "black");
        })
        .on('mousemove', function() {
            return tip2
            .style("top", (d3.event.pageY - 10) + "px")
            .style("left", (d3.event.pageX + 10) + "px");
        })
        .on("click", function(d) {
            window.open('advanced.php?&submitted=true&p=1&inode=' + d.label + '&doctype=file','_blank');
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
            return format(barValue(d));
        });

    barvaluelabel.exit().remove();

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
