<?php
/**
 * s7n functions and definitions
 *
 * @link https://s7n.co
 *
 * @author Afshin Zohari
 * @package WordPress
 * @since 1.0
 */

/**
 * Feature activation
 *
 * True => Enable, False => Disable
 */
$theme_options = false;
$post_views = true;
$comment_like_system = true;
$comment_dislike = true;
/**
 * Definitions
 */
define('WP_THEME_DIR', get_stylesheet_directory_uri() . '/');
define('SITE_URL', home_url());
define('SITE_NAME', get_bloginfo('name'));
define('SITE_DESCRIPTION', get_bloginfo('description'));

/**
 * Functions
 */
function s7n_logo()
{ ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo MAIN_DIR; ?>assets/images/s7n.svg);
            height: 65px;
            width: 320px;
            background-size: 320px 65px;
            background-repeat: no-repeat;
            padding-bottom: 30px;
        }
    </style>
<?php }

function get_content($URL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function s7n_url()
{
    return 'https://s7n.co';
}

function s7n_title()
{
    return __('Enjoy the difference!!', 's7n');
}

function s7n_powered()
{
    return __('<a href="https://s7n.ir" title="s7n Specialized and Professional Web Design & Development Company">Powered by s7n</a>', 's7n');
}

add_action('login_enqueue_scripts', 's7n_logo', 9999);
add_filter('login_headerurl', 's7n_url', 9999);
add_filter('login_headertext', 's7n_title', 9999);
add_filter('update_footer', 's7n_powered', 9999);

function s7n_site_icon_size($sizes)
{
    $sizes[] = 16;
    $sizes[] = 70;
    $sizes[] = 96;
    $sizes[] = 150;
    $sizes[] = 180;
    $sizes[] = 310;

    return $sizes;
}

function s7n_site_icon_tag($meta_tags)
{
    $meta_tags[] = sprintf('<link rel="icon" href="%s" sizes="16x16" />', esc_url(get_site_icon_url(null, 16)));
    $meta_tags[] = sprintf('<meta name="msapplication-square70x70logo" content="%s" />', esc_url(get_site_icon_url(null, 70)));
    $meta_tags[] = sprintf('<link rel="icon" href="%s" sizes="96x96" />', esc_url(get_site_icon_url(null, 96)));
    $meta_tags[] = sprintf('<meta name="msapplication-square150x150logo" content="%s" />', esc_url(get_site_icon_url(null, 150)));
    $meta_tags[] = sprintf('<link rel="apple-touch-icon" href="%s" />', esc_url(get_site_icon_url(null, 180)));
    $meta_tags[] = sprintf('<meta name="msapplication-square310x310logo" content="%s" />', esc_url(get_site_icon_url(null, 310)));

    return $meta_tags;
}

add_filter('site_icon_image_sizes', 's7n_site_icon_size');
add_filter('site_icon_meta_tags', 's7n_site_icon_tag');


/* post like & dislike*/

function s7n_post_like()
{
    if (!wp_verify_nonce($_POST['nonce'], 's7n_post_like_nonce') || !isset($_POST['nonce'])) {
        wp_send_json(array('status' => 0, 'message' => __('مشکلی پیش آمده دوباره امتحان کنید.')));
    }
    $like_cookie = null;
    $type = $_POST['type'];
    $post_id = $_POST['postid'];
    $ip = s7n_get_ip();
    if (isset($_COOKIE[$type . '_' . $post_id])) {
        $like_cookie = $_COOKIE[$type . '_' . $post_id];
    }
    $likes = get_post_meta($post_id, 's7n_post_' . $type, true);
    $likes_ip = get_post_meta($post_id, 's7n_post_' . $type . '_ips', true);
    $likes_ip = !$likes_ip ? array() : $likes_ip;
    $likes = !$likes ? 0 : $likes;
    if (!in_array($ip, $likes_ip) || $like_cookie != 1) {
        setcookie($type . '_' . $post_id, 1, time() + (86400 * 1825), "/"); // 86400 = 1 day
        array_push($likes_ip, $ip);
        $likes += 1;
    } else {
        setcookie($type . '_' . $post_id, 0, time() + (86400 * 1825), "/"); // 86400 = 1 day
        if (($key = array_search($ip, $likes_ip)) !== false) {
            unset($likes_ip[$key]);
        }
        $likes -= 1;
    }
    update_post_meta($post_id, 's7n_post_' . $type . '_ips', $likes_ip);
    update_post_meta($post_id, 's7n_post_' . $type, $likes);
    $likes = get_post_meta($post_id, 's7n_post_' . $type, true);
    wp_send_json(array('status' => 1, 'message' => __('با موفقیت بروزرسانی شد.'), 'info' => array('count' => $likes)));
}

function s7n_get_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}

