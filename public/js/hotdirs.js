/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/*
 * d3 hotdirs analytics for diskover-web
 */


$(document).ready(function() {

     $('#changepath').click(function () {
         console.log('changing paths');
         var newpath = encodeURIComponent($('#pathinput').val());
         setCookie('path', newpath);
         location.href = "hotdirs.php?index=" + index +"&index2=" + index2 + "&path=" + newpath + "&filter=" + filter + "&mtime=" + mtime + '&maxdepth=' + maxdepth + "&use_count=" + use_count + "&show_new_dirs=" + show_new_dirs + "&sort=" + sort + "&sortorder=" + sortorder;
         return false;
     });

     /* ------- SHOW NEW DIRS CHECKBOX -------*/

     d3.select("#shownewdirs").on("change", function() {
         var snd = document.getElementById('shownewdirs').checked;
         (snd) ? show_new_dirs = 1 : show_new_dirs = 0;
         setCookie('show_new_dirs', show_new_dirs);
         console.log("removing json data on local storage because show new dirs changed");
         sessionStorage.removeItem("diskover-heatmap");
         location.href="hotdirs.php?index=" + index +"&index2=" + index2 + "&path=" + encodeURIComponent(path) + "&filter=" + filter + "&mtime=" + mtime + '&maxdepth=' + maxdepth + "&use_count=" + use_count + "&show_new_dirs=" + show_new_dirs + "&sort=" + sort + "&sortorder=" + sortorder;
     });

     /* ------- MAXDEPTH BUTTONS -------*/

    d3.select("#depth1").on("click", function() {
        maxdepth = 1;
        setCookie('maxdepth', 1)
        console.log("removing json data on local storage because maxdepth changed");
        sessionStorage.removeItem("diskover-heatmap");
        location.href='hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + "&use_count=" + use_count + "&show_new_dirs=" + show_new_dirs + "&sort=" + sort + "&sortorder=" + sortorder;
    });
    d3.select("#depth2").on("click", function() {
        maxdepth = 2;
        setCookie('maxdepth', 2)
        console.log("removing json data on local storage because maxdepth changed");
        sessionStorage.removeItem("diskover-heatmap");
        location.href='hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + "&use_count=" + use_count + "&show_new_dirs=" + show_new_dirs + "&sort=" + sort + "&sortorder=" + sortorder;
    });
    d3.select("#depth3").on("click", function() {
        maxdepth = 3;
        setCookie('maxdepth', 3)
        console.log("removing json data on local storage because maxdepth changed");
        sessionStorage.removeItem("diskover-heatmap");
        location.href='hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + "&use_count=" + use_count + "&show_new_dirs=" + show_new_dirs + "&sort=" + sort + "&sortorder=" + sortorder;
    });
    d3.select("#depth4").on("click", function() {
        maxdepth = 4;
        setCookie('maxdepth', 4)
        console.log("removing json data on local storage because maxdepth changed");
        sessionStorage.removeItem("diskover-heatmap");
        location.href='hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + "&use_count=" + use_count + "&show_new_dirs=" + show_new_dirs + "&sort=" + sort + "&sortorder=" + sortorder;
    });
    d3.select("#depth5").on("click", function() {
        maxdepth = 5;
        setCookie('maxdepth', 5)
        console.log("removing json data on local storage because maxdepth changed");
        sessionStorage.removeItem("diskover-heatmap");
        location.href='hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter=' + filter + '&mtime=' + mtime + '&maxdepth=' + maxdepth + "&use_count=" + use_count + "&show_new_dirs=" + show_new_dirs + "&sort=" + sort + "&sortorder=" + sortorder;
    });

    d3.select("#depth"+maxdepth).classed("active", true);
    for (i = 1; i <= 5; i++) {
        if (i != maxdepth) {
            d3.select("#depth"+i).classed("active", false);
        }
    }

    // update path field
    document.getElementById('pathinput').value = path;

});


// buttons

var filter = $_GET('filter');
var mtime = $_GET('mtime');
var min_change_percent = $_GET('min_change_percent');
var index = $_GET('index') || getCookie('index');
var index2 = $_GET('index2') || getCookie('index2');
var sort = $_GET('sort') || 'change_percent_filesize';
var sortorder = $_GET('sortorder') || 'desc';
var show_new_dirs = ($_GET('show_new_dirs')) ? parseInt($_GET('show_new_dirs')) : parseInt(getCookie('show_new_dirs'));
(show_new_dirs === '' || show_new_dirs === 1) ? show_new_dirs = 1 : show_new_dirs = 0;
(show_new_dirs === 1) ? $('#shownewdirs').prop('checked', true) : $('#shownewdirs').prop('checked', false);

if (sort == 'change_percent_filesize' && sortorder == 'desc') {
    $(".button-sizechange-desc").addClass('active');
} else if (sort == 'change_percent_filesize' && sortorder == 'asc') {
    $(".button-sizechange-asc").addClass('active');
} else if (sort == 'change_percent_items' && sortorder == 'desc') {
    $(".button-itemschange-desc").addClass('active');
} else if (sort == 'change_percent_items' && sortorder == 'asc') {
    $(".button-itemschange-asc").addClass('active');
} else {
    $(".button-sizechange-desc").addClass('active');
}

$(".button-sizechange-desc").click(function () {
    window.location.href = 'hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&show_new_dirs=' + show_new_dirs + '&show_files=0&use_count=0&sort=change_percent_filesize&sortorder=desc';
});
$(".button-sizechange-asc").click(function () {
    window.location.href = 'hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&show_new_dirs=' + show_new_dirs + '&show_files=0&use_count=0&sort=change_percent_filesize&sortorder=asc';
});
$(".button-itemschange-desc").click(function () {
    window.location.href = 'hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&show_new_dirs=' + show_new_dirs + '&show_files=0&use_count=1&sort=change_percent_items&sortorder=desc';
});
$(".button-itemschange-asc").click(function () {
    window.location.href = 'hotdirs.php?index=' + index + '&index2=' + index2 + '&path=' + path + '&filter='  + filter + '&mtime=' + mtime + '&show_new_dirs=' + show_new_dirs + '&show_files=0&use_count=1&sort=change_percent_items&sortorder=asc';
});
