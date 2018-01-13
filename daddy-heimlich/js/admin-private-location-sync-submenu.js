jQuery(document).ready(function($) {
	$('#wpbody').on('blur', '.instagram-source', function() {
		var $this = $(this);
		var instagramSource = $this.val();
		if ( ! instagramSource ) {
			return;
		}
		var $parent = $this.parent();
		var $post = $this.parents('.post');
		var postID = $parent.find('.post-id').val();
		var data = {
			'action': 'daddio_private_location_sync',
			'post-id': postID,
			'instagram-source': instagramSource
		};
		$post.addClass('loading')

		$.post(ajaxurl, data )
			.done(function(resp) {
				var html = '<p class="success">' + resp.data.message + '</p>';
				$parent.html( html );
				$post.addClass('success');
			})
			.fail(function(resp) {
				var html = '<p class="fail">' + resp.data.message + '</p>';
				$parent.html( html );
				$post.addClass('fail');
			}).always(function() {
				$post.removeClass('loading');
			});
	});
});
