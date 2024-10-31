<?php
function pinglunla_cur_page_url() 
{
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") 
    {
        $pageURL .= "s";
    }
    $pageURL .= "://";

    if ($_SERVER["SERVER_PORT"] != "80") 
    {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } 
    else 
    {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

function pinglunla_plugins_url_s($path) {
// WP < 2.6
	if ( !function_exists('plugins_url') ) {
		return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__)).'/'. $path;
	}
	return plugins_url(plugin_basename(dirname(__FILE__)));
}

function pinglunla_plugins_url($path = '', $plugin = '')
{
    if (function_exists('plugins_url'))
        return plugins_url($path, $plugin);
    else
        return pinglunla_plugins_url_s($path);
}

function pinglunla_post_to($url, $post_data) {
    if(function_exists('curl_init')) {
        if (!function_exists('curl_setopt_array')) {
            function curl_setopt_array(&$ch, $curl_options)
            {
                foreach ($curl_options as $option => $value) {
                    if (!curl_setopt($ch, $option, $value)) {
                        return false;
                    }
                }
                return true;
            }
        }
        //use curl
        return pinglunla_post_to_curl($url, $post_data);
    } else if(ini_get('allow_url_fopen') && function_exists('stream_get_contents')) {
        //use get_file_contents
        return pinglunla_post_to_fgc($url, $post_data);
    } else {
        return -1; // No curl, no file_get_contents, sorry!!!
    }
}

/* post files with curl */
function pinglunla_post_to_curl($url, $post_data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if(!is_null($post_data)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

/* post files with file_get_contents */
function pinglunla_post_to_fgc($url, $post_data)
{
    if(is_null($post_data)) {
        return file_get_contents($url);
    } else {
        $opts = array("http" =>
            array(
                'method' => "POST",
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' =>  http_build_query( $post_data )
                )
            );
        $context = stream_context_create($opts);
        return file_get_contents($url, false, $context);
    }
}
?>
