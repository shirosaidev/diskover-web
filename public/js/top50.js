/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * d3 top50 analytics for diskover-web
 */

$(document).ready(function () {
    top50Switch($_GET('doctype'));
});

function top50Switch(a) {
    if (a == 'directory') {
        document.getElementById('top50files').style.display = 'none';
        document.getElementById('top50dirs').style.display = 'block';
    } else {
        document.getElementById('top50dirs').style.display = 'none';
        document.getElementById('top50files').style.display = 'block';
    }
}

// buttons

var top50type = $_GET('top50type');
var path = $_GET('path');
var filter = $_GET('filter');
var mtime = $_GET('mtime');
var index = $_GET('index') || getCookie('index');
var index2 = $_GET('index2') || getCookie('index2');
var doctype = $_GET('doctype');
$(".button-largest").click(function () {
    window.location.href = 'top50.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&doctype=' + doctype + '&top50type=Largest';
});
$(".button-oldest").click(function () {
    window.location.href = 'top50.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&doctype=' + doctype + '&top50type=Oldest';
});
$(".button-newest").click(function () {
    window.location.href = 'top50.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&doctype=' + doctype + '&top50type=Newest';
});
$(".button-users").click(function () {
    window.location.href = 'top50.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&doctype=' + doctype + '&top50type=Users';
});
$(".button-groups").click(function () {
    window.location.href = 'top50.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&doctype=' + doctype + '&top50type=Groups';
});
