<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; Sunburst Chart</title>
  <link rel="stylesheet" href="/css/bootstrap.min.css" media="screen" />
  <link rel="stylesheet" href="/css/bootstrap-theme.min.css" media="screen" />
  <link rel="stylesheet" href="/css/diskover.css" media="screen" />
</head>
<style>
path {
  stroke: #fff;
  fill-rule: evenodd;
}
.d3-tip {
  line-height: 1;
  font-weight: bold;
  padding: 12px;
  background: rgba(0, 0, 0, 0.8);
  color: #fff;
  border-radius: 2px;
}
/* Creates a small triangle extender for the tooltip */
.d3-tip:after {
  box-sizing: border-box;
  display: inline;
  font-size: 10px;
  width: 100%;
  line-height: 1;
  color: rgba(0, 0, 0, 0.8);
  content: "\25BC";
  position: absolute;
  text-align: center;
}
/* Style northward tooltips differently */
.d3-tip.n:after {
  margin: -1px 0 0 0;
  top: 100%;
  left: 0;
}
</style>
<body>
  <script language="javascript" src="/js/jquery.min.js"></script>
<script language="javascript" src="/js/bootstrap.min.js"></script>
<script language="javascript" src="/js/d3.v3.min.js"></script>
<script language="javascript" src="/js/d3.tip.v0.6.3.js"></script>
<script language="javascript" src="/js/spin.min.js"></script>

<div class="container-fluid">
  <div class="row">
    <div class="col-xs-4 col-xs-offset-8">
      <div class="pull-right">
      <div class="btn-group" data-toggle="buttons">
        <button class="btn btn-primary" id="size"> Size</button>
        <button class="btn btn-primary active" id="count"> Count</button>
      </div>
      <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Filter
        <span class="caret"></span></button>
        <ul class="dropdown-menu">
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=1000000" target="_parent">>1 MB (default)</a></li>
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=2000000" target="_parent">>2 MB</a></li>
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=5000000" target="_parent">>5 MB</a></li>
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=10000000" target="_parent">>10 MB</a></li>
        </ul>
      </div>
  </div>
</div>
  <div class="row">
    <div class="col-xs-12" id="sunburst-container">
    </div>
  </div>
</div>

<!-- spin loader -->
<script>

var loc = "<?php echo $_GET['path']; ?>";
loc = encodeURIComponent(loc);
var filter = "<?php echo $_GET['filter']; ?>";

// config references
var chartConfig = {
    target : 'sunburst-container',
    data_url : 'd3_data.php?path='+loc+'&type=files&filter='+filter
};

// loader settings
var opts = {
  lines: 9, // The number of lines to draw
  length: 9, // The length of each line
  width: 5, // The line thickness
  radius: 14, // The radius of the inner circle
  color: '#EE3124', // #rgb or #rrggbb or array of colors
  speed: 1.9, // Rounds per second
  trail: 40, // Afterglow percentage
  className: 'spinner', // The CSS class to assign to the spinner
};

// loader settings
var target = document.getElementById(chartConfig.target);

// callback function wrapped for loader in 'init' function
function init() {

    // trigger loader
    var spinner = new Spinner(opts).spin(target);

    // load json data and trigger callback
    d3.json(chartConfig.data_url, function(data) {

        // stop spin.js loader
        spinner.stop();

        if (!data.warning && !data.info) {
          // instantiate chart within callback
          createSunburst(data);
        }

    });
}

</script>

<!-- d3 chart -->

<script>

// d3 sunburst

function createSunburst(data) {

  node = data;

  var path = svg.datum(data).selectAll("path")
      .data(partition.nodes)
      .enter().append("path")
      //.attr("display", function(d) { return d.depth ? null : "none"; }) // hide inner ring
      .attr("d", arc)
      .style("fill", function(d) { return color((d.children ? d : d.parent).name); })
      .on("click", click)
      .on('mouseover', tip.show)
      .on('mouseout', tip.hide)
      .each(stash);

  function stash(d) {
    d.x0 = d.x;
    d.dx0 = d.dx;
  }

  d3.select("#size").on("click", function() {
    path
        .data(partition.value(function(d) { return d.size; }).nodes)
      .transition()
        .duration(1000)
        .attrTween("d", arcTweenData);

   d3.select("#size").classed("active", true);
   d3.select("#count").classed("active", false);
  });

  d3.select("#count").on("click", function change() {
    path
        .data(partition.value(function(d) { return 1; }).nodes)
      .transition()
        .duration(1000)
        .attrTween("d", arcTweenData);

   d3.select("#size").classed("active", false);
   d3.select("#count").classed("active", true);
  });

  function click(d) {
    node = d;
    path.transition()
      .duration(1000)
      .attrTween("d", arcTweenZoom(d));
  }

}

// When switching data: interpolate the arcs in data space.
function arcTweenData(a, i) {
  var oi = d3.interpolate({x: a.x0, dx: a.dx0}, a);
  function tween(t) {
    var b = oi(t);
    a.x0 = b.x;
    a.dx0 = b.dx;
    return arc(b);
  }
  if (i == 0) {
   // If we are on the first arc, adjust the x domain to match the root node
   // at the current zoom level. (We only need to do this once.)
    var xd = d3.interpolate(x.domain(), [node.x, node.x + node.dx]);
    return function(t) {
      x.domain(xd(t));
      return tween(t);
    };
  } else {
    return tween;
  }
}

// When zooming: interpolate the scales.
function arcTweenZoom(d) {
  var xd = d3.interpolate(x.domain(), [d.x, d.x + d.dx]),
      yd = d3.interpolate(y.domain(), [d.y, 1]),
      yr = d3.interpolate(y.range(), [d.y ? 20 : 0, radius]);
  return function(d, i) {
    return i
        ? function(t) { return arc(d); }
        : function(t) { x.domain(xd(t)); y.domain(yd(t)).range(yr(t)); return arc(d); };
  };
}

var margin = {top: 20, right: 10, bottom: 20, left: 10},
    width = parseInt(d3.select('#sunburst-container').style('width'), 10),
    width = width - margin.left - margin.right,
    height = Math.ceil((width * 3) / 4) - margin.top - margin.bottom,
    radius = Math.min(width, height) / 2;

var x = d3.scale.linear()
    .range([0, 2 * Math.PI]);

var y = d3.scale.sqrt()
    .range([0, radius]);

var color = d3.scale.category20c();

var svg = d3.select("#sunburst-container").append("svg")
  .attr("width", width)
  .attr("height", height)
  .append("g")
  .attr("transform", "translate(" + width / 2 + "," + (height / 2 + 10) + ")");

var partition = d3.layout.partition()
    .sort(null)
    .value(function(d) { return 1; });

// Keep track of the node that is currently being displayed as root
var node;

var arc = d3.svg.arc()
    .startAngle(function(d) { return Math.max(0, Math.min(2 * Math.PI, x(d.x))); })
    .endAngle(function(d) { return Math.max(0, Math.min(2 * Math.PI, x(d.x + d.dx))); })
    .innerRadius(function(d) { return Math.max(0, y(d.y)); })
    .outerRadius(function(d) { return Math.max(0, y(d.y + d.dy)); });

var tip = d3.tip()
  .attr('class', 'd3-tip')
  .html(function(d) {
    return "<strong>File:</strong> <span style='color:red'>" + d.name + "</span>" +
      "<br><strong>Size (Bytes):</strong> <span style='color:red'>" + d.size + "</span>";
  });

svg.call(tip);

d3.select("#sunburst-container").append("div")
  .attr("class", "tooltip")
  .style("opacity", 0);


init();

</script>

</body>
</html>
