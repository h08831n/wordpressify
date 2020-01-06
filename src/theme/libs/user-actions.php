<?php
function update_user_list($userId = null, $listName = null, $postId = null, $type = 'add')
{
    if(!$listName){
        $listName = $_POST['list_name'];
    }
    if(!$postId){
        $postId = $_POST['post_id'];
    }
    if(!$type){
        $type = $_POST['update_type'];
    }
    if (is_user_logged_in()) {
        $userId = wp_get_current_user()->ID;
        $userList = get_user_list($userId, $listName);
        if ($type == 'add') {
            if(!in_array($postId,$userList)){
                array_push($userList, $postId);
                update_user_meta($userId, $listName, $userList);
            }
        } elseif ('remove') {
            if (($key = array_search($postId, $userList)) !== false) {
                unset($userList[$key]);
            }
            update_user_meta($userId, $listName, $userList);
        }elseif('clean'){
            update_user_meta($userId, $listName, null);
        }
        wp_send_json(
            array(
                'status' => 1,
                'message' => __('با موفقیت بروزرسانی شد.'),
                'info' => array(
                    'list' => $userList,
                    'type' => $type
                )
            )
        );
    } else {
        wp_send_json(
            array(
                'status' => 0,
                'message' => __('ابتدا باید وارد شوید.')
            )
        );
    }
}

add_action('wp_ajax_update_user_list', 'update_user_list');
add_action('wp_ajax_nopriv_update_user_list', 'update_user_list');

function get_user_list($userId, $listName)
{
    $userList = get_user_meta($userId, $listName, true);
    if ($userList == null) {
        $userList = array();
    }
    return $userList;
}

function is_in_user_list($userId = null, $listName, $postId)
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $userList = get_user_list($user->ID, $listName);
        return in_array($postId, $userList);
    } else {
        return false;
    }
}

/* update profile */
function update_user_info()
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $user_id = $user->ID;
        if (isset($_POST['first_name'])) {
            $user_first_name = $_POST['first_name'];
            update_user_meta($user_id, 'first_name', $user_first_name);
        }
        if (isset($_POST['last_name'])) {
            $user_last_name = $_POST['last_name'];
            update_user_meta($user_id, 'last_name', $user_last_name);
        }
        if (isset($_POST['user_email'])) {
            $user_email = $_POST['user_email'];
            update_user_meta($user_id, 'user_email', $user_email);
        }
        wp_send_json(
            array(
                'status' => 1,
                'message' => __('اطلاعات پروفایل شما بروزرسانی شد.')
            )
        );
    } else {
        wp_send_json(
            array(
                'status' => 0,
                'message' => __('ابتدا باید وارد شوید.')
            )
        );
    }
}

add_action('wp_ajax_update_user_info', 'update_user_infday-remainingo');