function s7n_post_like_status($post_id = null, $type = null)
{
    $user_ip = s7n_get_ip();
    $ips = get_post_meta($post_id, 's7n_post_' . $type . '_ips', true);
    if ($ips && in_array($user_ip, $ips)) {
        return true;
    } else {
        return null;
    }
}


function s7n_post_like_count($post_id = null, $type = null)
{
    $likes = get_post_meta($post_id, 's7n_post_' . $type, true);
    if (!$likes) {
        return 0;
    }
    return $likes;
}

add_action('wp_ajax_nopriv_s7n_post_like', 's7n_post_like');
add_action('wp_ajax_s7n_post_like', 's7n_post_like');


function s7n_comment_like()
{
    if (!wp_verify_nonce($_POST['nonce'], 's7n_comment_like_nonce') || !isset($_POST['nonce'])) {
        wp_send_json(array('status' => 0, 'message' => __('مشکلی پیش آمده دوباره امتحان کنید.')));
    }
    $like_comment_cookie = null;
    $type = $_POST['type'];
    $post_id = $_POST['postid'];
    $comment_id = $_POST['commentid'];
    $ip = s7n_get_ip();
    if (isset($_COOKIE[$type . '_' . $post_id . '_' . $comment_id])) {
        $like_comment_cookie = $_COOKIE[$type . '_' . $post_id . '_' . $comment_id];
    }
    $likes = get_comment_meta($comment_id, 's7n_comment_' . $type, true);
    $likes_ip = get_comment_meta($comment_id, 's7n_comment_' . $type . '_ips', true);
    $likes_ip = !$likes_ip ? array() : $likes_ip;
    $likes = !$likes ? 0 : $likes;
    if (!in_array($ip, $likes_ip) || $like_comment_cookie != 1) {
        setcookie($type . '_' . $post_id . '_' . $comment_id, 1, time() + (86400 * 1825), "/"); // 86400 = 1 day
        array_push($likes_ip, $ip);
        $likes += 1;

    } else {
        setcookie($type . '_' . $post_id . '_' . $comment_id, 0, time() + (86400 * 1825), "/"); // 86400 = 1 day
        if (($key = array_search($ip, $likes_ip)) !== false) {
            unset($likes_ip[$key]);
        }
        $likes -= 1;
    }
    update_comment_meta($comment_id, 's7n_comment_' . $type . '_ips', $likes_ip);
    update_comment_meta($comment_id, 's7n_comment_' . $type, $likes);
    $likes = get_comment_meta($comment_id, 's7n_comment_' . $type, true);
    wp_send_json(array('status' => 1, 'message' => __('با موفقیت بروزرسانی شد.'), 'info' => array('count' => $likes)));
}

function s7n_comment_like_status($comment_id = null, $type = null)
{
    $user_ip = s7n_get_ip();
    $ips = get_comment_meta($comment_id, 's7n_comment_' . $type . '_ips', true);
    if ($ips && in_array($user_ip, $ips)) {
        return true;
    } else {
        return null;
    }
}

function s7n_comment_like_count($comment_id = null, $type = null)
{
    $likes = get_comment_meta($comment_id, 's7n_comment_' . $type, true);
    if (!$likes) {
        return 0;
    }
    return $likes;
}

add_action('wp_ajax_nopriv_s7n_comment_like', 's7n_comment_like');
add_action('wp_ajax_s7n_comment_like', 's7n_comment_like');
// class s7n_menu extends Walker {
// 	var $db_fields = array( 'parent' => 'menu_item_parent', 'id' => 'db_id' );
// 	var $tree_type = array( 'post_type', 'taxonomy', 'custom' );

// 	function start_lvl( &$output, $depth = 0, $args = array() ) {
// 		$classes     = array( 'sub-menu' );
// 		$class_names = join( ' ', apply_filters( 'nav_menu_submenu_css_class', $classes, $args, $depth ) );
// 		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
// 		$output      .= "<ul $class_names>";
// 	}

