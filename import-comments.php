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

$response = pll_get_comments($_GET['of']);
header('Content-type: text/javascript');
echo json_encode($response);

function pll_get_comments($offset) {
    $page_size = 500;
    $post_data = array(
        "hs"        => $_SERVER["HTTP_HOST"],
        "of"   => $offset,
        "ps" => $page_size,
        "pg" => 1
    );

    $response = pinglunla_post_to('http://'.PLL_URL.'/webmaster/export_comments?'.http_build_query($post_data), NULL);
    try {
        $comments = json_decode($response);
    } catch(Exception $e) {
        $status = 'fail';
        $msg = "网络错误，请稍后再试";
    }
    $new_offset = $offset + $page_size;
    
    if ($comments != null) {
        pll_sync_comments($comments);
    }

    $imported_num = count($comments);
    if ($imported_num > 0) {
        $status = "partial";   
    } else {
        $status = "complete";
    }
    return compact('status', 'imported_num', 'msg', 'new_offset');
}

function pll_sync_comments($comments) {
    foreach ($comments as $comment) {
        $commentdata = array(
            "comment_post_ID" => url_to_postid($comment->page_url),
            "comment_author"  => $comment->author,
            "comment_author_email" => $comment->author_email,
            "comment_author_url"   => $comment->author_homepage,
            "comment_content"      => $comment->content,
            "comment_agent"        => "pinglunla",
            "comment_date"         => $comment->ctime
        );
        wp_insert_comment($commentdata);
    }
}
?>
