/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * crawl stats analytics for diskover-web
 */


function buildHtmlTable(doclist, selector) {
  var columns = addAllColumnHeaders(doclist, selector);

  for (var i = 0; i < doclist.length; i++) {
    var row$ = $('<tr/>');
    for (var colIndex = 0; colIndex < columns.length; colIndex++) {
      var cellValue = doclist[i][columns[colIndex]];
      if (cellValue == null) cellValue = "";
      row$.append($('<td/>').html(cellValue));
    }
    $(selector).append(row$);
  }
}

function addAllColumnHeaders(doclist, selector) {
  var columnSet = [];
  var headerTr$ = $('<tr/>');

  for (var i = 0; i < doclist.length; i++) {
    var rowHash = doclist[i];
    for (var key in rowHash) {
      if ($.inArray(key, columnSet) == -1) {
        columnSet.push(key);
        headerTr$.append($('<th/>').html(key));
      }
    }
  }
  $(selector).append(headerTr$);

  return columnSet;
}



// global data vars for d3
var data;
var sizes; // x
var items; // y
var crawltimes; // r
var dirnames; // text bubble
var paths; // text tip
var bulkdata; // bulk doc indexing rate chart

var indexname = $('#indexname').val();
var numdocs = $_GET('numdocs');

// init d3 charts

var margin = {top: 40, right: 20, bottom: 300, left: 70},
width = 1300 - margin.left - margin.right,
height = 600 - margin.top - margin.bottom;

var svg = d3.select("#crawlstatschart1").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
    .append("g")
    .attr("transform",
          "translate(" + margin.left + "," + margin.top + ")");

var margin2 = {top: 30, right: 20, bottom: 20, left: 70},
width2 = 1300 - margin2.left - margin2.right,
height2 = 200 - margin2.top - margin2.bottom;

var svg2 = d3.select("#crawlstatschart2").append("svg")
    .attr("width", width2 + margin2.left + margin2.right)
    .attr("height", height2 + margin2.top + margin2.bottom)
    .append("g")
    .attr("transform",
        "translate(" + margin2.left + "," + margin2.top + ")");

var margin3 = { top: 20, right: 80, bottom: 40, left: 80 },
height3 = 300 - margin3.top - margin3.bottom,
width3 = 1300 - margin3.left - margin3.right;

var svg3 = d3.select("#bulkindexchart").append("svg")
    .attr("width",width3 + margin3.left + margin3.right)
    .attr("height",height3 + margin3.top + margin3.bottom)
  .append("g")
    .attr("transform", "translate(" + margin3.left + "," + margin3.top + ")");


