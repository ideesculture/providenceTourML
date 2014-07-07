<?php
	MetaTagManager::addLink('stylesheet', __CA_URL_ROOT__."/app/plugins/tourML/css/iphone-iframe.css",'text/css');	
        $tour_id = $this->getVar('id');
?>
<script>
    localStorage.clear();
</script>
<H1>Preview</H1>
<div id="tap-webapp">
    <iframe src="http://collective.local/app/plugins/providenceTourML/lib/tap-webapp/index.php?id=<?php print($tour_id); ?>" height="568" width="320" frameBorder="0"></iframe>
</div> 
<div class="editorBottomPadding"><!-- empty --></div>
<div style="clear:both;"><!-- EMPTY --></div>