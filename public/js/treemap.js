/*
 * d3 Tree map for diskover-web
 */

function getESJsonData() {

    // config references
    var chartConfig = {
        target: 'mainwindow',
        data_url: '/d3_data_tm.php?path=' + encodeURIComponent(path) + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth
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
        if ((data && data.error) || error) {
            spinner.stop();
            console.warn("nothing found in Elasticsearch: " + error);
            document.getElementById('error').style.display = 'block';
            return false;
        }

        console.log("storing json data in session storage");
        // store in session Storage
        sessionStorage.setItem('diskover-treemap', JSON.stringify(data));

        // stop spin.js loader
        spinner.stop();

        renderTreeMap(data);

    });
}

function renderTreeMap(data) {

    svg.selectAll('.celllabel').remove();

    var treemap = d3.layout.treemap()
        .round(false)
        .size([w, h])
        .sticky(false)
        .value(function(d) {
            var val = (use_count == true) ? d.count : d.size;
            return val;
        });

    node = root = data;

    var nodes = treemap.nodes(root)
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
            return zoom(node == d.parent ? root : d.parent);
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
        .attr("width", function(d) {
            return d.dx - 1;
        })
        .attr("height", function(d) {
            return d.dy;
        })
        .style("fill", function(d) {
            return color(d.parent.name);
        })
        .attr("rx", 4);

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

    d3.select(window).on("click", function() {
        zoom(root);
    });

    /* ------- MAXDEPTH BUTTONS -------*/

    d3.select("#depth1").on("click", function() {
        maxdepth = 1;
        svg.selectAll('g').remove();
        getESJsonData();
    });
    d3.select("#depth2").on("click", function() {
        maxdepth = 2;
        svg.selectAll('g').remove();
        getESJsonData();
    });
    d3.select("#depth3").on("click", function() {
        maxdepth = 3;
        svg.selectAll('g').remove();
        getESJsonData();
    });
    d3.select("#depth4").on("click", function() {
        maxdepth = 4;
        svg.selectAll('g').remove();
        getESJsonData();
    });
    d3.select("#depth5").on("click", function() {
        maxdepth = 5;
        svg.selectAll('g').remove();
        getESJsonData();
    });

    d3.select("#depth"+maxdepth).classed("active", true);
    for (i = 1; i <= 5; i++) {
        if (i != maxdepth) {
            d3.select("#depth"+i).classed("active", false);
        }
    }

    /* ------- SIZE/COUNT BUTTONS -------*/

    d3.select("#size").on("click", function() {
        setCookie('use_count', 0);
        use_count = 0;
        treemap.value(size).nodes(root);
        d3.select("#size").classed("active", true);
        d3.select("#count").classed("active", false);
    });

    d3.select("#count").on("click", function() {
        setCookie('use_count', 1);
        use_count = 1;
        treemap.value(count).nodes(root);
        d3.select("#size").classed("active", false);
        d3.select("#count").classed("active", true);
    });

    function size(d) {
        return d.size;
    }

    function count(d) {
        return d.count;
    }

    function zoom(d) {
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
                return kx * d.dx - 1;
            })
            .attr("height", function(d) {
                return ky * d.dy - 1;
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
    }

    /* ------- TOOLTIP -------*/

    var tip = d3.tip()
        .attr('class', 'd3-tip')
        .html(function(d) {

            var rootval = (use_count == true) ? (node || root).count : (node || root).size;
            var percent = (d.value / rootval * 100).toFixed(1) + '%';
            var sum = (use_count == true) ? d.value : format(d.value);

            return "<span style='font-size:12px;color:white;'>" + d.name + "</span><br><span style='font-size:12px; color:red;'>" + sum + " (" + percent + ")</span>";
        });

    svg.call(tip);

    d3.select("#treemap-container").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);

    // show treemap buttons
    document.getElementById('buttons-container').style.display = 'inline-block';

    // store cookies
    setCookie('path', root.name);
    setCookie('maxdepth', maxdepth);

    // update analytics links
    updateVisLinks();

}

var path = decodeURIComponent($_GET('path'));
// remove any trailing slash
if (path != '/') {
    path = path.replace(/\/$/, "");
}
var filter = $_GET('filter') || 1048576, // min file size filter
    mtime = $_GET('mtime') || 0, // min modified time filter
    maxdepth = getCookie('maxdepth') || 2, // max directory depth
    root,
    node;

var use_count = getCookie('use_count');
(use_count == '') ? use_count = false: "";
(use_count == 1) ? $('#count').addClass('active'): $('#size').addClass('active');

console.log("PATH:" + path);
console.log("SIZE_FILTER:" + filter);
console.log("MTIME_FILTER:" + mtime);
console.log("MAXDEPTH:" + maxdepth);
console.log("USECOUNT:" + use_count);

console.time('loadtime')

// d3 treemap
var w = window.innerWidth - 40,
    h = window.innerHeight - 140,
    x = d3.scale.linear().range([0, w]),
    y = d3.scale.linear().range([0, h]),
    color = d3.scale.category20c();
    //color = d3.scale.ordinal()
        //.range(["#FFD22E", "#27BCF7", "#FFA226", "#AA86FC", "#FF4A7D", "#75DB51", "#A5A5A7"]);

var svg = d3.select("#treemap-container")
    .append("svg")
    .attr("class", "chart")
    .style("width", w + "px")
    .style("height", h + "px")
    .attr("width", w)
    .attr("height", h)
    .append("g")
    .attr("transform", "translate(.5,.5)");

// check if json data stored in session storage
root = JSON.parse(sessionStorage.getItem("diskover-treemap"));

// get data from Elasticsearh if no json in session storage or path diff
if (!root || (root && root.name != path)) {
    getESJsonData();
} else {
    renderTreeMap(root);
}

console.timeEnd('loadtime');
