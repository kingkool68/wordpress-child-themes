<?php
$parent = get_template_directory() . '/scss/base/_variables.scss';
$child = get_stylesheet_directory() . '/scss/base/_variables.scss';

$context = array(
	'colors' => WP_Styleguide::get_sass_colors( array( $parent, $child ) ),
);
echo Sprig::render( 'styleguide-common-html.twig', $context );
