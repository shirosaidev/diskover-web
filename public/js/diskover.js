$(document).ready(function () {

	// select all buttons on search results pages
	$(".tagAllDelete").click(function () {
		$(".tagDeleteInput").prop('checked', true);
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-sm btn-warning active');
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-sm btn-success');
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-sm btn-info');
		$(".tagDeleteLabel").closest('tr').attr('class', 'warning');
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	$(".tagAllArchive").click(function () {
		$(".tagArchiveInput").prop('checked', true);
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-sm btn-success active');
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-sm btn-warning');
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-sm btn-info');
		$(".tagArchiveLabel").closest('tr').attr('class', 'success');
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	$(".tagAllKeep").click(function () {
		$(".tagKeepInput").prop('checked', true);
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-sm btn-info active');
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-sm btn-success');
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-sm btn-warning');
		$(".tagKeepLabel").closest('tr').attr('class', 'info');
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	$(".tagAllUntagged").click(function () {
		$(".tagUntaggedInput").prop('checked', true);
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-sm btn-info');
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-sm btn-success');
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-sm btn-warning');
		$(".tagUntaggedLabel").closest('tr').attr('class', 'untagged');
		changeTagCount =  $('#results-tbody tr').length;
		$('.changetagcounter').text(changeTagCount + ' changes unsaved');
		$('.unsavedChangesAlert').show();
	});

	// reload page button on search results page
	$(".reload-results").click(function () {
		location.reload(true);
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

		if (jobCount == '0') {
			$('.no-result').show();
		} else {
			$('.no-result').hide();
		}
	});

	// number of changes on results page that need to be tagged (saved in Elasticsearch)
	var changeTagCount = 0;
	$(".custom-tag-input").keyup(function (e) {
		this.changed = typeof(this.changed) == 'undefined' ? false : this.changed;
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
		this.changed = typeof(this.changed) == 'undefined' ? false : this.changed;
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
	if (0 == a) return "0 Bytes";
	var c = 1024,
		d = b || 2,
		e = ["Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"],
		f = Math.floor(Math.log(a) / Math.log(c));
	return parseFloat((a / Math.pow(c, f)).toFixed(d)) + " " + e[f]
}

// update url links in nav bar for visualizations
function updateVisLinks() {
	var path = (getCookie('path')) ? getCookie('path') : '';
	var filter = (getCookie('filter')) ? getCookie('filter') : 1048576;
	var mtime = (getCookie('mtime')) ? getCookie('mtime') : 0;
	var maxdepth = (getCookie('maxdepth')) ? getCookie('maxdepth') : 2;
	var url = "/filetree.php?path=" + path + "&filter=" + filter + "&mtime=" + mtime;
	document.getElementById("filetreelink").setAttribute("href", url);
	var url = "/treemap.php?path=" + path + "&filter=" + filter + "&mtime=" + mtime + "&maxdepth=" + maxdepth;
	document.getElementById("treemaplink").setAttribute("href", url);
	return false;
}

// update visualization links
updateVisLinks();
