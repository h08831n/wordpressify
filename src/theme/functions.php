<?php
include 'libs/main-functions.php';
include 'libs/user-actions.php';
//include 'libs/jDateTime.php';
function wordpressify_resources() {
	wp_enqueue_style( 'style', get_stylesheet_uri() );
	wp_enqueue_script( 'header_js', get_template_directory_uri() . '/js/header-bundle.js', null, 1.0, false );
	wp_enqueue_script( 'footer_js', get_template_directory_uri() . '/js/footer-bundle.js', null, 1.0, true );
}

add_action( 'wp_enqueue_scripts', 'wordpressify_resources' );

// Theme setup
function wordpressify_setup() {
	// Handle Titles
	add_theme_support( 'title-tag' );

}

add_action( 'after_setup_theme', 'wordpressify_setup' );


add_image_size('test', 150, 150, true);
function the_thumbnail($sizeof, $class = null, $postId = null)
{
    if ($postId == null) {
        $postId = get_the_ID();
    }

    if (has_post_thumbnail()) {
        echo get_the_post_thumbnail($postId, $sizeof, array('class' => $class));
    } elseif ($sizeof == 'test') {
        echo '<img src="' . WP_THEME_DIR . 'img/no-thumb-test.jpg" title="' . get_the_title() . '"  alt="' . get_the_title() . '" width="150" height="150"/>';
    } 
}

//show_admin_bar( false );

// Checks if there are any posts in the results
function is_search_has_results() {
	return 0 != $GLOBALS['wp_query']->found_posts;
}