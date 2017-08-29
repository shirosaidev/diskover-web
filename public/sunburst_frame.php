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
        <button class="btn btn-primary btn-sm" id="size"> Size</button>
        <button class="btn btn-primary active btn-sm" id="count"> Count</button>
      </div>
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

// config references
var chartConfig = {
    target : 'sunburst-container'
};

// loader settings
var opts = {
  lines: 12, // The number of lines to draw
  length: 5, // The length of each line
  width: 3, // The line thickness
  radius: 7, // The radius of the inner circle
  color: '#EE3124', // #rgb or #rrggbb or array of colors
  speed: 1.9, // Rounds per second
  trail: 40, // Afterglow percentage
  className: 'spinner', // The CSS class to assign to the spinner
};

// loader settings
var target = document.getElementById(chartConfig.target);

// callback function wrapped for loader in 'init' function
function init() {

		// get json data from parent window
		var data = window.parent.getJSON();

			if (!data.warning && !data.info) {
				// instantiate chart within callback
				createSunburst(data);
			}
}

</script>

<!-- d3 chart -->

<script>

// format bytes to mb, gb
function formatBytes(a,b){if(0==a)return"0 Bytes";var c=1024,d=b||2,e=["Bytes","KB","MB","GB","TB","PB","EB","ZB","YB"],f=Math.floor(Math.log(a)/Math.log(c));return parseFloat((a/Math.pow(c,f)).toFixed(d))+" "+e[f]}

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
			.on('mousemove', function(d) {
				return tip
					.style("top", (d3.event.pageY-10)+"px")
					.style("left", (d3.event.pageX+10)+"px");
			})
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

// try to scale width and height of sunburst to browser window size
var width = parseInt(document.getElementById("sunburst-container").offsetWidth, 10);
var height = Math.ceil((width * 3) / 4);

// fallback
if (width < 960 || height < 700) {
	var width = 960, height = 700;
}

var margin = {left: 10, right: 10, top: 20, bottom: 20},
		width = width - margin.left - margin.right,
    height = height - margin.top - margin.bottom,
    radius = Math.min(width, height) / 2.1;

var x = d3.scale.linear()
    .range([0, 2 * Math.PI]);

var y = d3.scale.linear()
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
		if(d.count){var type = "<strong><i class='glyphicon glyphicon-folder-open'></i></strong> <span style='color:red'>" + d.name + "</span><br><strong><i class='glyphicon glyphicon-duplicate'></i></strong> <span style='color:red'>" + d.count + "</span>";}else{var type = "<strong><i class='glyphicon glyphicon-file'></i></strong> <span style='color:red'>" + d.name + "</span>";};
    return type +
      "<br><strong><i class='glyphicon glyphicon-floppy-disk'></i></strong> <span style='color:red'>" + formatBytes(d.size) + "</span>";
  });

svg.call(tip);

d3.select("#sunburst-container").append("div")
  .attr("class", "tooltip")
  .style("opacity", 0);


init();

</script>

</body>
</html>