// 	function end_lvl( &$output, $depth = 0, $args = array() ) {
// 		$output .= "</ul>";
// 	}

// 	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
// 		$classes     = empty( $item->classes ) ? array() : (array) $item->classes;
// 		$args        = apply_filters( 'nav_menu_item_args', $args, $item, $depth );
// 		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
// 		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
// 		$output      .= '<li' . $class_names . ' itemprop="name">';

// 		$atts           = array();
// 		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
// 		$atts['target'] = ! empty( $item->target ) ? $item->target : '';
// 		$atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';
// 		$atts['href']   = ! empty( $item->url ) ? $item->url : '';

// 		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

// 		$attributes = '';
// 		foreach ( $atts as $attr => $value ) {
// 			if ( $value ) {
// 				$value      = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
// 				$attributes .= ' ' . $attr . '="' . $value . '"';
// 			}
// 		}

// 		$title = apply_filters( 'the_title', $item->title, $item->ID );

// 		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

// 		$item_output = $args->before;
// 		$item_output .= '<a itemprop="url"' . $attributes . '>';
// 		$item_output .= $args->link_before . $title . $args->link_after;
// 		$item_output .= '</a>';
// 		$item_output .= $args->after;

// 		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
// 	}

// 	function end_el( &$output, $item, $depth = 0, $args = array() ) {
// 		$output .= '</li>';
// 	}

// }

function s7n_pagination($both = 1, $middle = 2, $prev = '<span class="change-page-arrow prev-page"><i class="icon-left-line"></i></span>', $next = '<span class="change-page-arrow next-page"><i class="icon-right-line"></i></span>')
{
    global $wp_query;
    $max_num_pages = $wp_query->max_num_pages;
    if ($max_num_pages < 2) {
        return;
    }
    $big_number = 999999999;
    $args = array(
        'base' => str_replace($big_number, '%#%', esc_url(get_pagenum_link($big_number))),
        'total' => $max_num_pages,
        'current' => max(1, get_query_var('paged')),
        'end_size' => $both,
        'mid_size' => $middle,
        'prev_text' => $prev,
        'next_text' => $next,
        'before_page_number' => ' <span class="number-page">',
        'after_page_number' => '</span>'
    );
    ?>
    <div class="pagination-num num-fa">
        <div class="page-list">
            <?= paginate_links($args) ?>
        </div>
    </div>
    <?php
}


