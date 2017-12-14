<input type="hidden" name="ct_checkjs" id="ct_checkjs" value="0" />
<script type="text/javascript">
var date = new Date();
document.getElementById("ct_checkjs").value = date.getFullYear(); 
document.cookie = "ct_timestamp" + "=" + encodeURIComponent(Math.floor(new Date().getTime()/1000)) + "; path=/";
</script>