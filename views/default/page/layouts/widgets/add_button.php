<?php
/**
 * Button area for showing the add widgets panel
 */

elgg_load_js('lightbox');
elgg_load_css('lightbox');

?>
<div class="elgg-widget-add-control">
	<?php
	$options = array(
		'id' => 'widgets-add-panel',
		'text' => elgg_echo('widgets:add'),
		'class' => 'elgg-button elgg-button-action elgg-lightbox',
		'data-colorbox-opts' => '{"inline":true, "href":"#widget_manager_widgets_select", "innerWidth": 600, "maxHeight": "80%"}'
	);
	
	if (elgg_in_context("iframe_dashboard")) {
		// TODO: why hide? we could also not output the button
		$options["style"] = "visibility: hidden;";
	}
	echo elgg_view('output/url', $options);
	
	?>
</div>