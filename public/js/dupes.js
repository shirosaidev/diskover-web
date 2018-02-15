/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * d3 dupes analytics for diskover-web
 */

function getESJsonData() {

     // config references
     var chartConfig = {
         target: 'mainwindow',
         data_url: 'd3_data_dupes.php'
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
         if ((data && data.error) || error || data === null) {
             spinner.stop();
             console.warn("nothing found in Elasticsearch: " + error);
             document.getElementById('error').style.display = 'block';
             return false;
         }

         console.log("storing json data in session storage");
         // store in session Storage
         sessionStorage.setItem('diskover-dupes', JSON.stringify(data));

         // stop spin.js loader
         spinner.stop();

         renderDupesCharts(data);

     });
}

function renderDupesCharts(dataset) {

     // display charts container
     document.getElementById('dupescharts-wrapper').style.display = 'block';

     var totalcount = d3.sum(dataset, function(d) {
         return d.count;
     });

     var width = 720;
     var height = 500;
     var radius = Math.min(width, height) / 2;

     var color = d3.scale.category20c();

     var svg = d3.select("#dupescountchart")
         .append('svg')
         .attr('width', width)
         .attr('height', height)
         .append('g')
         .attr('transform', 'translate(' + width / 2 + ',' + height / 2 + ')');

     var tip = d3.tip()
         .attr('class', 'd3-tip')
         .html(function(d) {
             var percent = (d.value / totalcount * 100).toFixed(1) + '%';
             return "<span style='font-size:12px;color:white;'>dupe_md5: " + d.data.label + "</span><br><span style='font-size:12px; color:red;'>count: " + d.value + " (" + percent + ")</span>";
         });

     svg.call(tip);

     d3.select("#dupescountchart").append("div")
         .attr("class", "tooltip")
         .style("opacity", 0);

     var pie = d3.layout.pie()
         .value(function(d) {
             return d.count;
         })
         .sort(null);

     var path = d3.svg.arc()
         .outerRadius(radius - 10)
         .innerRadius(0);

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
             document.location.href='advanced.php?>&submitted=true&p=1&dupe_md5=' + d.data.label;
         })
         .on("mouseover", function(d) {
             tip.show(d);
             var files = '';
             d.data.files.forEach(function(f) {
                 files += f + '<br>\n';
             });
             document.getElementById('dupefiles').style.color=color(d.data.label);
             document.getElementById('dupefiles').innerHTML=files;
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
         });


     var totalsize = d3.sum(dataset, function(d) {
         return d.size;
     });

     var width = 720;
     var height = 500;
     var radius = Math.min(width, height) / 2;

     var color = d3.scale.category20c();

     var svg = d3.select("#filesizechart")
         .append('svg')
         .attr('width', width)
         .attr('height', height)
         .append('g')
         .attr('transform', 'translate(' + width / 2 + ',' + height / 2 + ')');

     var tip2 = d3.tip()
         .attr('class', 'd3-tip')
         .html(function(d) {
             var percent = (d.value / totalsize * 100).toFixed(1) + '%';
             return "<span style='font-size:12px;color:white;'>dupe_md5: " + d.data.label + "</span><br><span style='font-size:12px; color:red;'>size: " + format(d.value) + " (" + percent + ")</span>";
         });

     svg.call(tip2);

     d3.select("#filesizechart").append("div")
         .attr("class", "tooltip")
         .style("opacity", 0);

     var pie = d3.layout.pie()
         .value(function(d) {
             return d.size;
         })
         .sort(null);

     var path = d3.svg.arc()
         .outerRadius(radius - 10)
         .innerRadius(0);

     var label = d3.svg.arc()
         .outerRadius(radius - 40)
         .innerRadius(radius - 40);

     var arc = svg.selectAll('.arc')
         .data(pie(dataset))
         .enter().append('g')
         .attr('class', 'arc');

     arc.append('path')
         .attr('d', path)
         .attr('fill', function(d) {
             return color(d.data.label);
         })
         .on("click", function(d) {
             document.location.href='advanced.php?&submitted=true&p=1&dupe_md5=' + d.data.label;
         })
         .on("mouseover", function(d) {
             tip2.show(d);
             var files = '';
             d.data.files.forEach(function(f) {
                 files += f + '<br>\n';
             });
             document.getElementById('dupefiles').style.color=color(d.data.label);
             document.getElementById('dupefiles').innerHTML=files;
         })
         .on("mouseout", function(d) {
             tip2.hide(d);
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
         });

}

console.time('loadtime')
// check if json data stored in session storage
root = JSON.parse(sessionStorage.getItem("diskover-dupes"));

// get data from Elasticsearh if no json in session storage
if (!root) {
    getESJsonData();
} else {
    console.log("using cached json data in session storage");
    renderDupesCharts(root);
}

console.timeEnd('loadtime');
