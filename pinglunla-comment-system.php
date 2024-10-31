<?php
/*
Plugin Name: 评论啦
Plugin URI: http://www.pinglunla.com/
Description: 评论啦, 功能强大的社会化评论系统, 管理评论, 提升活跃度, 带来用户, 带来流量, 一起发现评论, 发现互联网。
Version: 0.2
Author: pinglunla
Author URI: http://www.pinglunla.com/
License: GPLv2
*/
require_once('pinglunla-utils.php');

if(!defined("PLL_URL")) {
    define("PLL_URL", "www.pinglunla.com");
}
    
function pinglunla_create_menu() {
	add_submenu_page(
         'edit-comments.php',
         '评论啦社会化评论系统',
         '评论啦',
         'moderate_comments',
         'pinglunla',
         'pinglunla_comments_manage_page'
     );
}
add_action( 'admin_menu', 'pinglunla_create_menu' , 10);

function pinglunla_menu_admin_head() {
?>
<script type="text/javascript">
jQuery(function($) {
    // fix menu
    var mc = $('#menu-comments');
    mc.find('a.wp-has-submenu').attr('href', 'edit-comments.php?page=pinglunla').end().find('.wp-submenu  li:has(a[href="edit-comments.php?page=pinglunla"])').prependTo(mc.find('.wp-submenu ul'));
});
</script>
<?php
}
add_action('admin_head', 'pinglunla_menu_admin_head');

$pinglunla_cc0 = get_option("pinglunla_cc0", "暂无评论");
$pinglunla_cc1 = get_option("pinglunla_cc1", "{count}条评论");