if ($post_views == true) {
    function s7n_set_views($postID = null)
    {
        if ($postID == null):
            global $post;
            $postID = $post->ID;
        endif;
        $view_count = s7n_get_views($postID) + 1;
        if ($view_count != 1) {
            update_post_meta($postID, 'views', ($view_count));
        } else {
            add_post_meta($postID, 'views', 1);
        }

        return $view_count;
    }

    function s7n_get_views($postID = null)
    {
        if ($postID == null):
            global $post;
            $postID = $post->ID;
        endif;

        return intval(get_post_meta($postID, 'views', true));
    }

    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);

    function posts_column_views($defaults)
    {
        $defaults['post_views'] = 'بازدید';

        return $defaults;
    }

    function posts_custom_column_views($column_name, $id)
    {
        if ($column_name === 'post_views') {
            echo s7n_set_views(get_the_ID());
        }
    }

    add_filter('manage_posts_columns', 'posts_column_views');
    add_action('manage_posts_custom_column', 'posts_custom_column_views', 5, 2);
}
//if ($comment_like_system == true) {
//
//
//    function s7n_check_comment_liked($id, $ip = null)
//    {
//        if ($ip == null) {
//            $ip = s7n_get_ip();
//        }
//        if (in_array('like_' . $ip, get_comment_meta($id, 'cm_like_ip'), true)) {
//            return true;
//        }
//
//        return false;
//    }
//
//    function s7n_check_comment_disliked($id, $ip = null)
//    {
//        if ($ip == null) {
//            $ip = s7n_get_ip();
//        }
//        if (in_array('dislike_' . $ip, get_comment_meta($id, 'cm_like_ip'), true)) {
//            return true;
//        }
//
//        return false;
//    }
//
//    function s7n_comment_like($id, $ip)
//    {
//        $get_likes = s7n_get_the_comment_like($id);
//        if (!s7n_check_comment_liked($id, $ip)) {
//            if (!s7n_check_comment_disliked($id, $ip)) {
//                if (!add_comment_meta($id, 'cm_likes', ($get_likes + 1), true)) {
//                    update_comment_meta($id, 'cm_likes', ($get_likes + 1));
//                }
//                add_comment_meta($id, 'cm_like_ip', 'like_' . $ip);
//
//                return s7n_get_the_comment_like($id);
//            } else {
//                return 'false';
//            }
//        } else {
//            s7n_comment_unlike($id, $get_likes, $ip);
//
//            return s7n_get_the_comment_like($id);
//        }
//    }
//
//    function s7n_comment_dislike($id, $ip)
//    {
//        $get_dislikes = s7n_get_the_comment_dislike($id);
//        if (!s7n_check_comment_disliked($id, $ip)) {
//            if (!s7n_check_comment_liked($id, $ip)) {
//                if (!add_comment_meta($id, 'cm_dislikes', ($get_dislikes + 1), true)) {
//                    update_comment_meta($id, 'cm_dislikes', ($get_dislikes + 1));
//                }
//                add_comment_meta($id, 'cm_like_ip', 'dislike_' . $ip);
//
//                return s7n_get_the_comment_dislike($id);
//            } else {
//                return 'false';
//            }
//        } else {
//            s7n_comment_undislike($id, $get_dislikes, $ip);
//
//            return s7n_get_the_comment_dislike($id);
//        }
//    }
//
//    function s7n_comment_unlike($id, $get_likes, $ip)
//    {
//        update_comment_meta($id, 'cm_likes', ($get_likes - 1));
//        delete_comment_meta($id, 'cm_like_ip', 'like_' . $ip);
//    }
//
//    function s7n_comment_undislike($id, $get_dislikes, $ip)
//    {
//        update_comment_meta($id, 'cm_dislikes', ($get_dislikes - 1));
//        delete_comment_meta($id, 'cm_like_ip', 'dislike_' . $ip);
//    }
//
//    function s7n_the_comment_like($id)
//    {
//        echo s7n_get_the_comment_like($id);
//    }
//
//    function s7n_get_the_comment_like($id)
//    {
//        return intval(get_comment_meta($id, 'cm_likes', true));
//    }
//
//    function s7n_the_comment_dislike($id)
//    {
//        echo s7n_get_the_comment_dislike($id);
//    }
//
//    function s7n_get_the_comment_dislike($id)
//    {
//        return intval(get_comment_meta($id, 'cm_dislikes', true));
//    }
//
//    function s7n_comment_like_system_ajax()
//    {
//        if (check_ajax_referer('comment_like_referrer', 'referrer', false) == false) {
//            wp_die('Forbbiden access!', '', array(403));
//        }
//        if ((!$_POST['id']) || (!$_POST['type'])) {
//            echo false;
//            die;
//        }
//        $id = $_POST['id'];
//        $type = $_POST['type'];
//        $ip = s7n_get_ip();
//        if ($type === 'like') {
//            echo s7n_comment_like($id, $ip);
//        } else {
//            echo s7n_comment_dislike($id, $ip);
//        }
//        die;
//    }
//
//    function s7n_comment_like_ajax()
//    {
//        if (check_ajax_referer('comment_like_referrer', 'referrer', false) == false) {
//            wp_die('Forbbiden access!', '', array(403));
//        }
//        if ((!$_POST['id'])) {
//            echo false;
//            die;
//        }
//
//        $id = $_POST['id'];
//        $ip = s7n_get_ip();
//        echo s7n_comment_like($id, $ip);
//        die;
//    }
//
//    if ($comment_dislike == true) {
//        add_action('wp_ajax_comment_like', 's7n_comment_like_system_ajax');
//        add_action('wp_ajax_nopriv_comment_like', 's7n_comment_like_system_ajax');
//    } else {
//        add_action('wp_ajax_comment_like', 's7n_comment_like_ajax');
//        add_action('wp_ajax_nopriv_comment_like', 's7n_comment_like_ajax');
//    }
//}
add_filter('sanitize_user', 'strtolower');
add_filter('login_errors', function ($error) {
    global $errors;
    $err_codes = $errors->get_error_codes();
    if (in_array('invalid_username', $err_codes) || in_array('incorrect_password', $err_codes)) {
        $error = (in_array('invalid_username', $err_codes)) ? '<strong>ERROR</strong>: Invalid username/password. <a href="%s">Lost your password</a>?' : '<strong>ERROR</strong>: Invalid username/password. <a href="%2$s">Lost your password</a>?';
    }

    return $error;
});

