/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

$(document).ready(function () {

    $('.tagfiles').submit(function() {
        if (changeTagCount == 0) {
            alert("no files tagged")
            return false;
        }
        return true;
    });

	// select all buttons on search results pages
	$(".tagAllDelete").click(function () {
		$(".tagDeleteInput").prop('checked', true);
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-xs btn-warning active');
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-xs btn-success');
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-xs btn-info');
		$(".tagDeleteLabel").closest('tr').attr('class', 'warning');
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	$(".tagAllArchive").click(function () {
		$(".tagArchiveInput").prop('checked', true);
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-xs btn-success active');
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-xs btn-warning');
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-xs btn-info');
		$(".tagArchiveLabel").closest('tr').attr('class', 'success');
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	$(".tagAllKeep").click(function () {
		$(".tagKeepInput").prop('checked', true);
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-xs btn-info active');
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-xs btn-success');
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-xs btn-warning');
		$(".tagKeepLabel").closest('tr').attr('class', 'info');
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	$(".tagAllUntagged").click(function () {
		$(".tagUntaggedInput").prop('checked', true);
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-xs btn-info');
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-xs btn-success');
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-xs btn-warning');
		$(".tagUntaggedLabel").closest('tr').attr('class', 'untagged');
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	// reload page button on search results page
	$(".reload-results").click(function () {
		location.reload(true);
	});

    // select results per page
    $("#resultsize").change(function () {
		$(this).closest('form').trigger('submit');
	});

	// copy custom tag button on search results page
	$(".copyCustomTag").click(function () {
		var customtag = $(this).closest('tr').find('.custom-tag-input').val();
		$(".custom-tag-input").val(customtag);
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	// reload page button on analytics pages
	$("#reload").click(function () {
		console.log("removing path cookie because reload");
		deleteCookie("path");
		console.log("removing json data in session storage because reload");
		sessionStorage.removeItem("diskover-filetree");
		sessionStorage.removeItem("diskover-treemap");
        sessionStorage.removeItem("diskover-heatmap");
		location.reload(true);
	});

	// highlight results table rows when radio buttons pressed
	$("#highlightRowDelete input").change(function () {
		$(this).closest('tr').attr('class', 'warning');
	});

	$("#highlightRowArchive input").change(function () {
		$(this).closest('tr').attr('class', 'success');
	});

	$("#highlightRowKeep input").change(function () {
		$(this).closest('tr').attr('class', 'info');
	});

	$("#highlightRowUntagged input").change(function () {
		$(this).closest('tr').attr('class', 'untagged');
	});

	// search within text input
	$(".search").keyup(function () {
		var searchTerm = $(".search").val();
		var searchSplit = searchTerm.replace(/ /g, "'):containsi('")

		$.extend($.expr[':'], {
			'containsi': function (elem, i, match, array) {
				return (elem.getElementsByTagName("input")[4].value || elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
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

	// number of changes on results page that need to be tagged (saved in Elasticsearch)
	$(".custom-tag-input").keyup(function (e) {
		this.changed = typeof(this.changed) === 'undefined' ? false : this.changed;
		if (!this.changed) {
			changeTagCount += 1;
			this.changed = true;
		}

		$('.changetagcounter').text(changeTagCount + ' changes unsaved');

		if (changeTagCount > 0) {
			$('.unsavedChangesAlert').show();
		} else {
			$('.unsavedChangesAlert').hide();
		}
	});

	$(".tagButtons").change(function (e) {
		this.changed = typeof(this.changed) === 'undefined' ? false : this.changed;
		if (!this.changed) {
			changeTagCount += 1;
			this.changed = true;
		}

		$('.changetagcounter').text(changeTagCount + ' changes unsaved');

		if (changeTagCount > 0) {
			$('.unsavedChangesAlert').show();
		} else {
			$('.unsavedChangesAlert').hide();
		}
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
	window.parent.document.getElementById("filetreelink").setAttribute("href", url);
	var url = "treemap.php?index=" + index + "&index2=" + index2 + "&path=" + path + "&filter=" + filter + "&mtime=" + mtime + "&maxdepth=" + maxdepth + "&use_count=" + use_count;
	window.parent.document.getElementById("treemaplink").setAttribute("href", url);
    var url = "heatmap.php?index=" + index + "&index2=" + index2 + "&path=" + path + "&filter=" + filter + "&mtime=" + mtime + "&maxdepth=" + maxdepth + "&use_count=" + use_count;
	window.parent.document.getElementById("heatmaplink").setAttribute("href", url);
    var url = "top50.php?index=" + index + "&index2=" + index2 + "&path=" + path + "&filter=" + filter + "&mtime=" + mtime;
	window.parent.document.getElementById("top50link").setAttribute("href", url);
	return false;
}

// listen for msgs from diskover socket server and display progress bar on screen
// using XMLHttpRequest
function listenSocketServer() {
    var socketlistening = document.getElementById('socketlistening').value;
    if (socketlistening == 0) {
        return false;
    }
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