function pinglunla_comments_manage_page() {
    global $pinglunla_cc0, $pinglunla_cc1;
    
    $pinglunla_sid = get_option("pinglunla_sid", "");
    $pinglunla_seo = get_option("pinglunla_seo", 0);
    
    

    $wpurl = get_bloginfo("wpurl");
    if(empty($wpurl) || strlen($wpurl) < 8) {
        $host = $_SERVER["HTTP_HOST"];
    } else {
        $arr = parse_url($wpurl);
        $host = $arr['host'];
    }
    
    
	if (version_compare($wp_version, '2.6', '<')) {
?>
    <script type="text/javascript" src="http://s2.pinglun.la/md/js/jquery-1.7.2.min.js"></script>
<?php
	}
?>
<style type="text/css">
.pinglunla_clear {clear:both;}
.pinglunla_tabpage_item {clear:both;}
.pinglunla_tabpage_item img {display:inline;}
.pinglunla_tab {
width: 100%;
background: #FAFAFA;
height: 40px;
margin-bottom: 10px;
}

.pinglunla_tab .pinglunla_tab_wrapper {
margin: 0 auto;
width: 400px;
text-align: center;
padding-top: 10px;
height:30px;
}

.pinglunla_tab a {
text-decoration: none;
line-height: 150%;
margin-right: 5px;
color: #333;
padding: 7px;
*padding:3px;
*float:left;

}
.pinglunla_tab a:hover,  .pinglunla_tab a.selected  {
background: #333;
padding: 7px;
border-radius: 3px;
color: white;
*padding:3px;
}

.pinglunla_tabpages {width:1010px;margin:0 auto;}

.pinglunla_tabpages hr {border:none;
height:1px;
background-color:#eee;
color:#eee;
margin: 20px 0;
padding:0;
}
.pinglunla_tabpages .cancel_btn {color:#888;}

.pinglunla_tabpages h3 {
font-size: 14px;
font-weight: bold;
line-height: 22px;
margin:3px;
}
</style>
<script type="text/javascript">
jQuery(function() {
    // init menu btns
    jQuery(".pinglunla_tab a").click(function() {
        jQuery(".pinglunla_tabpage_item").hide();
        jQuery(".pinglunla_tab a").removeClass("selected");
        jQuery(this).addClass("selected");
        jQuery(".pinglunla_tabpages ."+jQuery(this).attr("dv")).show();
    }).first().click();
    

    var pinglunla_trigger_export = function (){
        jQuery('#pinglunla_reset_export_comments').hide();
        jQuery('#pinglunla_export_comments').unbind('click').click(function() {
            jQuery(this).css("color", "#ccc").attr("disabled", "disabled");
            jQuery('#pinglunla_export_div').html('<p class="status"></p>');
            jQuery('#pinglunla_export_div .status').removeClass('pinglunla-export-fail').addClass('pinglunla-exporting').html('处理中....');
            pinglunla_export_comments(0);
            return false;
        });
    };
    pinglunla_trigger_export();
    
    var pinglunla_export_comments = function(offset) {
        var export_div = jQuery('#pinglunla_export_div');
        var total = parseInt(export_div.attr('total') || '0');
        jQuery('#pll_wait').show();
        jQuery.get(
            '<?php echo pinglunla_plugins_url('export-comments.php', __FILE__); ?>?of='+offset,
            {},
            function(response) {
                switch (response.status) {
                    case 'partial':
                        var exported_num = parseInt(response.exported_num);
                        if (exported_num > 0) {
                            total += exported_num;
                            response.msg = "本次已导入<b>" + exported_num + "</b>条评论，累计导入<b>" + total + "</b>条评论。";
                            export_div.attr('total', total);
                            pinglunla_export_comments(response.new_offset);
                        }
                        break;
                    case 'complete':
                        jQuery('#pinglunla_reset_export_comments').show();
                        jQuery('#pll_wait').hide();
                        break;
                    case 'fail':
                        pinglunla_trigger_export();
                        jQuery('#pll_wait').hide();
                        break;
                }
                export_div.html(response.msg);
            },
            'json'
        );
    };
    
    jQuery('#pinglunla_reset_export_comments').unbind('click').click(function() {
        jQuery.get(
            '<?php echo pinglunla_plugins_url('export-comments.php', __FILE__); ?>?reset=1', {},
            function(response) {
                jQuery('#pinglunla_export_div').html(response.msg);
                pinglunla_trigger_import();
            },
            'json'
        );
    });

    var pinglunla_trigger_import = function (){
        jQuery('#pinglunla_import_comments').unbind('click').click(function() {
            jQuery(this).css("color", "#ccc").attr("disabled", "disabled");
            jQuery('#pinglunla_import_div').html('<p class="status"></p>');
            jQuery('#pinglunla_import_div .status').removeClass('pinglunla-export-fail').addClass('pinglunla-importing').html("处理中");
            pinglunla_import_comments(0);
            return false;
        });
    };

    pinglunla_trigger_import();

    var pinglunla_import_comments = function (offset) {
        var import_div = jQuery('#pinglunla_import_div');
        var total = parseInt(import_div.attr('total') || '0');
        jQuery('#pll_wait2').show();
        jQuery.get(
            '<?php echo pinglunla_plugins_url('import-comments.php', __FILE__); ?>?of='+offset, {},
            function(response) {
                switch (response.status) {
                    case 'partial':
                        var imported_num = parseInt(response.imported_num);
                        if (imported_num > 0) {
                            total += imported_num;
                            response.msg = "本次已导回<b>" + imported_num + "</b>条评论，累计导回<b>" + total + "</b>条评论。";
                            import_div.attr('total', total);
                            pinglunla_import_comments(response.new_offset);
                        }
                        break;
                    case 'complete':
                        jQuery('#pll_wait2').hide();
                        response.msg = "导回成功, 累计导回" + total + "条评论。";
                        break;
                    case 'fail':
                        jQuery('#pll_wait2').hide();
                        pinglunla_trigger_import();
                        break;
                }
                import_div.html(response.msg);
            },
            'json'
        );
    };

    jQuery("#pinglunla_export_json_comments").click(function() {
        jQuery(this).css("color", "#ccc").attr("disabled", "disabled");
        jQuery("#pinglunla_debug_frame").attr("src", "<?php echo pinglunla_plugins_url('export-json-comments.php', __FILE__).'?host='.$host ?>");
    });
    
    jQuery("#pinglunla_toggle_seo").click(function() {
        jQuery(this).css("color", "#ccc").attr("disabled", "disabled");
        jQuery("#pinglunla_debug_frame").attr("src", "<?php echo pinglunla_plugins_url('pinglunla-toggle-seo.php', __FILE__) ?>");
    });
    
    jQuery("#pinglunla_save_cc").click(function() {
        jQuery(this).css("color", "#ccc").attr("disabled", "disabled");
        jQuery("#pinglunla_debug_frame").attr("src", "<?php echo pinglunla_plugins_url('pinglunla-save-cc.php', __FILE__) ?>?cc0="+encodeURIComponent(jQuery("#pll_cc0").val())+"&cc1="+encodeURIComponent(jQuery("#pll_cc1").val()));
    });
});
</script>
<div class="pinglunla_tab">
<div class="pinglunla_tab_wrapper">
<a dv="pinglunla_comments_manage" href="javascript:;">评论管理</a>
<a id="pll_adv_options" dv="pinglunla_advanced_options" href="javascript:;">高级选项</a>
</div>
</div>

<div class="pinglunla_tabpages">
    <div class="pinglunla_tabpage_item pinglunla_comments_manage">
<?php
    echo '<iframe id="pinglunla_frame" frameBorder="0" width="100%" height="1500px" src="http://'.PLL_URL.'/webmaster?host='.$arr["host"].'"></iframe>';
?>
    </div>
	
    <div class="pinglunla_tabpage_item pinglunla_advanced_options">
        <h3>评论导入</h3>
        <p>将您的网站的原有评论导出并保存到评论啦 (注意：如果评论数量较多，同步的时间会比较长，请耐心等待 ^_^)</p>
        <button id="pinglunla_export_comments" class="pll_btn export_btn">一键导入</button>
        <button id="pinglunla_reset_export_comments" class="pll_btn export_btn" title="重新开始导入评论">重置导入</button>
        <br /><img style="display:none" id="pll_wait" src="<?php echo pinglunla_plugins_url('wait.gif', __FILE__); ?>" />
        <span id="pinglunla_export_div"></span>
        <iframe name="pinglunla_debug_frame" frameBorder="0" id="pinglunla_debug_frame" width="93%" height="50px"></iframe>

        <br />
        <hr />
        <h3>导回评论</h3>
        <p>将评论从评论啦导回到Wordpress数据库中</p>
        <button id="pinglunla_import_comments" class="pll_btn import_btn">一键导回</button>
        <br /><img style="display:none" id="pll_wait2" src="<?php echo pinglunla_plugins_url('wait.gif', __FILE__); ?>" />
        <span id="pinglunla_import_div"></span>

        <br />
		<hr />
        <h3>评论导出 json 文件</h3>
        <p>如果无法正常执行“一键导入”，可使用此功能导出评论数据，然后到评论啦官网站长管理页面导入。</p>
        <button id="pinglunla_export_json_comments" class="pll_btn export_btn">导出 json 文件</button>
        <br />
        <hr />
        <h3>SEO设置</h3>
        <p>开启SEO，方便Google、Baidu检索评论，速度会有些减缓。</p>
        <button id="pinglunla_toggle_seo" class="pll_btn export_btn">
        <?php
        if($pinglunla_seo == 0) {
            echo '开启评论SEO';
        } else {
            echo '关闭评论SEO';
        }
        ?>
        </button>
        <br />
        <hr />
        <h3>评论计数文本设置</h3>
        <p>无评论，有评论时显示的文本。</p>
        零条评论：<input id="pll_cc0" placeholder="无评论文本" value="<?php echo $pinglunla_cc0; ?>" /><br />
        多条评论：<input id="pll_cc1" placeholder="有评论文本" value="<?php echo $pinglunla_cc1; ?>" />&nbsp;(实际显示时，{count}会被真实数字代替)<br />
        <button id="pinglunla_save_cc" class="pll_btn export_btn">保存设置</button>
        <br />
    </div>
</div>
<?php
}

function pinglunla_can_replace() {
    global $post;

    if ( is_feed() )                       { return false; }
    if ( 'draft' == $post->post_status )   { return false; }

    return true;
}

function pinglunla_comments_template($value) {
    global $post;
    if ( !( 'open' == $post->comment_status ) ) {
        return;
    }
    return dirname(__FILE__) . '/comments.php';
}

function pinglunla_comments_number($count) {
    global $post;
    
    return $count;
}

function pinglunla_comments_text($comment_text) {
    global $post;
    global $pinglunla_cc0, $pinglunla_cc1, $pinglunla_cc2;

    if ( pinglunla_can_replace() ) {
        $permlink_of_page = get_permalink( $post->ID );
        return '<span class="pll_comment_count_tag" style="display:none" ct="'.$pinglunla_cc1.'" ct0="'.$pinglunla_cc0.'">'.$permlink_of_page.'</span>';
    } else {
        return $comment_text;
    }
}


function pinglunla_output_footer_comment_js() {
    if (!pinglunla_can_replace()) return;
    echo '<script type="text/javascript" src="http://s2.pinglun.la/md/cc.js" charset="utf-8"></script>';
}

add_filter('comments_template', 'pinglunla_comments_template', 0);
add_filter('comments_number', 'pinglunla_comments_text');
add_filter('get_comments_number', 'pinglunla_comments_number');
add_action('wp_footer', 'pinglunla_output_footer_comment_js');
?>