function getjsondata(refreshcharts) {
    // config references
    var chartConfig = {
        target: 'mainwindow',
        data_url: 'd3_data_crawlstats.php?index=' + indexname + '&numdocs=' + numdocs
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
    if (refreshcharts === false) {
        // trigger loader
        var spinner = new Spinner(opts).spin(target);
    }

    // load json data from Elasticsearch
    d3.json(chartConfig.data_url, function(error, dataset) {

        // update global data vars
        data = dataset.slowestcrawlers;
        sizes = dataset.sizes; // x
        items = dataset.items; // y
        crawltimes = dataset.crawltimes; // r
        dirnames = dataset.dirnames; // text bubble
        paths = dataset.paths; // text tip
        bulkdata = dataset.bulkdocs // bulk docs indexing rate chart
        topcrawltimedata = dataset.workertopcrawltimes // indexing stats tables
        topbulktimedata = dataset.workertopbulktimes // indexing stats tables

        if (refreshcharts === false) {
            // stop spin.js loader
            spinner.stop();
        } else {
            svg.selectAll("*").remove();
            svg2.selectAll("*").remove();
        }

        // load charts
        loadchart1()
        loadchart2()
        loadchart3()

        // load tables
        buildHtmlTable(topcrawltimedata, '#topbycrawltime');
        buildHtmlTable(topbulktimedata, '#topbybulktime');

        // show tables
        document.getElementById('workerindexingstats').style.display = 'block';

    });
}

function loadchart1() {
    // bar stack
    var xData = ['crawltime']; // stack

    var x = d3.scale.ordinal()
        .rangeRoundBands([0, width], .35);

    var y = d3.scale.linear()
        .rangeRound([height, 0]);

    var color = d3.scale.category20c();
    var color2 = d3.scale.category20b();

    var xAxis = d3.svg.axis()
            .scale(x)
            .orient("bottom");

    var yAxis = d3.svg.axis()
        .scale(y)
        .orient("left")
        .ticks(10);

    var dataIntermediate=xData.map(function (c){
        return data.map(function(d) {
            return {x: d.path, y: d[c], items: d.items, filesize: d.filesize};
        });
    });

    var dataStackLayout = d3.layout.stack()(dataIntermediate);

    x.domain(dataStackLayout[0].map(function(d) {
        return d.x;
    }));

    y.domain([0,
        d3.max(dataStackLayout[dataStackLayout.length - 1],
            function (d) { return d.y0 + d.y;})
        ])
        .nice();

    var layer = svg.selectAll(".stack")
        .data(dataStackLayout);

    layer
        .enter().append("g")
        .attr("class", "stack")
        .style("fill", function (d, i) {
            return color(i);
        });

    layer.selectAll("rect")
        .data(function (d) {
            return d;
        })
        .enter().append("rect")
        .attr("x", function (d) {
            return x(d.x);
        })
        .attr("y", function (d) {
            return y(d.y + d.y0);
        })
        .attr("height", function (d) {
            return y(d.y0) - y(d.y + d.y0);
        })
        .attr("width", x.rangeBand())
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
        .on('click', function(d) {
            window.open('simple.php?submitted=true&p=1&q=path_parent:(' + encodeURIComponent(escapeHTML(d.x)) + ' ' + encodeURIComponent(escapeHTML(d.x)) + '\\/*)','_blank');
        });

    layer.transition().duration(250)
        .attr("y", function (d, i) {
            return height - y(d.y + d.y0);
        })
        .attr("height", function (d) {
            return y(d.y0) - y(d.y + d.y0);
        });

    layer.exit()
        .remove()

    // line
    var line = d3.svg.line()
        .x(function (d,i) {
            return x(paths[i]);
        })
        .y(function (d,i) {
            return y(crawltimes[i]) - 20;
        })
        .interpolate("bundle");

    svg.append('path')
        .datum(crawltimes)
        .attr("d", line)
        .attr("class", "line")
        .attr("fill", "none")
        .attr("stroke", "steelblue")
        .attr("stroke-linejoin", "round")
        .attr("stroke-linecap", "round")
        .attr("stroke-width", 1.5);

    // axis
    svg.append("g")
        .attr("class", "axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis)
        .selectAll("text")
          .attr("transform", "rotate(-45)")
          .attr("y", 10)
          .attr("x", 0)
          .attr("dx", "-.8em")
          .attr("dy", ".15em")
          .style("text-anchor", "end");

    svg.append("g")
        .attr("class", "axis")
        .attr("transform", "translate(0,0)")
        .call(yAxis)
        .append("text")
          .attr("transform", "rotate(-90)")
          .attr("y", 0)
          .attr("x", 0)
          .attr("dy", ".71em")
          .style("text-anchor", "end")
          .text("Crawl time (sec)");

    // scatterplot
    var x2 = d3.scale.linear()
        .domain([d3.min(crawltimes), d3.max(crawltimes)])
        .range([width-20, 20 ]);
    var y2 = d3.scale.linear()
      .domain([d3.min(items), d3.max(items)])
      .range([ height-20, 20 ]);
    var r = d3.scale.linear()
      .domain([d3.min(items), d3.max(items)])
      .range([5, 35]);

    var g = svg.append("svg:g")

    g.selectAll('scatterplot')
        .data(items)
        .enter().append("svg:circle")
        .attr("cy", function (d,i) { return y2(d); } )
        .attr("cx", function (d,i) { return x2(crawltimes[i]); } )
        .attr("r", function(d,i){ return r(items[i]);})
        .style("fill", function(d, i){return color2(i);})
        .on("mouseover", function(d,i) {
            tip2.show(i);
        })
        .on("mouseout", function(d,i) {
            tip2.hide(i);
        })
        .on('mousemove', function() {
            if (d3.event.pageY > window.innerHeight - 50) {
                // change tip for bottom of screen
                return tip2
                    .style("top", (d3.event.pageY - 40) + "px")
                    .style("left", (d3.event.pageX + 10) + "px");
            } else if (d3.event.pageX > window.innerWidth - 200) {
                // change tip for right side of screen
                return tip2
                    .style("top", (d3.event.pageY + 10) + "px")
                    .style("left", (d3.event.pageX - 200) + "px");
            } else {
                return tip2
                    .style("top", (d3.event.pageY - 10) + "px")
                    .style("left", (d3.event.pageX + 10) + "px");
            }
        })
        .on('click', function(d,i) {
            window.open('simple.php?submitted=true&p=1&q=path_parent:(' + encodeURIComponent(escapeHTML(paths[i])) + ' ' + encodeURIComponent(escapeHTML(paths[i])) + '\\/*)','_blank');
        });

    g.selectAll('scatterplot')
        .data(items)
        .enter().append("text")
        .attr('class', 'bubble')
        .attr("y", function (d,i) { return y2(d); })
        .attr("x", function (d,i) { return x2(crawltimes[i]); })
        .attr("dx", function(d,i){ return -r(items[i]);})
        .text(function(d, i){return dirnames[i];});

    // tooltips
    var tip = d3.tip()
        .attr('class', 'd3-tip')
        .html(function(d) {
            return "<span style='font-size:12px;color:white;'>" + d.x + "</span><br>\
            <span style='font-size:12px; color:red;'>crawl time: " + d3.round(d.y * 100 / 100, 3) + " sec</span><br>\
            <span style='font-size:12px; color:red;'>items: " + d.items + " ("+format(d.filesize)+")</span>";
        });

    svg.call(tip);

    var tip2 = d3.tip()
        .attr('class', 'd3-tip')
        .html(function(d) {
            return "<span style='font-size:12px;color:white;'>" + paths[d] + "</span><br>\
            <span style='font-size:12px; color:red;'>crawl time: " + d3.round(crawltimes[d] * 100 / 100, 3) + " sec</span><br>\
            <span style='font-size:12px; color:red;'>items: " + items[d] + " ("+format(sizes[d])+")</span>";
        });

    svg.call(tip2);

    d3.select("#crawlstatschart1").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);
}

function loadchart2() {

    // bar stack
    var xData = ['filecount','directorycount']; // stack

    var x = d3.scale.ordinal()
        .rangeRoundBands([0, width2], .35);

    var y = d3.scale.linear()
        .rangeRound([height2, 0]);

    var color = d3.scale.category20b();
    var color2 = d3.scale.category20();

    var xAxis = d3.svg.axis()
            .scale(x)
            .orient("bottom");

    var yAxis = d3.svg.axis()
        .scale(y)
        .orient("left")
        .ticks(10);

    var dataIntermediate=xData.map(function (c){
        return data.map(function(d) {
            return {x: d.path, y: d[c], items: d.items, filesize: d.filesize};
        });
    });

    var dataStackLayout = d3.layout.stack()(dataIntermediate);

    x.domain(dataStackLayout[0].map(function(d) {
        return d.x;
    }));

    y.domain([0,
        d3.max(dataStackLayout[dataStackLayout.length - 1],
            function (d) { return d.y0 + d.y;})
        ])
        .nice();

    var layer = svg2.selectAll(".stack")
        .data(dataStackLayout)
        .enter().append("g")
        .attr("class", "stack")
        .style("fill", function (d, i) {
            return color(i);
        });

    layer.selectAll("rect")
        .data(function (d) {
            return d;
        })
        .enter().append("rect")
        .attr("x", function (d) {
            return x(d.x);
        })
        .attr("y", function (d) {
            return y(d.y + d.y0);
        })
        .attr("height", function (d) {
            return y(d.y0) - y(d.y + d.y0);
        })
        .attr("width", x.rangeBand())
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
        .on('click', function(d) {
            var t = (d.y0===0) ? "file" : "directory";
            window.open('simple.php?submitted=true&p=1&q=path_parent:(' + encodeURIComponent(escapeHTML(d.x)) + ' ' + encodeURIComponent(escapeHTML(d.x)) + '\\/*)&doctype='+t,'_blank');
        });

    // line
    /*var line = d3.svg.line()
        .x(function (d,i) {
            return x(paths[i]);
        })
        .y(function (d,i) {
            return y(crawltimes[i]) - 20;
        })
        .interpolate("bundle");

    svg2.append('path')
        .datum(crawltimes)
        .attr("d", line)
        .attr("class", "line")
        .attr("fill", "none")
        .attr("stroke", "steelblue")
        .attr("stroke-linejoin", "round")
        .attr("stroke-linecap", "round")
        .attr("stroke-width", 1.5);*/

    // axis
    svg2.append("g")
        .attr("class", "axis")
        .attr("transform", "translate(0," + height2 + ")")
        .call(xAxis)
        .selectAll("text")
          .attr("transform", "rotate(-90)")
          .attr("y", 0)
          .attr("x", 0)
          .attr("dx", "-.8em")
          .attr("dy", ".15em")
          .style("text-anchor", "end")
          .style("opacity", 0);

    svg2.append("g")
        .attr("class", "axis")
        .attr("transform", "translate(0,0)")
        .call(yAxis)
        .append("text")
          .attr("transform", "rotate(-90)")
          .attr("y", 0)
          .attr("x", 0)
          .attr("dy", ".71em")
          .style("text-anchor", "end")
          .text("Items (file/dir)");

    // tooltips
    var tip = d3.tip()
        .attr('class', 'd3-tip')
        .html(function(d) {
            var t = (d.y0===0) ? "files" : "dirs"
            return "<span style='font-size:12px;color:white;'>" + d.x + "</span><br>\
            <span style='font-size:12px; color:red;'>"+t+": " + d.y + "</span><br>\
            <span style='font-size:12px; color:red;'>items: " + d.items + " ("+format(d.filesize)+")</span>";
        });

    svg2.call(tip);

    d3.select("#crawlstatschart2").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);

}