/*
 * Function creates post duplicate as a draft and redirects then to the edit post screen
 */
function rd_duplicate_post_as_draft()
{
    global $wpdb;
    if (!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'rd_duplicate_post_as_draft' == $_REQUEST['action']))) {
        wp_die('No post to duplicate has been supplied!');
    }

    /*
     * Nonce verification
     */
    if (!isset($_GET['duplicate_nonce']) || !wp_verify_nonce($_GET['duplicate_nonce'], basename(__FILE__))) {
        return;
    }


    /*
     * get the original post id
     */
    $post_id = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']));
    /*
     * and all the original post data then
     */
    $post = get_post($post_id);

    /*
     * if you don't want current user to be the new post author,
     * then change next couple of lines to this: $new_post_author = $post->post_author;
     */
    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;

    /*
     * if post data exists, create the post duplicate
     */
    if (isset($post) && $post != null) {

        /*
         * new post data array
         */
        $args = array(
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'post_author' => $new_post_author,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_name' => $post->post_name,
            'post_parent' => $post->post_parent,
            'post_password' => $post->post_password,
            'post_status' => 'draft',
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'to_ping' => $post->to_ping,
            'menu_order' => $post->menu_order
        );

        /*
         * insert the post by wp_insert_post() function
         */
        $new_post_id = wp_insert_post($args);

        /*
         * get all current post terms ad set them to the new post draft
         */
        $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }

        /*
         * duplicate all post meta just in two SQL queries
         */
        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
        if (count($post_meta_infos) != 0) {
            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;
                if ($meta_key == '_wp_old_slug') {
                    continue;
                }
                $meta_value = addslashes($meta_info->meta_value);
                $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
            }
            $sql_query .= implode(" UNION ALL ", $sql_query_sel);
            $wpdb->query($sql_query);
        }


        /*
         * finally, redirect to the edit post screen for the new draft
         */
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
        exit;
    } else {
        wp_die('Post creation failed, could not find original post: ' . $post_id);
    }
}


add_action('admin_action_rd_duplicate_post_as_draft', 'rd_duplicate_post_as_draft');

/*
 * Add the duplicate link to action list for post_row_actions
 */
function rd_duplicate_post_link($actions, $post)
{
    if (current_user_can('edit_posts')) {
        $actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=rd_duplicate_post_as_draft&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce') . '" title="Duplicate this item" rel="permalink">Duplicate</a>';
    }

    return $actions;
}

add_filter('post_row_actions', 'rd_duplicate_post_link', 10, 2);

/*-------------------------------------
 </> @author  Hossein Naghneh - s7n.co
 </> Limit Expert
 </> @version 1.0.0
/*------------------------------------*/
function s7n_get_excerpt($character, $post_id = null)
{
    if ($post_id == '') {
        $post_id = get_the_ID();
    }
    if (get_the_excerpt($post_id)) {
        $excerpt = get_the_excerpt($post_id);
    } else {
        $excerpt = get_the_content($post_id);
    }
    $excerpt = strip_shortcodes($excerpt);
    $excerpt = strip_tags($excerpt);
    $the_str = mb_substr($excerpt, 0, $character);

    return $the_str . '...';
}

/*-------------------------------------
 </> @author  Hossein Naghneh - s7n.co
 </> Limit text
 </> @version 1.0.0
/*------------------------------------*/
function s7n_limiter($string, $character)
{
    $the_str = mb_substr($string, 0, $character);
    return $the_str . '...';
}

// post content limiter
function get_limit_content($num, $id)
{
    $theContent = get_the_content($id);
    $output = wp_strip_all_tags($theContent, true);
    $search = array('|\[(.+?)\](.+?\[/\\1\])?|s', '/\s+/S');
    $replace = array('', ' ');
    $output = preg_replace($search, $replace, $output);
    $content = explode(' ', $output, $num);
    array_pop($content);
    $content = implode(' ', $content) . " ...";
    return $content;
}

