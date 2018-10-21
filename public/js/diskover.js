/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

// default constants
var FILTER = 1;
var MAXDEPTH = 2;
var MTIME = 0;
var USE_COUNT = 0;
var SHOW_FILES = 1;
var HIDE_THRESH = 0.9;

var index = ($_GET('index')) ? $_GET('index') : getCookie('index');
var index2 = ($_GET('index2')) ? $_GET('index2') : getCookie('index2');
var path = ($_GET('path')) ? decodeURIComponent($_GET('path')) : decodeURIComponent(getCookie('path'));
// remove any trailing slash
if (path !== '/') {
	path = path.replace(/\/$/, "");
}
var filter = ($_GET('filter')) ? parseInt($_GET('filter')) : parseInt(getCookie('filter'));
if (filter === '') var filter = FILTER;
var mtime = ($_GET('mtime')) ? $_GET('mtime') : getCookie('mtime');
if (mtime === '') var mtime = MTIME;
var maxdepth = ($_GET('maxdepth')) ? parseInt($_GET('maxdepth')) : parseInt(getCookie('maxdepth'));
if (maxdepth === '') var maxdepth = MAXDEPTH;
var use_count = ($_GET('use_count')) ? parseInt($_GET('use_count')) : parseInt(getCookie('use_count'));
if (use_count === '') var use_count = USE_COUNT;
var show_files = ($_GET('show_files')) ? parseInt($_GET('show_files')) : parseInt(getCookie('show_files'));
if (show_files === '') var show_files = SHOW_FILES;

// check if using aws s3 index
var s3_index = (index.includes('s3')) ? 1 : 0;

var xhr = new XMLHttpRequest();
var changeTagCount = 0;

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
        sessionStorage.removeItem("diskover-hotdirs-heatmap");
        sessionStorage.removeItem("diskover-dupes");
        sessionStorage.removeItem("diskover-hardlinks");
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
            timeit = 0;
            return false;
        }
        var results;
        // delay for 500 ms before searching ES for user input
        setTimeout(function() {
            $.ajax({
                type:'GET',
                url:'searchkeypress.php',
                data: $('#searchnav').serialize(),
                success: function(data) {
                        if (data != "") {
                            // set width and position of search results div to match search input
                            var w = $('#searchnavbox').width();
                            var p = $('#searchnavbox').position();
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
        }, 500);
        return false;
	});

});

// cookie functions
function setCookie(cname, cvalue, exdays) {
    if (exdays != '') {
        var d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        var expires = "expires="+ d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    } else {
        document.cookie = cname + "=" + cvalue + ";path=/";
    }
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
   var chr = {'/': '\\/', '(': '\\(', ')': '\\)', '[': '\\[', ']': '\\]',
       ' ': '\\ ', '&': '\\&', '<': '\\<', '>': '\\>', '+': '\\+', '-': '\\-',
       '|': '\\|', '!': '\\!', '{': '\\{', '}': '\\}', '^': '\\^', '~': '\\~',
       '?': '\\?', ':': '\\:', '=': '\\=', '\'': '\\\'', '"': '\\"', '@': '\\@',
       '.': '\\.', '#': '\\#', '*': '\\*'};
   function abc(a) {
      return chr[a];
   }
   escaped_text = text.replace('\\', '\\\\');
   escaped_text = escaped_text.replace(/[\/\(\)\[\] &\<\>\+\-\|\!\{\}\^~\?\:\='"@\.#\*]/g, abc);
   return escaped_text;
}

// calculate change percentage between two numbers
function changePercent(a,b) {
    return ((a - b) / b) * 100;
}

// listen for msgs from diskover socket server and display progress info on screen
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
        } else if (this.readyState==3 && this.status==200) {
            var lines = this.responseText.trim().split('\n');
            var lastline = lines[lines.length-1]
            //console.log(lastline)
            var data = JSON.parse(lastline);
            if (data.msg == 'error') {  // check if we received err msg
                console.log("got error msg, closing event source and displaying error")
        		// hide progress
        		//document.getElementById('progressbar-container').style.display = 'none';
        		// display error
                $("#errormsg-container").fadeIn();
        		document.getElementById('errormsg').innerHTML = '<i class="glyphicon glyphicon-exclamation-sign"></i> Error, check command';
                setTimeout(function(){
                    $("#errormsg-container").fadeOut();
                    // enable run buttons
                    $('.run-btn').removeClass("disabled");
                }, 3000);
        	} else if (data.msg == 'taskstart') {
                console.log("got taskstart msg, storing taskid in cookie " + data.taskid)
                setCookie('running_task_id', data.taskid);
                // show progress bar
                //$("#progressbar-container").fadeIn();
                // update progressbar
                document.getElementById('loading').style.display='block';
                document.getElementById('loading-text').innerHTML = 'Running taskid ' + data.taskid + '...';
                /*setTimeout(function(){
                    $("#progressbar-container").fadeOut();
                    // enable run buttons
                    $('.run-btn').removeClass("disabled");
                }, 3000);*/
        	}
        } else if (this.readyState==4 && this.status==200) {
            var lines = this.responseText.trim().split('\n');
            var lastline = lines[lines.length-1]
            //console.log(lastline)
            var data = JSON.parse(lastline);
            if (data.msg == 'taskfinish') {  // check if we received taskfinish msg
                console.log("got taskfinish msg, showing progress bar and reloading page")
                // show progress bar
                //$("#progressbar-container").fadeIn();
        		document.getElementById('loading-text').innerHTML = 'Finished (exitcode:  '+data.exitcode+', elapsedtime: '+data.elapsedtime+')';
                setTimeout(function(){
                    //$("#progressbar-container").fadeOut();
                    // enable run buttons
                    //$('.run-btn').removeClass("disabled");
                    location.reload(true);
                }, 3000);
            }
        }
    }
}

// send command to diskover socket server
function runCommand() {
    var command = document.getElementById('command').value;
    if (!command) {
        alert("no command entered")
        return false;
    }
    try {
        command = JSON.parse(command);
    }
    catch(err) {
        console.log(err.message);
        alert("json error, check command string");
        return false;
    }
    var socketlistening = document.getElementById('socketlistening').value;
    if (socketlistening == 0) {
        alert("diskover socket server not listening")
        return false;
    }
    command = JSON.stringify(command);
    console.log("sending command to socket server")
    console.log(command)
    xhr.open("GET", "sockethandler.php?command="+command, true);
    xhr.timeout = 0;
    xhr.send();
}

// copy text to clipboard on button click
function copyToClipboard(element) {
  var $temp = $("<input>");
  $("body").append($temp);
  $temp.val($(element).text()).select();
  document.execCommand("copy");
  $temp.remove();
}
