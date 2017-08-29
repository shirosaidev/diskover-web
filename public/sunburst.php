<?php
if (!empty($_GET['path'])) {
  $path = $_GET['path'];
	// remove any trailing slash unless root
	if ($path != "/") {
  	$path = rtrim($path, '/');
	}
}
if (!empty($_GET['filter'])) {
  $filter = $_GET['filter'];
} else {
  $filter = 1048576; // default filter 1 MB
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
.selected {
  color: orange;
}

.node {
  position: absolute;
  list-style: none;
  cursor: default;
  margin-left: 20px;
	margin-top: 70px;
	white-space: nowrap;
  word-wrap: break-word;
}

.node span {
  margin-right: 3px;
}

.node .caret {
  font-size: 10px;
	color: darkgray;
}

.node .glyphicon-folder-close {
	color: skyblue;
}

.node .glyphicon-folder-open {
	color: skyblue;
}

.node .glyphicon-file {
	color: dimgray;
}

.node .filesize-red {
  color: red;
  font-size: 12px;
  padding-left: 10px;
}

.node .filesize-orange {
  color: orangered;
  font-size: 12px;
  padding-left: 10px;
}

.node .filesize-yellow {
  color: orange;
  font-size: 12px;
  padding-left: 10px;
}

.node .filesize-gray {
  color: gray;
  font-size: 12px;
  padding-left: 10px;
}

.node .filecount {
  color: gray;
  font-size: 10px;
  padding-left: 5px;
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
  overflow: hidden;
}

.path {
	width: 300px;
}

.path:focus {
	width: 300px;
}

.filter-dropdown {
  margin-left: 15px;
  margin-top: 2px;
  display: none;
}
</style>
<body>
<?php include __DIR__ . "/nav.php"; ?>

<div class="container" id="warning" style="display:none;">
  <div class="row">
    <div class="alert alert-dismissible alert-danger col-xs-8">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, too many sub directories :(</strong> Choose a different path and try again.
    </div>
  </div>
</div>
<div class="container" id="info" style="display:none;">
  <div class="row">
    <div class="alert alert-dismissible alert-warning col-xs-8">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, no files found or all files too small (filtered) :(</strong> Choose a different path and try again.
    </div>
  </div>
</div>
<div class="container-fluid">
  <div class="row">
    <div class="col-xs-4" id="tree-container">
			<form class="form-inline" id="path-container" style="display:none;">
			<div class="form-group">
				<div class="col-xs-12">
					<input type="text" name="path" id="path" class="path" value="<?php echo $path; ?>">
				</div>
			</div>
			<button type="submit" id="submit" class="btn btn-primary btn-sm">Go</button>
      </form>
      <div class="btn-group filter-dropdown" id="filter-container">
      <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Filter
        <span class="caret"></span></button>
        <ul class="dropdown-menu">
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=524288">>512 KB</a></li>
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=1048576">>1 MB (default)</a></li>
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=2097152">>2 MB</a></li>
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=5242880">>5 MB</a></li>
          <li><a href="/sunburst.php?path=<?php echo $_GET['path']; ?>&filter=10485760">>10 MB</a></li>
        </ul>
      </div>
    </div>
    <div class="col-xs-8">
      <div class="sunburst-container" id="sunburst-container" style="display:none;">
        <iframe name="sunburst" id="sunburst" src="/sunburst_frame.php" scrolling="no"></iframe>
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

<!-- path change -->
<script>
$('#submit').click( function() {
	var path = $('#path').val();
	var filter = "<?php echo $filter; ?>";
	location.href = "/sunburst.php?path="+path+"&filter="+filter;
	return false;
});
</script>

<!-- file toggle -->
<script>
$('#hidefiles').click( function() {

	return false;
});
</script>

<!-- sunburst scroll -->
<script>
$(window).scroll(function(){
  $("#sunburst-container").stop().animate({"marginTop": ($(window).scrollTop()) + "px", "marginLeft":($(window).scrollLeft()) + "px"}, "slow" );
});
</script>

<!-- spin loader -->
<script>

var path = encodeURIComponent("<?php echo $path; ?>");
var filter = "<?php echo $filter; ?>";

// config references
var chartConfig = {
    target : 'tree-container',
    data_url : '/d3_data.php?path='+path+'&filter='+filter
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

var jsondata;

// callback function wrapped for loader in 'init' function
function init() {

    // trigger loader
    var spinner = new Spinner(opts).spin(target);

    // load json data and trigger callback
    d3.json(chartConfig.data_url, function(data) {

				// jsondata for sunburst
				jsondata = JSON.parse(JSON.stringify(data));

        // stop spin.js loader
        spinner.stop();

        // display warning if too many files
        if (data.warning) {
          document.getElementById('warning').style.display = 'block';
				// display warning no files
        } else if (data.info) {
            document.getElementById('info').style.display = 'block';
        } else {
					// show path input
					document.getElementById('path-container').style.display = 'inline-block';
          // show filter dropdown
					document.getElementById('filter-container').style.display = 'inline-block';
          // show iframe
          document.getElementById('sunburst-container').style.display = 'block';
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

// format bytes to mb, gb
function formatBytes(a,b){if(0==a)return"0 Bytes";var c=1024,d=b||2,e=["Bytes","KB","MB","GB","TB","PB","EB","ZB","YB"],f=Math.floor(Math.log(a)/Math.log(c));return parseFloat((a/Math.pow(c,f)).toFixed(d))+" "+e[f]}

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
            loc = parent.name;
          } else if (d.depth == 1) {
						if (parent.name=="/") {
            	loc = "/"+d.name;
						} else {
							loc = parent.name+"/"+d.name;
						}
          } else {
						if (path!='/'){loc=path+'/'+parent.name+'/'+d.name;};
          }
          loc = encodeURIComponent(loc);
          if (d.depth <=1 && loc != loc0 && d.children) {
						// direction changed, reload sunburst
            document.getElementById('sunburst').contentWindow.location.reload();
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
          : "glyphicon-file";
      return "glyphicon " + icon;
  });
  //add text
  entered.append("span").attr("class", "filename")
      .html(function (d) { return d.name; });
	//add filesize
  entered.append("span").attr("class", function (d) {
				if (d.size > 10737418240) {
						var fileclass = "filesize-red";
				} else if (d.size > 5368709120 && d.size < 10737418240) {
						var fileclass = "filesize-orange";
				} else if (d.size > 1073741824 && d.size < 5368709120) {
						var fileclass = "filesize-yellow";
				} else {
						var fileclass = "filesize-gray";
				}
				return fileclass;
	})
			.html(function (d) { return formatBytes(d.size); });
	// add file count
	entered.append("span").attr("class", "filecount")
			.html(function (d) { return d.count; });
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
    loc,
		loc0;

var filter = "<?php echo $filter; ?>";
var path = "<?php echo $path; ?>";

loc = encodeURIComponent(path);
loc0 = encodeURIComponent(path);

// for sunburst frame to get json data
function getJSON() {
  // at root
  if (loc == encodeURIComponent(path)) {
    return jsondata;
  }
  // directory changed so we need to get child data
  // gets the child array and sets json data for sunburst
  var jsondata_child;
  // split path by / into arr
  var arr = loc.split('%2F');
  // get last dir in arr
  var dir = decodeURIComponent(arr[arr.length-1]);

  jsondata.children.forEach(getChild);

  function getChild(child) {
    if (child.name == dir) {
      loc0 = loc;
      jsondata_child = child;
    }
  }
  return jsondata_child;
}

var tree = d3.layout.treelist()
    .childIndent(10)
    .nodeHeight(20);
var ul = d3.select("#tree-container").append("ul").classed("treelist", "true");

init();

</script>

</body>
</html>
