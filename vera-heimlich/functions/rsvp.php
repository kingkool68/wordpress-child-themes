<?php
function filter_daddio_rsvp_page_slug() {
	return 'rsvp-veras-first-birthday-party';
}
add_filter( 'daddio_rsvp_page_slug', 'filter_daddio_rsvp_page_slug' );

function filter_daddio_rsvp_yes_content() {
$content = <<<'EOD'
	<h2 class="birthday-rsvp-title">Great! We'll see you there.</h2>
	<link href="https://addtocalendar.com/atc/1.5/atc-style-button-icon.css" rel="stylesheet" type="text/css">
	<p class="addtocalendar">
		<var class="atc_event">
			<var class="atc_date_start">2018-02-24 14:00:00</var>
			<var class="atc_date_end">2018-02-24 17:00:00</var>
			<var class="atc_timezone">America/New_York</var>
			<var class="atc_title">Vera Heimlich's 1st Birthday Party</var>
			<var class="atc_description">https://veraheimlich.com/rsvp/</var>
			<var class="atc_location">Tivoli Community Association 13101 Nordic Hill Dr, Silver Spring, MD 20906</var>
			<var class="atc_organizer">Kristina Heimlich</var>
			<var class="atc_organizer_email">us@12hugo.com</var>
		</var>
	</p>
	<script type="text/javascript">
		(function () {
			if (window.addtocalendar)if (typeof window.addtocalendar.start == "function")return;
			if (window.ifaddtocalendar == undefined) { window.ifaddtocalendar = 1;
				var d = document, s = d.createElement('script'), g = 'getElementsByTagName';
				s.type = 'text/javascript';s.charset = 'UTF-8';s.async = true;
				s.src = ('https:' == window.location.protocol ? 'https' : 'http')+'://addtocalendar.com/atc/1.5/atc.min.js';
				var h = d[g]('body')[0];h.appendChild(s); }})();
	</script>

EOD;
	$happy_img = '<img width="1080" height="1350" src="https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse.jpg" class="aligncenter from-instagram" alt="" srcset="https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse.jpg 1080w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse-240x300.jpg 240w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse-768x960.jpg 768w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse-819x1024.jpg 819w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse-320x400.jpg 320w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse-360x450.jpg 360w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse-480x600.jpg 480w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse-640x800.jpg 640w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2017/12/BdV6trYFJse-800x1000.jpg 800w" sizes="(max-width: 1080px) 100vw, 1080px">';
	$content .= $happy_img;
	return $content;
}
add_filter( 'daddio_rsvp_yes_content', 'filter_daddio_rsvp_yes_content' );