/* login register */
if (!is_user_logged_in()) {
    add_action('init', 'ajax_login_init');
} else {
    add_action('init', 'ajax_logout_init');
}
function ajax_login_init()
{
    add_action('wp_ajax_nopriv_ajax_login', 'ajax_login');
    add_action('wp_ajax_nopriv_ajax_register', 'ajax_register');
}

function ajax_logout_init()
{
    add_action('wp_ajax_s7n_ajax_logout', 's7n_ajax_logout');
}

function ajax_login()
{
    // First check the nonce, if it fails the function will break
    check_ajax_referer('ajax-login-nonce', 'security');

    // Nonce is checked, get the POST data and sign user on
    $info = array();
    $info['user_login'] = $_POST['login_username'];
    $info['user_password'] = $_POST['login_password'];
//    $info['remember'] = true;

    $user_signon = wp_signon($info, false);
    if (is_wp_error($user_signon)) {
        wp_send_json(array('status' => 0, 'message' => __('نام کاربری و یا پسورد اشتباه است.')));
    } else {
        wp_send_json(array('status' => 1, 'message' => __('ورود با موفقیت انجام شد، به صفحه اصلی منتقل می شوید.')));;
    }
}

function ajax_register()
{
    check_ajax_referer('ajax-register-nonce', 'security');
    // Post values
    $username = $_POST['register_username'];
    $password = $_POST['register_password'];
    $email = $_POST['register_email'];
    $name = $_POST['register_name'];
    $nick = $_POST['register_name'];

    /**
     * IMPORTANT: You should make server side validation here!
     *
     */

    $userdata = array(
        'user_login' => $username,
        'user_pass' => $password,
        'user_password' => $password,
        'user_email' => $email,
        'first_name' => $name,
        'nickname' => $nick,
    );

    $user_id = wp_insert_user($userdata);

    // Return
    if (!is_wp_error($user_id)) {
        $user_signon = wp_signon($userdata, false);
        if (!is_wp_error($user_signon)) {
            wp_send_json(array('status' => 2, 'message' => __('ثبت نام با موفقیت انجام شد و وارد شدید.')));
        } else {
            wp_send_json(array('status' => 1, 'message' => __('ثبت نام با موفقیت انجام شد.')));
        }
    } else {
        wp_send_json(array('status' => 0, 'message' => __($user_id->get_error_message())));
    }
}

function s7n_ajax_logout()
{
    check_ajax_referer('ajax-logout-nonce', 'ajaxsecurity');

    wp_logout();
    ob_clean(); // probably overkill for this, but good habit
    echo 'adios!!';
    wp_die();
}

add_action('wp_logout', 'auto_redirect_after_logout');
function auto_redirect_after_logout()
{
    wp_redirect(home_url());
    exit();
}

