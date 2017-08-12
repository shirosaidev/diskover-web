<?php
if (!empty($_GET['path'])) {
  $path = $_GET['path'];
  $path = rtrim($path, '/');
}
if (!empty($_GET['filter'])) {
  $filter = $_GET['filter'];
} else {
  $filter = 1000000;
}
?>

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
.selected{
  color: orange;
}
.node {
  position: absolute;
  list-style: none;
  cursor: default;
  padding-left: 20px;
}
.node span {
  margin-right: 3px;
}

.node .caret {
  font-size: 10px;
}

.sunburst-container {
  position: relative;
  padding-bottom: 75%;
  padding-top: 35px;
  height: 0;
  overflow: hidden;
}

.sunburst-container iframe {
  position: absolute;
  top: 0;
  left: 0;
  border: 0;
  width: 100%;
  height: 100%;
}
</style>
<body>
<?php include __DIR__ . "/nav.php"; ?>

<div class="container" id="warning" style="display:none;">
  <div class="row">
    <div class="alert alert-dismissible alert-danger col-xs-8">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, too many files :(</strong> Choose a different path and try again.
    </div>
  </div>
</div>
<div class="container-fluid">
  <div class="row">
    <div class="col-xs-4" id="tree-container">
    </div>
    <div class="col-xs-8">
      <div class="sunburst-container">
        <iframe name="sunburst" id="sunburst" src="sunburst_frame.php?path=<?php echo urlencode($path); ?>&filter=<?php echo $filter; ?>"></iframe>
      </div>
    </div>
  </div>
</div>

<script language="javascript" src="/js/jquery.min.js"></script>
<script language="javascript" src="/js/bootstrap.min.js"></script>
<script language="javascript" src="/js/diskover.js"></script>
<script language="javascript" src="/js/d3.v3.min.js"></script>
<script language="javascript" src="/js/spin.min.js"></script>
<script language="javascript" src="/js/treelist.js"></script>

<!-- spin loader -->
<script>

var path = encodeURIComponent("<?php echo $path; ?>");

// config references
var chartConfig = {
    target : 'tree-container',
    data_url : 'd3_data.php?path='+path
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

        // display warning if too many files
        if (data.warning) {
          document.getElementById('warning').style.display = 'block';
        } else {
          // instantiate chart within callback
          updateTree(data, data);
          setupTree(data);
          updateTree(data, data);
        }

    });
}

</script>

<!-- d3 chart -->

<script>

// d3 tree

function updateTree(data, parent) {

  var nodes = tree.nodes(data),
      duration = 250;

  function toggleChildren(d) {
    if (d.children) {
        d._children = d.children;
        d.children = null;
    } else if (d._children) {
        d.children = d._children;
        d._children = null;
    }
  }

  var nodeEls = ul.selectAll("li.node").data(nodes, function (d) {
      d.id = d.id || ++id;
      return d.id;
  });
  //entered nodes
  var entered = nodeEls.enter().append("li").classed("node", true)
      .style("top", parent.y +"px")
      .style("opacity", 0)
      .style("height", tree.nodeHeight() + "px")
      .on("click", function (d) {
          toggleChildren(d),
          updateTree(data, d);
          if (d.depth == 0) {
            var loc = parent.name;
          } else if (d.depth == 1) {
            var loc = parent.name+"/"+d.name;
          } else {
            var loc = "<?php echo $path; ?>"+"/"+parent.name+"/"+d.name;
          }
          loc = encodeURIComponent(loc);
          var filter = "<?php echo $filter; ?>";
          if (d.depth <=2 && loc != loc0) {
            document.getElementById('sunburst').src = "sunburst_frame.php?path="+loc+"&filter="+filter;
            loc0 = loc;
          }
      })
      .on("mouseover", function (d) {
          d3.select(this).classed("selected", true);
      })
      .on("mouseout", function (d) {
          d3.selectAll(".selected").classed("selected", false);
      });
  //add arrows if it is a folder
  entered.append("span").attr("class", function (d) {
      var icon = d.children ? " glyphicon-chevron-down"
          : d._children ? "glyphicon-chevron-right" : "";
      return "caret glyphicon " + icon;
  });
  //add icons for folder for file
  entered.append("span").attr("class", function (d) {
      var icon = d.children || d._children ? "glyphicon-folder-close"
          : "glyphicon-folder-close"; // glyphicon-file
      return "glyphicon " + icon;
  });
  //add text
  entered.append("span").attr("class", "filename")
      .html(function (d) { return d.name; });
  //update caret direction
  nodeEls.select("span.caret").attr("class", function (d) {
      var icon = d.children ? " glyphicon-chevron-down"
          : d._children ? "glyphicon-chevron-right" : "";
      return "caret glyphicon " + icon;
  });
  //update position with transition
  nodeEls.transition().duration(duration)
      .style("top", function (d) { return (d.y - tree.nodeHeight()) + "px";})
      .style("left", function (d) { return d.x + "px"; })
      .style("opacity", 1);
  nodeEls.exit().remove();
}

function setupTree(data) {

  function collapse(d) {
    if (d.children) {
      d._children = d.children;
      d._children.forEach(collapse);
      d.children = null;
    }
  }

  function expandSingle(d) {
    if (d._children) {
      if (d.depth == 0) {
        d.children = d._children;
        d._children = null;
      }
    }
  }
  data.children.forEach(collapse);
  data.children.forEach(expandSingle);
}

var id = 0,
    loc0;

var tree = d3.layout.treelist()
    .childIndent(20)
    .nodeHeight(20);
var ul = d3.select("#tree-container").append("ul").classed("treelist", "true");

init();

</script>

</body>
</html>
