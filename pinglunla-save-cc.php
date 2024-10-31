<?php
require_once("../../../wp-includes/version.php");
if ( version_compare( $wp_version, '2.6', '>' ) ) {
	require_once("../../../wp-load.php");
} else {
	require_once("../../../wp-config.php");
	require_once("../../../wp-settings.php");
}

$err0 = '';
$err1 = '';

if(isset($_GET['cc0']) && '' != $_GET['cc0']) {
    update_option("pinglunla_cc0", $_GET['cc0']);
} else {
    $err0 = '零条评论文本设置失败';
}
if(isset($_GET['cc1']) && strpos($_GET['cc1'], '{count}') !== false) {
    update_option("pinglunla_cc1", $_GET['cc1']);
} else {
    $err1 = '多条评论文本设置失败';
}
if('' == $err0 && '' == $err1) {
    echo "<script>parent.document.getElementById('pinglunla_save_cc').innerText = '设置成功';</script>";
} else {
    echo "<script>parent.document.getElementById('pinglunla_save_cc').innerText = '设置失败! $err0 $err1';</script>";
}
?>
