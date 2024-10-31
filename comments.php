<?php
include_once(dirname(__FILE__)."/pinglunla-utils.php");

$pinglunla_seo = get_option("pinglunla_seo", 0);
$webpage_url = "http://".PLL_URL."/comment_box/plugin/get_comments?url=".urlencode(pinglunla_cur_page_url());

?>
<!-- Pll Begin -->
<div id="pinglunla_here">
<?php
if($pinglunla_seo == 1) {
echo file_get_contents($webpage_url);
}
?>
</div>
<a href="http://pinglun.la/" id="logo-pinglunla">评论啦</a><script type="text/javascript" src="http://s2.pinglun.la/md/pinglun.la.js" charset="utf-8"></script>
<!-- Pll End -->

