<?php
	MetaTagManager::addLink('stylesheet', __CA_URL_ROOT__."/app/plugins/tourML/css/iphone-iframe.css",'text/css');	
    $tour_id = $this->getVar('id');
    $target = "http://".__CA_SITE_HOSTNAME__ . "/".__CA_URL_ROOT__."app/plugins/providenceTourML/lib/tap-webapp/index.php?id=".$tour_id;
?>
<script>
    localStorage.clear();
</script>
<H1>Preview</H1>
<div id="tap-webapp">
    <iframe src="<?php print $target; ?>" height="568" width="320" frameBorder="0"></iframe>
</div> 
<div class="editorBottomPadding"><!-- empty --></div>
<div style="clear:both;"><!-- EMPTY --></div>