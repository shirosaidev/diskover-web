<form method="post" id="logform">
    <input type="hidden" name="date" value="<?php $date = new DateTime(); echo $date->format("Y-m-d h:i:s"); ?>">
    <input type="hidden" name="version" value="<?php echo $VERSION; ?>">
    <input type="hidden" name="searchquery" value="<?php echo $_REQUEST['q']; ?>">
    <input type="hidden" name="searchresults" value="<?php echo $total; ?>">
    <input type="hidden" name="searchresultssize" value="<?php echo $total_size/1024/1024/1024; ?>">
    <input type="hidden" name="request" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <input type="hidden" name="diskspacetotal" value="<?php echo $diskspace_total/1024/1024/1024; ?>">
    <input type="hidden" name="diskspaceused" value="<?php echo $diskspace_used/1024/1024/1024; ?>">
    <input type="hidden" name="totalfilesize" value="<?php echo $totalFilesizeAll/1024/1024/1024; ?>">
    <input type="hidden" name="totaldirs" value="<?php echo $totaldirs; ?>">
    <input type="hidden" name="totalfiles" value="<?php echo $totalfiles; ?>">
    <input type="hidden" name="path" value="<?php echo $diskspace_path; ?>">
    <input type="hidden" name="crawlstarttime" value="<?php echo $firstcrawltime; ?>">
    <input type="hidden" name="crawlfinishtime" value="<?php echo $lastcrawltime; ?>">
    <input type="hidden" name="crawlelapsedtime" value="<?php echo $crawlelapsedtime; ?>">
    <input type="hidden" name="crawlcumulativetime" value="<?php echo $crawlcumulativetime; ?>">
    <input type="hidden" name="bulkupdatetime" value="<?php echo $bulkcumulativetime; ?>">
    <input type="hidden" name="workerbots" value="<?php echo $numworkers; ?>">
</form>
<script>
$(document).ready(function(){
    if (getCookie('sendstats') == 1) {
        var formdata = $('#logform').serialize();
        $.ajax({
          type: "POST",
          data: formdata,
          url: "https://shirosaidev.000webhostapp.com/diskoverweb_data_collector.php",
          success: function(data) {
            console.log("SUCCESS: " + data);
          },
          error: function(data) {
            console.log("ERROR");
            console.log(data);
          }
      });
    }
});
</script>