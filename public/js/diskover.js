$(document).ready(function () {

	$("#tagAllDelete").click(function () {
		$(".tagDeleteInput").prop('checked', true);
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-sm btn-warning active');
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-sm btn-success');
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-sm btn-info');
		$(".tagDeleteLabel").closest('tr').attr('class', 'warning');
	});

	$("#tagAllArchive").click(function () {
		$(".tagArchiveInput").prop('checked', true);
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-sm btn-success active');
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-sm btn-warning');
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-sm btn-info');
		$(".tagArchiveLabel").closest('tr').attr('class', 'success');
	});

	$("#tagAllKeep").click(function () {
		$(".tagKeepInput").prop('checked', true);
		$(".tagKeepLabel").attr('class', 'tagKeepLabel btn btn-sm btn-info active');
		$(".tagArchiveLabel").attr('class', 'tagArchiveLabel btn btn-sm btn-success');
		$(".tagDeleteLabel").attr('class', 'tagDeleteLabel btn btn-sm btn-warning');
		$(".tagKeepLabel").closest('tr').attr('class', 'info');
	});

	$("#refresh").click(function () {
		location.reload(true);
	});

	$("#reload").click(function () {
		console.log("removing json data in session storage because reload");
		sessionStorage.removeItem("diskover-filetree");
		location.reload(true);
	});

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

	$(".search").keyup(function () {
		var searchTerm = $(".search").val();
		var listItem = $('.results tbody').children('tr');
		var searchSplit = searchTerm.replace(/ /g, "'):containsi('")

		$.extend($.expr[':'], {
			'containsi': function (elem, i, match, array) {
				return (elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
			}
		});

		$(".results tbody tr").not(":containsi('" + searchSplit + "')").each(function (e) {
			$(this).attr('visible', 'false');
		});

		$(".results tbody tr:containsi('" + searchSplit + "')").each(function (e) {
			$(this).attr('visible', 'true');
		});

		var jobCount = $('.results tbody tr[visible="true"]').length;
		$('.counter').text(jobCount + ' item');

		if (jobCount == '0') {
			$('.no-result').show();
		} else {
			$('.no-result').hide();
		}
	});

});

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

function changeFileTreeLink() {
	var path = (getCookie('path')) ? getCookie('path') : '/';
	var filter = (getCookie('filter')) ? getCookie('filter') : 1048576;
	var mtime = (getCookie('mtime')) ? getCookie('mtime') : 0;
	var maxdepth = (getCookie('maxdepth')) ? getCookie('maxdepth') : 1;
	var url = "/filetree.php?path=" + path + "&filter=" + filter + "&mtime=" + mtime + "&maxdepth=" + maxdepth;
	document.getElementById("filetreelink").setAttribute("href", url);
	return false;
}

// change file tree link
changeFileTreeLink();