function loadchart3() {
    // setup scales - the domain is specified inside of the function called when we load the data
    var xScale = d3.time.scale().range([0, width3]);
    var yScale = d3.scale.linear().range([height3, 0]);
    var xScale_line = d3.time.scale().range([0, width3]);
    var yScale_line = d3.scale.linear().range([height3, 0]);
    var color = d3.scale.ordinal()
        .domain(["docs"])
        .range(["#6BD4E7"]);

    // setup the axes
    var xAxis = d3.svg.axis().scale(xScale).orient("bottom").tickFormat(d3.time.format("%H:%M:%S"));
    var yAxis = d3.svg.axis().scale(yScale).orient("left");

    // create function to parse dates into date objects
    var parseDate = d3.time.format("%Y-%m-%dT%H:%M:%SZ").parse;
    var formatDate = d3.time.format("%Y-%m-%dT%H:%M:%SZ");
    var bisectDate = d3.bisector(function(d) { return d.date; }).left;

    // set the area attributes
    var area = d3.svg.area()
      .interpolate("basis")
      .x(function(d) { return xScale(d.date); })
      .y0(function(d) { return yScale(d.y0); })
      .y1(function(d) { return yScale(d.y0 + d.y); });

    var stack = d3.layout.stack()
        .values(function(d) { return d.values; });

    var line = d3.svg.line()
      .interpolate("basis")
      .x(function(d) { return xScale_line(d.date); })
      .y(function(d) { return yScale_line(d.value); });

    // import data and create chart
    var data = bulkdata.map(function(d) {
      return {
        date: parseDate(d.date),
        docs: +d.docs
      };
    });
    
    // sort data ascending - needed to get correct bisector results
    data.sort(function(a,b) {
      return a.date - b.date;
    });

    // color domain
    color.domain(d3.keys(data[0]).filter(function(key) { return key !== "date"; }));

    // create doctypes array with object for each doctype (file/directory) containing all data
    var doctypes = stack(color.domain().map(function(type) {
      return {
        type: type,
        values: data.map(function(d){
            return {
              date: d.date, 
              y: +d[type]
            };
        })
      };
    }));

    var doctypes_line = color.domain().map(function(type) {
      return {
        type: type,
        values: data.map(function(d){
          return {
            date: d.date, 
            value: +d[type]
          };
        })
      };
    });

    // Find the value of the date with highest total value
    /*
    var maxDateVal = d3.max(data, function(d){
      var vals = d3.keys(d).map(
        function(key){ 
          return key !== "date" ? d[key] : 0 });
      return d3.sum(vals);
    });
    */

    // add domain ranges to the x and y scales
    xScale.domain(d3.extent(data, function(d) { return d.date; }));
    yScale.domain([0, d3.max(doctypes, function(c) { return d3.max(c.values, function(v) { return v.y; }); })
    ]);
    xScale_line.domain(d3.extent(data, function(d) { return d.date; }));
    yScale_line.domain([0, d3.max(doctypes_line, function(c) { return d3.max(c.values, function(v) { return v.value; }); })
    ]);

    // add the x axis
    svg3.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + height3 + ")")
      .call(xAxis)
      .selectAll("text")
          .attr("transform", "rotate(-45)")
          .attr("y", 10)
          .attr("x", 0)
          .attr("dx", "-.8em")
          .attr("dy", ".15em")
          .style("text-anchor", "end");

    // add the y axis
    svg3.append("g")
        .attr("class", "y axis")
        .call(yAxis)
      .append("text")
        .attr("transform","rotate(-90)")
        .attr("y",10)
        .attr("dy",".71em")
        .style("text-anchor","end")
        .text(function() { return "Indexing Rate (docs)"; });
    
    // add the area groups
    var doctype = svg3.selectAll(".doctype")
        .data(doctypes)
      .enter().append("g")
        .attr("class","doctype_bulkindexchart");

    // add the line groups
    var doctype_line = svg.selectAll(".doctype-line")
        .data(doctypes_line)
      .enter().append("g")
        .attr("class","doctype-line_bulkindexchart");

    // add the doctype count paths
    doctype.append("path")
        .attr("class", "area_bulkindexchart")
        .attr("d", function(d) {
        return area(d.values); 
      })
      .style("fill", function(d) { return color(d.type); });

    doctype_line.append("path")
        .attr("class", "line_bulkindexchart")
        .attr("d", function(d) {
        return line(d.values); 
      })
      .style("opacity", 0);

    var legend = svg3.selectAll(".legend_bulkindexchart")
      .data(color.domain()).enter()
      .append("g")
      .attr("class","legend_bulkindexchart")
      .attr("transform", "translate(" + (width3 +20) + "," + 0+ ")");

    legend.append("rect")
      .attr("x", 0) 
      .attr("y", function(d, i) { return 20 * i; })
      .attr("width", 10)
      .attr("height", 10)
      .style("fill", function(d, i) {
        return color(i);}); 
   
    legend.append("text")
      .attr("x", 20) 
      .attr("dy", "0.75em")
      .attr("y", function(d, i) { return 20 * i; })
      .text(function(d) {return d});
      
    legend.append("text")
      .attr("x",0) 
      //.attr("dy", "0.75em")
      .attr("y",-10)
      .text("Type");

    var mouseG = svg3.append("g")
      .attr("class", "mouse-over-effects_bulkindexchart");

    mouseG.append("path") // this is the gray vertical line to follow mouse
      .attr("class", "mouse-line_bulkindexchart")
      .style("stroke", "#555")
      .style("stroke-width", "1px")
      .style("opacity", "0");
      
    var lines = document.getElementsByClassName('line_bulkindexchart');

    var mousePerLine = mouseG.selectAll('.mouse-per-line_bulkindexchart')
      .data(doctypes_line)
      .enter()
      .append("g")
      .attr("class", "mouse-per-line_bulkindexchart");

    mousePerLine.append("circle")
      .attr("r", 7)
      .style("stroke", function(d) {
        return color(d.type);
      })
      .style("fill", "none")
      .style("stroke-width", "1px")
      .style("opacity", "0");

    mousePerLine.append("text")
      .attr("class", "hovertext")
      .attr("transform", "translate(20,3)");

    mouseG.append('svg:rect') // append a rect to catch mouse movements on canvas
      .attr('width', width3) // can't catch mouse events on a g element
      .attr('height', height3)
      .attr('fill', 'none')
      .attr('pointer-events', 'all')
      .on('mouseout', function() { // on mouse out hide line, circles and text
        d3.select(".mouse-line_bulkindexchart")
          .style("opacity", "0");
        d3.selectAll(".mouse-per-line_bulkindexchart circle")
          .style("opacity", "0");
        d3.selectAll(".mouse-per-line_bulkindexchart text")
          .style("opacity", "0");
      })
      .on('mouseover', function() { // on mouse in show line, circles and text
        d3.select(".mouse-line_bulkindexchart")
          .style("opacity", "1");
        d3.selectAll(".mouse-per-line_bulkindexchart circle")
          .style("opacity", "1");
        d3.selectAll(".mouse-per-line_bulkindexchart text")
          .style("opacity", "1");
      })
      .on('mousemove', function() { // mouse moving over canvas
        var mouse = d3.mouse(this);
        d3.select(".mouse-line_bulkindexchart")
          .attr("d", function() {
            var d = "M" + mouse[0] + "," + height3;
            d += " " + mouse[0] + "," + 0;
            return d;
          });

        d3.selectAll(".mouse-per-line_bulkindexchart")
          .attr("transform", function(d, i) {
            //console.log(width/mouse[0])
            var xDate = xScale_line.invert(mouse[0]),
                bisect = d3.bisector(function(d) { return d.date; }).right;
                idx = bisect(d.values, xDate);
            
            var beginning = 0,
                end = lines[i].getTotalLength(),
                target = null;

            while (true){
              target = Math.floor((beginning + end) / 2);
              pos = lines[i].getPointAtLength(target);
              if ((target === end || target === beginning) && pos.x !== mouse[0]) {
                  break;
              }
              if (pos.x > mouse[0])      end = target;
              else if (pos.x < mouse[0]) beginning = target;
              else break; //position found
            }
            
            d3.select(this).select('text')
              .text(function() { return yScale_line.invert(pos.y).toFixed(0); });
              
            return "translate(" + mouse[0] + "," + pos.y +")";
          });
      });
}


$(document).ready(function() {
    // auto refresh crawl stats charts
    var crawlfinished = $('#crawlfinished').val();
    console.log('crawlfinished ' . crawlfinished);
    // load d3 data
    getjsondata(false);
    // auto refresh
    var auto_refresh;
    if (crawlfinished === 'false') {
        autorefresh(3000);
    } else {  // crawl is finished so disable interval
        autorefresh(0);
    }
    function autorefresh(worker_refreshtime) {
        if (worker_refreshtime == 0) {
            clearInterval(auto_refresh);
            $('#autorefresh_off').attr('style', 'color: #33A0D4 !important');
            $('#autorefresh_on').attr('style', 'color: #FFF !important');
        } else {
            auto_refresh = setInterval(
                function () {
                    //d3.selectAll(".d3-tip").remove();
                    // fetch new d3 data
                    getjsondata(true);
                }, worker_refreshtime); // refresh every 3 sec
                $('#autorefresh_on').attr('style', 'color: #33A0D4 !important');
                $('#autorefresh_off').attr('style', 'color: #FFF !important');
        }
    };
});
