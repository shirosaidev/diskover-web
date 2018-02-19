/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

$(document).ready(function () {

	// reload page button on search results page
	$(".reload-results").click(function () {
		location.reload(true);
	});

    // select results per page
    $("#resultsize").change(function () {
		$(this).closest('form').trigger('submit');
	});

	// reload page button on analytics pages
	$("#reload").click(function () {
		console.log("removing path cookie because reload");
		deleteCookie("path");
		console.log("removing json data in session storage because reload");
		sessionStorage.removeItem("diskover-filetree");
		sessionStorage.removeItem("diskover-treemap");
        sessionStorage.removeItem("diskover-heatmap");
        sessionStorage.removeItem("diskover-dupes");
		location.reload(true);
	});

	// search within text input
	$("#searchwithin").keyup(function () {
		var searchTerm = $("#searchwithin").val();
		var searchSplit = searchTerm.replace(/ /g, "'):containsi('")

		$.extend($.expr[':'], {
			'containsi': function (elem, i, match, array) {
				return (elem.innerText || elem.textContent || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
			}
		});

		$("#results-tbody tr").not(":containsi('" + searchSplit + "')").each(function (e) {
			$(this).attr('visible', 'false');
		});

		$("#results-tbody tr:containsi('" + searchSplit + "')").each(function (e) {
			$(this).attr('visible', 'true');
		});

		var jobCount = $('#results-tbody tr[visible="true"]').length;
		$('.counter').text(jobCount + ' item');

		if (jobCount === 0) {
			$('.no-result').show();
		} else {
			$('.no-result').hide();
		}
	});

    // search items in ES on keypress on nav search
	$("#searchnavinput").keyup(function () {
        if ($('#searchnavinput').val() === "") {
            $('#essearchreply-text-nav').html("");
            $('#essearchreply-nav').hide();
            return false;
        }
        var results;
        $.ajax({
            type:'GET',
            url:'searchkeypress.php',
            data: $('#searchnav').serialize(),
            success: function(data) {
            		if (data != "") {
                        // set width and position of search results div to match search input
                        var w = $('#searchnavbox').width();
                        var p = $('#searchnavbox').position();
                        console.log(p)
                        $("#essearchreply-nav").css({left: p.left, position:'absolute'});
                        $('#essearchreply-nav').width(w);
            			$('#essearchreply-nav').show();
                        $('#essearchreply-text-nav').html(data);
            		} else {
                        $('#essearchreply-text-nav').html("");
                        $('#essearchreply-nav').hide();
            		}
                }
        });
        return false;
	});

    // update visualization links
    updateVisLinks();
});

// cookie functions
function setCookie(cname, cvalue) {
	document.cookie = cname + "=" + cvalue + ";path=/";
}

function getCookie(cname) {
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

function deleteCookie(cname) {
	document.cookie = cname + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

// GET url values
function $_GET(param) {
	var vars = {};
	window.location.href.replace(location.hash, '').replace(
		/[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
		function (m, key, value) { // callback
			// remove any trailing #
			if (!value) return '';
			value = value.replace(/#$/, "");
			vars[key] = value !== undefined ? value : '';
		}
	);

	if (param) {
		return vars[param] ? vars[param] : '';
	}
	return vars;
}

// format bytes to mb, gb
function format(a, b) {
	if (0 === a) return "0 Bytes";
	var c = 1024,
		d = b || 2,
		e = ["Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"],
		f = Math.floor(Math.log(a) / Math.log(c));
	return parseFloat((a / Math.pow(c, f)).toFixed(d)) + " " + e[f]
}

// escape special characters
function escapeHTML(text) {
   var chr = { '/': '\\/', '(': '\\(', ')': '\\)', '[': '\\[', ']': '\\]',
       ' ': '\\ ', '&': '\\&', '<': '\\<', '>': '\\>', '+': '\\+', '-': '\\-',
       '|': '\\|', '!': '\\!', '{': '\\{', '}': '\\}', '^': '\\^', '~': '\\~',
       '?': '\\?', ':': '\\:' };
   function abc(a) {
      return chr[a];
   }
   return text.replace(/[/()\[\]\ &<>+\-\|!{}^~?:]/g, abc);
}

// calculate change percentage between two numbers
function changePercent(a,b) {
    return ((a - b) / b) * 100;
}

// update url links in nav bar for visualizations
function updateVisLinks() {
	var path = ($_GET('path')) ? $_GET('path') : getCookie('path');
	var filter = (getCookie('filter')) ? getCookie('filter') : FILTER;
	var mtime = (getCookie('mtime')) ? getCookie('mtime') : MTIME;
	var maxdepth = (getCookie('maxdepth')) ? getCookie('maxdepth') : MAXDEPTH;
    var use_count = (getCookie('use_count')) ? getCookie('use_count') : USE_COUNT;
    var index = ($_GET('index')) ? $_GET('index') : getCookie('index');
    var index2 = ($_GET('index2')) ? $_GET('index2') : getCookie('index2');
	var url = "filetree.php?index=" + index + "&index2=" + index2 + "&path=" + path + "&filter=" + filter + "&mtime=" + mtime + "&use_count=" + use_count;
	document.getElementById("filetreelink").setAttribute("href", url);
	var url = "treemap.php?index=" + index + "&index2=" + index2 + "&path=" + path + "&filter=" + filter + "&mtime=" + mtime + "&maxdepth=" + maxdepth + "&use_count=" + use_count;
	document.getElementById("treemaplink").setAttribute("href", url);
    var url = "heatmap.php?index=" + index + "&index2=" + index2 + "&path=" + path + "&filter=" + filter + "&mtime=" + mtime + "&maxdepth=" + maxdepth + "&use_count=" + use_count;
	document.getElementById("heatmaplink").setAttribute("href", url);
    var url = "top50.php?index=" + index + "&index2=" + index2 + "&path=" + path + "&filter=" + filter + "&mtime=" + mtime;
	document.getElementById("top50link").setAttribute("href", url);
    var url = "tags.php?index=" + index + "&index2=" + index2;
	document.getElementById("tagslink").setAttribute("href", url);
    var url = "dupes.php?index=" + index + "&index2=" + index2;
	document.getElementById("dupeslink").setAttribute("href", url);
    var url = "smartsearches.php?index=" + index + "&index2=" + index2;
	document.getElementById("smartsearcheslink").setAttribute("href", url);
	return false;
}

// listen for msgs from diskover socket server and display progress bar on screen
// using XMLHttpRequest
function listenSocketServer() {
    var socketlistening = document.getElementById('socketlistening');
    if (socketlistening === null) return false;
    socketlistening = document.getElementById('socketlistening').value;
    if (socketlistening == 0) return false;
    console.log('listening for xhr msgs from diskover socket server')
    /*xhr.onprogress = function() {
        console.log(this.responseText)
        var data = JSON.parse(this.responseText);
        console.log(data);
        // update progressbar
        document.getElementById('progressbar').innerHTML = data.percent + '%';
        document.getElementById('progressbar').innerHTML += ' ['+data.eta+', '+data.it_per_sec+' '+data.it_name+'/s]';
        document.getElementById('progressbar').setAttribute('aria-valuenow', data.percent);
        document.getElementById('progressbar').style.width = data.percent+'%';
    }*/
    xhr.onreadystatechange = function() {
        //console.log(this.readyState)
        //console.log(this.status)
        //console.log(this.responseText)
        if (this.readyState==2 && this.status==200) {
            // disable run buttons
            $('.run-btn').addClass("disabled");
            // show progress bar
            $("#progress-container").fadeIn();
        } else if (this.readyState==3 && this.status==200) {
            var lines = this.responseText.trim().split('\n');
            var lastline = lines[lines.length-1]
            //console.log(lastline)
            var data = JSON.parse(lastline);
            if (data.msg == 'error') {  // check if we received err msg
                console.log("got error msg, closing event source and displaying error")
        		// hide progress
        		document.getElementById('progress-container').style.display = 'none';
        		// display error
                $("#errormsg-container").fadeIn();
                $("#errormsg").fadeIn();
        		document.getElementById('errormsg').innerHTML = '<i class="glyphicon glyphicon-exclamation-sign"></i> Error, check command';
                setTimeout(function(){
                    $("#errormsg").fadeOut();
                    $("#errormsg-container").fadeOut();
                    // enable run buttons
                    $('.run-btn').removeClass("disabled");
                }, 3000);
        	} else if (data.msg == 'taskid') {
                console.log("got taskid msg, storing in cookie " + data.id)
                setCookie('running_task_id', data.id);
        	} else {
        		// update progressbar
                document.getElementById('progressbar').innerHTML = data.percent + '%';
        		document.getElementById('progressbar').innerHTML += ' ['+data.eta+', '+data.it_per_sec+' '+data.it_name+'/s]';
        		document.getElementById('progressbar').setAttribute('aria-valuenow', data.percent);
        		document.getElementById('progressbar').style.width = data.percent+'%';
        	}
        } else if (this.readyState==4 && this.status==200) {
            var lines = this.responseText.trim().split('\n');
            var lastline = lines[lines.length-1]
            //console.log(lastline)
            var data = JSON.parse(lastline);
        	console.log(data);
            if (data.msg == 'exit') {  // check if we received exit msg
                console.log("got exit msg, hiding progress bar and reloading page")
                document.getElementById('progressbar').innerHTML = '100%';
                document.getElementById('progressbar').style.width = '100%';
        		document.getElementById('progressbar').innerHTML = 'Finished (exit code:  '+data.exitcode+', elapsed time: '+data.elapsedtime+')';
                setTimeout(function(){
                    $(".progress-container").fadeOut();
                    // enable run buttons
                    $('.run-btn').removeClass("disabled");
                    //document.getElementById('progressbar').innerHTML = '0%';
                    //document.getElementById('progressbar').style.width = '0%';
                    location.reload(true);
                }, 3000);
            } else {
        		// update progressbar
                document.getElementById('progressbar').innerHTML = data.percent + '%';
        		document.getElementById('progressbar').innerHTML += ' ['+data.eta+', '+data.it_per_sec+' '+data.it_name+'/s]';
        		document.getElementById('progressbar').setAttribute('aria-valuenow', data.percent);
        		document.getElementById('progressbar').style.width = data.percent+'%';
        	}
        }
    }
}

// send command to diskover socket server
function runCommand(command) {
    var socketlistening = document.getElementById('socketlistening').value;
    if (socketlistening == 0) {
        alert("diskover socket server not listening")
        return false;
    }
    if (!command) {
		alert("no command")
		return false;
	}
    var command = JSON.stringify(command);
    console.log("sending command to socket server")
    console.log(command)
    xhr.open("GET", "sockethandler.php?command="+command, true);
    xhr.send();
}

var xhr = new XMLHttpRequest();
var changeTagCount = 0;

// default constants
var FILTER = 1;
var MAXDEPTH = 2;
var MTIME = 0;
var USE_COUNT = 0;
