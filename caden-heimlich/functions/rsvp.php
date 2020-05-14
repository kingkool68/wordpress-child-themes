<?php
function filter_daddio_rsvp_page_slug() {
	return 'rsvp-for-veras-third-birthday-party';
}
add_filter( 'daddio_rsvp_page_slug', 'filter_daddio_rsvp_page_slug' );

function filter_daddio_rsvp_yes_content() {
	$content   = <<<'EOD'
	<h2 class="birthday-rsvp-title">Great! We'll see you there.</h2>
	<link href="https://addtocalendar.com/atc/1.5/atc-style-button-icon.css" rel="stylesheet" type="text/css">
	<p class="addtocalendar">
		<var class="atc_event">
			<var class="atc_date_start">2020-02-23 11:00:00</var>
			<var class="atc_date_end">2020-02-23 13:00:00</var>
			<var class="atc_timezone">America/New_York</var>
			<var class="atc_title">Vera Heimlich's 3rd Birthday Party</var>
			<var class="atc_description">https://veraheimlich.com/rsvp/</var>
			<var class="atc_location">EVERKIDS Playhouse 5405 Twin Knolls Rd #3, Columbia, MD 21045</var>
			<var class="atc_organizer">Russell & Kristina Heimlich</var>
			<var class="atc_organizer_email">naudebynature@gmail.com</var>
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
	$happy_img = '<img width="1080" height="1080" src="https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0.jpg" class="aligncenter from-instagram" alt="" loading="lazy" srcset="https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0.jpg 1080w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-150x150.jpg 150w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-300x300.jpg 300w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-768x768.jpg 768w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-1024x1024.jpg 1024w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-320x320.jpg 320w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-360x360.jpg 360w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-480x480.jpg 480w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-640x640.jpg 640w, https://turbo.russellheimlich.com/wp-content/uploads/sites/5/2019/09/B2cNrbshhZ_I8mz1GLVKK2Aj7BGR0ymcNEfEMs0-800x800.jpg 800w" sizes="(max-width: 1080px) 100vw, 1080px">';
	$content  .= $happy_img;
	return $content;
}
add_filter( 'daddio_rsvp_yes_content', 'filter_daddio_rsvp_yes_content' );
