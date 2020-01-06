<?php
get_header();

$fake_post_id = 0;
$result = update_post_meta( $fake_post_id, 'latitude', 'foo' );
var_dump( $result );

get_footer();
