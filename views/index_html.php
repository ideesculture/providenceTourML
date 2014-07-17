<?php
$va_available_tours 	= $this->getVar('available_tours');
?>

<h1>Home</h1>


<table style="border:1px solid gray;width:100%;">
<?php
foreach($va_available_tours as $tour) : ?>
	
	<tr>
		<td><?php print $tour["tour_id"]; ?></td>
		<td><?php print $tour["tour_code"]; ?></td>
		<td><?php print $tour["preferred_labels"]; ?></td>
		<td><a href="http://<?php print __CA_SITE_HOSTNAME__ . "/".__CA_URL_ROOT__."index.php/providenceTourML/providenceTourML/preview/id/".$tour["tour_id"]; ?>" class='form-button'><span class='form-button'><img src='<?php print "http://".__CA_SITE_HOSTNAME__ . "/".__CA_URL_ROOT__?>/themes/default/graphics/buttons/lens.png' border='0' alt='Preview' class='form-button-left' style='padding-right: 10px;'/>Preview</a>
	</tr>

<?php
endforeach;
?>
</table>