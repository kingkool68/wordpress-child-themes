jQuery(document).ready(function($) {
	$('#wpbody').on('blur', '.instagram-source', function() {
		var $this = $(this);
		var instagramSource = $this.val();
		if ( ! instagramSource ) {
			return;
		}
		var $parent = $this.parent();
		var data = {
			'action': 'daddio_location_sync',
			'location-id': 0,
			'instagram-source': instagramSource
		};
		$parent.addClass('loading');

		$.post(ajaxurl, data )
			.done(function(resp) {
				var html = '<p class="success">' + resp.data.message + '</p>';
				$parent.html( html ).addClass('success');
			})
			.fail(function(resp) {
				console.log( resp );
				var html = '<p class="fail">' + resp.data.message + '</p>';
				$parent.html( html ).addClass('fail');
			}).always(function() {
				$parent.removeClass('loading');
			});
	});
});
