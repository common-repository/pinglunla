<?php
require_once("../../../wp-includes/version.php");
if ( version_compare( $wp_version, '2.6', '>' ) ) {
	require_once("../../../wp-load.php");
} else {
	require_once("../../../wp-config.php");
	require_once("../../../wp-settings.php");
}
include_once("./pinglunla-utils.php");

set_time_limit(0);
define('PAGE_SIZE', 200);


if ($_GET['reset'] == 1) {
    update_option('pinglunla_exported_cid', 0);
    $status = 'fail';
    $msg = "重置成功，您可以从零开始导入评论";
} else {
    if(isset($_GET['of'])) {
        $offset = intval($_GET['of']);
    } else {
        $offset = 0;
    }
    $exporte_num = 0;

    if (current_user_can('manage_options')) {
        $comments = pinglunla_retrieve_comment($offset, PAGE_SIZE);
        if ($comments) {
            $wxr = pinglunla_wp2json($comments);
            $post_data = array(
                "ict" => "common_json",
                "Filedata" => $wxr,
                "hs" => $_SERVER["HTTP_HOST"],
                "pg" => 1
            );

            $res = pinglunla_post_to('http://'.PLL_URL.'/webmaster/import_comments', $post_data);
            if ($res == 'ok') {
                $status = 'partial';
                $msg = "";
                
                $exported_num = count($comments);
                update_option("pinglunla_exported_cid", $comments[$exported_num-1]->comment_ID);
            } else {
                $status = 'fail';
                $msg = "网络错误, 请稍候再试";
            }
        }
        
        if (empty($comments) || pinglunla_is_completed()) {
            $status = 'complete';
            $msg = '您的评论已全部导入到评论啦';
        }
        $new_offset = $offset + PAGE_SIZE;
    } else {
        $status = 'fail';
        $msg = "您没有`manage_options`权限，无法操作评论数据";
    }

}
$response = compact('status', 'msg', 'exported_num', 'new_offset');
header('Content-type: text/javascript');
echo json_encode($response);

function pinglunla_is_completed() {
    global $wpdb;
    $max_comment_id = $wpdb->get_var( $wpdb->prepare("
            SELECT MAX(comment_ID)
            FROM $wpdb->comments
            WHERE comment_agent != 'pinglunla'
            AND comment_approved = 1
            "));
    $comment_id = get_option("pinglunla_exported_cid");
    if ($max_comment_id <= $comment_id) {
        return true;
    }
    return false;
}

function pinglunla_retrieve_comment($offset, $page_size) {
    global $wpdb;
    $comment_id = intval(get_option("pinglunla_exported_cid", 0));
    
    $comments = $wpdb->get_results( $wpdb->prepare("SELECT wpc.*, wpp.ID, wpp.post_name, wpp.post_title
        FROM $wpdb->comments as wpc, $wpdb->posts as wpp
        WHERE comment_ID > %d
        AND comment_approved = 1
        AND comment_agent != 'pinglunla'
        AND wpp.ID = wpc.comment_post_ID
        ORDER BY comment_ID ASC
        LIMIT ".$offset.','.$page_size,
        $comment_id));
    return $comments;
}

function pinglunla_wp2json($comments) {
    $total_rows = count($comments);
    $count = 0;
    $pll_json = "[";
    foreach ($comments as $comment) {
        $arr = array(
            "author"=>$comment->comment_author,
            "content"=>$comment->comment_content,
            "page_url"=>get_permalink($comment->ID),
            "post_name"=>$comment->post_name,
            "ctime"=>$comment->comment_date,
            "page_title"=>$comment->post_title,
            "author_email"=>$comment->comment_author_email,
            "author_homepage"=>$comment->comment_author_url
        );
        $count++;
        if ($count < $total_rows) {
            $w_str = json_encode($arr).",";
        } else {
            $w_str = json_encode($arr);
        }
        $pll_json .= $w_str;

    }
    $pll_json .= "]";
    return $pll_json;
}

?>
