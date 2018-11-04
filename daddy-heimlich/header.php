<?php
ob_start();
include get_template_directory() . '/svg/zadie-heimlich-icons.svg';
$svg_icons = ob_get_clean();

$menu_args = array(
	'theme_location' => 'main-menu',
	'container'      => false,
	'menu_class'     => 'holder',
	'menu_id'        => false,
	'echo'           => false,
);

$context = array(
	'svg_icons'                   => $svg_icons,
	'site_url'                    => get_site_url(),
	'site_title'                  => get_bloginfo('name'),
	'child_birthday_machine_time' => date( DATE_W3C, get_childs_birthday() ),
	'childs_current_age'          => get_childs_current_age(),
	'main_menu'                   => wp_nav_menu( $menu_args ),
);
Sprig::out( 'header.twig', $context );
