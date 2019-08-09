<form method="post" id="logform">
    <input type="hidden" name="version" value="<?php echo $VERSION; ?>">
    <input type="hidden" name="request" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <input type="hidden" name="estime" value="<?php echo $estime; ?>">
    <input type="hidden" name="pageloadtime" value="<?php echo $time; ?>">
</form>
<script>
$(document).ready(function(){
    if (getCookie('sendstats') == 1 && getCookie('crawlfinished') == 1) {
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