function s7n_breadcrumbs($separator = '&gt;', $breadcrums_id = 'breadcrumbs', $breadcrums_class = 'breadcrumbs', $home_title = 'صفحه نخست', $custom_taxonomy = 'product_cat',$prefix=null)
{

    // Get the query & post information
    global $post, $wp_query;

    // Do not display on the homepage
    if (!is_front_page()) {

        // Build the breadcrums
        echo '<ul id="' . $breadcrums_id . '" class="' . $breadcrums_class . '">';

        // Home page
        echo '<li class="item-home"><a class="bread-link bread-home" href="' . get_home_url() . '" title="' . $home_title . '">' . $home_title . '</a></li>';
        if ($separator) {
            echo '<li class="separator separator-home"> ' . $separator . ' </li>';
        }

        if (is_archive() && !is_tax() && !is_category() && !is_tag()) {

            echo '<li class="item-current item-archive"><strong class="bread-current bread-archive">' . post_type_archive_title($prefix, false) . '</strong></li>';

        } else if (is_archive() && is_tax() && !is_category() && !is_tag()) {

            // If post is a custom post type
            $post_type = get_post_type();

            // If it is a custom post type display name and link
            if ($post_type != 'post') {

                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);

                echo '<li class="item-cat item-custom-post-type-' . $post_type . '"><a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . $post_type_object->labels->name . '</a></li>';
                if ($separator) {
                echo '<li class="separator"> ' . $separator . ' </li>';
            }
            }

            $custom_tax_name = get_queried_object()->name;
            echo '<li class="item-current item-archive"><strong class="bread-current bread-archive">' . $custom_tax_name . '</strong></li>';

        } else if (is_single()) {

            // If post is a custom post type
            $post_type = get_post_type();

            // If it is a custom post type display name and link
            if ($post_type != 'post') {

                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);

                echo '<li class="item-cat item-custom-post-type-' . $post_type . '"><a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . $post_type_object->labels->name . '</a></li>';
                if ($separator) {
                echo '<li class="separator"> ' . $separator . ' </li>';

            }
            }

            // Get post category info
            $category = get_the_category();

            if (!empty($category)) {

                // Get last category post is in
                $category_array = array_values($category);
                $last_category = end($category_array);

                // Get parent any categories and create array
                $get_cat_parents = rtrim(get_category_parents($last_category->term_id, true, ','), ',');
                $cat_parents = explode(',', $get_cat_parents);

                // Loop through parent categories and store in variable $cat_display
                $cat_display = '';
                foreach ($cat_parents as $parents) {
                    $cat_display .= '<li class="item-cat">' . $parents . '</li>';
                    if ($separator) {
                    $cat_display .= '<li class="separator"> ' . $separator . ' </li>';
                }
                }

            }

            // If it's a custom post type within a custom taxonomy
            $taxonomy_exists = taxonomy_exists($custom_taxonomy);
            if (empty($last_category) && !empty($custom_taxonomy) && $taxonomy_exists) {

                $taxonomy_terms = get_the_terms($post->ID, $custom_taxonomy);
                $cat_id = $taxonomy_terms[0]->term_id;
                $cat_nicename = $taxonomy_terms[0]->slug;
                $cat_link = get_term_link($taxonomy_terms[0]->term_id, $custom_taxonomy);
                $cat_name = $taxonomy_terms[0]->name;

            }

            // Check if the post is in a category
            if (!empty($last_category)) {
                echo $cat_display;
                echo '<li class="item-current item-' . $post->ID . '"><strong class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</strong></li>';

                // Else if post is in a custom taxonomy
            } else if (!empty($cat_id)) {

                echo '<li class="item-cat item-cat-' . $cat_id . ' item-cat-' . $cat_nicename . '"><a class="bread-cat bread-cat-' . $cat_id . ' bread-cat-' . $cat_nicename . '" href="' . $cat_link . '" title="' . $cat_name . '">' . $cat_name . '</a></li>';
                if ($separator) {
                echo '<li class="separator"> ' . $separator . ' </li>';
                }
                echo '<li class="item-current item-' . $post->ID . '"><strong class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</strong></li>';

            } else {

                echo '<li class="item-current item-' . $post->ID . '"><strong class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</strong></li>';

            }

        } else if (is_category()) {

            // Category page
            echo '<li class="item-current item-cat"><strong class="bread-current bread-cat">' . single_cat_title('', false) . '</strong></li>';

        } else if (is_page()) {

            // Standard page
            if ($post->post_parent) {

                // If child page, get parents
                $anc = get_post_ancestors($post->ID);

                // Get parents in the right order
                $anc = array_reverse($anc);

                // Parent page loop
                if (!isset($parents)) $parents = null;
                foreach ($anc as $ancestor) {
                    $parents .= '<li class="item-parent item-parent-' . $ancestor . '"><a class="bread-parent bread-parent-' . $ancestor . '" href="' . get_permalink($ancestor) . '" title="' . get_the_title($ancestor) . '">' . get_the_title($ancestor) . '</a></li>';
                    if ($separator) {
                        $parents .= '<li class="separator separator-' . $ancestor . '"> ' . $separator . ' </li>';
                    }
                }

                // Display parent pages
                echo $parents;

                // Current page
                echo '<li class="item-current item-' . $post->ID . '"><strong title="' . get_the_title() . '"> ' . get_the_title() . '</strong></li>';

            } else {

                // Just display current page if not parents
                echo '<li class="item-current item-' . $post->ID . '"><strong class="bread-current bread-' . $post->ID . '"> ' . get_the_title() . '</strong></li>';

            }

        } else if (is_tag()) {

            // Tag page

            // Get tag information
            $term_id = get_query_var('tag_id');
            $taxonomy = 'post_tag';
            $args = 'include=' . $term_id;
            $terms = get_terms($taxonomy, $args);
            $get_term_id = $terms[0]->term_id;
            $get_term_slug = $terms[0]->slug;
            $get_term_name = $terms[0]->name;

            // Display the tag name
            echo '<li class="item-current item-tag-' . $get_term_id . ' item-tag-' . $get_term_slug . '"><strong class="bread-current bread-tag-' . $get_term_id . ' bread-tag-' . $get_term_slug . '">' . $get_term_name . '</strong></li>';

        } elseif (is_day()) {

            // Day archive

            // Year link
            echo '<li class="item-year item-year-' . get_the_time('Y') . '"><a class="bread-year bread-year-' . get_the_time('Y') . '" href="' . get_year_link(get_the_time('Y')) . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</a></li>';
            if ($separator) {
                echo '<li class="separator separator-' . get_the_time('Y') . '"> ' . $separator . ' </li>';
            }
            // Month link
            echo '<li class="item-month item-month-' . get_the_time('m') . '"><a class="bread-month bread-month-' . get_the_time('m') . '" href="' . get_month_link(get_the_time('Y'), get_the_time('m')) . '" title="' . get_the_time('M') . '">' . get_the_time('M') . ' Archives</a></li>';
            if ($separator) {
                echo '<li class="separator separator-' . get_the_time('m') . '"> ' . $separator . ' </li>';
            }
            // Day display
            echo '<li class="item-current item-' . get_the_time('j') . '"><strong class="bread-current bread-' . get_the_time('j') . '"> ' . get_the_time('jS') . ' ' . get_the_time('M') . ' Archives</strong></li>';

        } else if (is_month()) {

            // Month Archive

            // Year link
            echo '<li class="item-year item-year-' . get_the_time('Y') . '"><a class="bread-year bread-year-' . get_the_time('Y') . '" href="' . get_year_link(get_the_time('Y')) . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</a></li>';
            if ($separator) {
                echo '<li class="separator separator-' . get_the_time('Y') . '"> ' . $separator . ' </li>';
            }
            // Month display
            echo '<li class="item-month item-month-' . get_the_time('m') . '"><strong class="bread-month bread-month-' . get_the_time('m') . '" title="' . get_the_time('M') . '">' . get_the_time('M') . ' Archives</strong></li>';

        } else if (is_year()) {

            // Display year archive
            echo '<li class="item-current item-current-' . get_the_time('Y') . '"><strong class="bread-current bread-current-' . get_the_time('Y') . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</strong></li>';

        } else if (is_author()) {

            // Auhor archive

            // Get the author information
            global $author;
            $userdata = get_userdata($author);

            // Display author name
            echo '<li class="item-current item-current-' . $userdata->user_nicename . '"><strong class="bread-current bread-current-' . $userdata->user_nicename . '" title="' . $userdata->display_name . '">' . 'Author: ' . $userdata->display_name . '</strong></li>';

        } else if (get_query_var('paged')) {

            // Paginated archives
            echo '<li class="item-current item-current-' . get_query_var('paged') . '"><strong class="bread-current bread-current-' . get_query_var('paged') . '" title="Page ' . get_query_var('paged') . '">' . __('Page') . ' ' . get_query_var('paged') . '</strong></li>';

        } else if (is_search()) {

            // Search results page
            echo '<li class="item-current item-current-' . get_search_query() . '"><strong class="bread-current bread-current-' . get_search_query() . '" title="جستجو برای: ' . get_search_query() . '">جستجو برای: ' . get_search_query() . '</strong></li>';

        } elseif (is_404()) {

            // 404 page
            echo '<li>' . 'Error 404' . '</li>';
        }

        echo '</ul>';

    }

}

add_filter( 'get_the_archive_title', function ($title) {
    if ( is_category() ) {
        $title = single_cat_title( '', false );
    } elseif ( is_tag() ) {
        $title = single_tag_title( '', false );
    } elseif ( is_author() ) {
        $title = '<span class="vcard">' . get_the_author() . '</span>' ;
    } elseif ( is_tax() ) {//for custom post types
        $title = sprintf( __( '%1$s' ), single_term_title( '', false ) );
    }elseif ( is_post_type_archive() ) {
        /* translators: Post type archive title. %s: Post type name */
        $title = sprintf( __( '%s' ), post_type_archive_title( '', false ) );
    }
    return $title;
});