<?php
require 'htmLawed.php';

function wpnsw_mceplugin_add_button($buttons)
{
    array_push($buttons, "separator", "wpnsw_mceplugin");
    return $buttons;
}

function wpnsw_mceplugin_register($plugin_array)
{
    $url = plugins_url("editor_plugin.js", __FILE__) ;

    $plugin_array['wpnsw_mceplugin'] = $url;
    return $plugin_array;
}

/**
 * Close all open xhtml tags at the end of the string
 * Sources: http://stackoverflow.com/questions/3810230/close-open-html-tags-in-a-string
 * @param string $html
 * @param array  $tags_to_strip
 * @return string
 * @author Milian <mail@mili.de> and @alexn on SO, and Benoît Hubert
 */
function get_autoclosing_tags($html) {
    $autoclosing_tags_pattern = '#<(meta|img|br|hr|input)[^>]* ?/>#iU';
    $success = preg_match_all($autoclosing_tags_pattern, $html, $result);
    return $success ? $result[1] : [];
}

function get_html_without_autoclosing_tags($html) {
    $autoclosing_tags_pattern = '#<(meta|img|br|hr|input)[^>]* ?/>#iU';
    return preg_replace($autoclosing_tags_pattern, '', $html);
//    $autoclosing_tags = get_autoclosing_tags($html);
}

if( !function_exists( 'close_non_autoclosing_tags' ) ) {
    function close_non_autoclosing_tags($html) {
        // put all opened tags into an array
        // <([a-z]+)(?: .*)?(?<![/|/ ])>
        preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];

        // put all closed tags into an array
        preg_match_all('#</([a-z]+)>#iU', $html, $result);
        $closedtags = $result[1];

        $len_opened = count($openedtags);

        // all tags are closed
        if (count($closedtags) == $len_opened) {
            return $html;
        }
        $openedtags = array_reverse($openedtags);

        // close tags
        for ($i = 0 ; $i < $len_opened ; $i++) {
            if (!in_array($openedtags[$i], $closedtags) ) {
                    $html .= '</'.$openedtags[$i].'>';
            } else {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
        return $html;
    }

}

if( !function_exists( 'close_and_strip_tags' ) ) {
    function close_and_strip_tags($html_src, $tags_to_strip = array()) {
        $html = htmLawed($html_src);
        $autoclosing = get_autoclosing_tags($html);
        // put all opened tags into an array
        // <([a-z]+)(?: .*)?(?<![/|/ ])>
        preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = array_merge($autoclosing, $result[1]);
        $len_opened = count($openedtags);

        for ($i = 0 ; $i < $len_opened ; $i++) {
            if (in_array($openedtags[$i], $tags_to_strip)){
                if( in_array($openedtags[$i], $autoclosing) !== false ) {
                    $pattern = '/<' . $openedtags[$i] . '[^<]* \/>/';
                    $replace = '';
                }
                else {
                    $pattern = '/<' . $openedtags[$i] . '[^>]*>(.*)<\/' . $openedtags[$i] . '>/';
                    $replace = '${1}';
                }
                $html = preg_replace($pattern, $replace, $html);
            }
        }
        return $html;
    }
}

/** 
* word-sensitive substring function with html tags awareness 
* @param text The text to cut 
* @param len The maximum length of the cut string 
* @returns string 
**/ 


function wpnsw_localize() {
    load_plugin_textdomain( 'wpnsw', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/* Scripts JS */
function wpnsw_load_scripts() {
    if( !is_admin() ) {
        $append = WP_DEBUG === true ? '?ts=' . time() : '';
        wp_enqueue_script( 'wpsps-js', plugins_url( "assets/wp-simple-post-slider.min.js{$append}", __FILE__), array( 'jquery' ) );
    }

}

// CSS Tableau
function wpnsw_load_styles() {
    $current_user = wp_get_current_user();
    $append = WP_DEBUG === true ? '?ts=' . time() : '';
    if (!is_admin() ) {
        wp_enqueue_style('news_slider_style', plugins_url( "assets/wp-simple-post-slider.min.css{$append}", __FILE__) );
    }
}


// Word- and HTML-Tag-sensitive substring
/*function wts_substr( $text, $max_len = 230 ) { 
    // Do something only if text is too long
    if( (strlen($text) > $max_len) ) { 
        // find out position of last white space in specified range (0, $max_len)
        $white_space_pos = strpos( $text, " ", $max_len ) - 1; 
//      $tag_opener_pos  = strrpos( $text, "<", $white_space_pos + 1 );
//      $tag_closer_pos  = strpos(  $text, ">", $tag_opener_pos );
        if( $white_space_pos > 0 ) 
            $text = substr($text, 0, $white_space_pos + 1 );
    } 
    return $text; 
} */


// Replace some tags
function wpnsw_replace( $string ) {
    $tags_to_replace = array(
        'h1'    => array( 'h1', 'style="font-size:medium; text-align:center;"' )
    );
    
    foreach( $tags_to_replace as $tag => $replacement ) {
        $replacement_tag   = $replacement[0];
        $replacement_attrs = $replacement[1];
        $string = str_replace( "<$tag", "<$replacement_tag $replacement_attrs", $string );
        //$string = preg_replace( "/<$tag/", "<$replacement_tag $replacement_attrs", $string );
        $string = str_replace( "</$tag", "</$replacement_tag", $string );
    }
    
    return $string;
}

// Word- and Tag-Sensitive substring
if( !function_exists( 'wts_substr' ) ) {
function wts_substr( $text, $max_len = 230 ) { 
    // Do something only if text is too long
    if( ( strlen( $text ) > $max_len ) ) { 
        // find out position of last white space in specified range (0, $max_len)
        $white_space_pos = strpos( $text, " ", $max_len ) - 1; 
        if( $white_space_pos > 0 ) 
            $cut_text = substr($text, 0, $white_space_pos + 1 );
        $tag_opener_pos  = strrpos( $cut_text, "<" );
        if( $tag_opener_pos > 0 ) {
            $tag_closer_pos  = strpos( $text, "</", $tag_opener_pos );
            if( $tag_closer_pos ) {
                $cut_pos = max( $tag_closer_pos, $white_space_pos + 1 );
                $cut_text = substr($text, 0, $cut_pos );
            }
        }
        if( isset( $cut_text ) ) return $cut_text;
    } 
    return $text; 
}
}

// concatenate content and thumb, and if no thumb get first thumb from gallery
function wpnsw_shiba_filter( $post_id, $content, $thumbnail, $show_thumbs ) {

    global $wpdb;
    /* Preg match setup */
    $matches = array();
    $match = "";
    $id_attrs = array();
    $ids = array();
    $pattern_to_replace = array();
    $replacement_a = array("[Galerie photos]");

    // We enter this condition if we find a gallery
    if( preg_match( '/\[gallery(.)*]/', $content, $matches ) == 1 ) {

            $match = $matches[0];
            $pattern_to_replace[] = '/\[gallery(.)*]/'; // replace the [gallery attr1="xx" attr2="y"] whatever its content
            // An ID was specified for this gallery, then let's retrieve it
            if( preg_match( '/id="(\d)+"/', $match, $id_attrs ) == 1 ) {
                    $id_string = $id_attrs[0];
                    if( preg_match( '/(\d)+/', $id_string, $ids ) == 1) $post_id = $ids[0]; // replace $post_id parameter with this one
            }
            $posts = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_parent = '$post_id' AND post_type='attachment' ORDER BY ID ASC" );
            $post = $posts[0];
            $thumb_url = wp_get_attachment_thumb_url( $post->ID );
            $meta = wp_get_attachment_metadata( $post->ID, true );
            $thumb_data = $meta['sizes']['thumbnail'];
            if( empty( $thumbnail ) ) $thumbnail = array( $thumb_url, $thumb_data['width'], $thumb_data['height'] );
    }

    $thumb_str = "";
    if( !empty( $thumbnail ) && $show_thumbs ) {
            $thumb_str = "<div class='sliderthumb'><img class='my-alignleft' alt='thumb for ".basename($thumbnail[0])."' src='".$thumbnail[0]."' /></div>";
            $ie7marginleft = 2 + $thumbnail[1];
            $ie7width = 284 - $thumbnail[1];
            //$thumb_str .= "<!--[if lt IE 7]><div class='slidercontent' style='margin-left: {$ie7marginleft}px; width: {$ie7width}px;'><![endif]-->\n";
    }

    return array( preg_replace($pattern_to_replace, $replacement_a, $content ), $thumb_str ); 
}


/* Post html excerpt, cut post content after 245 characters or <!--cuthere--> tag */
if( !function_exists( 'post_html_excerpt' ) ) {
function post_html_excerpt( $string ) {
    $morepos = strpos( $string, '<!--cuthere-->' );
    $len = ( $morepos ? $morepos + 13 : 245 );
    $string = wpnsw_replace( wts_substr( $string, $len ) );
    return ( close_and_strip_tags( $string, ['img'] ) . '&nbsp;[...]' );
}   
}
/* Those functions should be put inside the widget class */
function get_sanitized_post_number( $number ) {
    if ( empty( $number ) )
        $number = 10;
    else if ( $number < 1 )
        $number = 1;
    else if ( $number > 12 )
        $number = 12;
    return $number;
}


function convert_title_link( $title ) {
    $page_posts_link = get_permalink( get_option( 'page_for_posts' ) );
    $link_open = "<a href='$page_posts_link'>";
    $link_close = "</a>";
    return $link_open . $title . $link_close;
}

function wp_widget_init() {
    register_widget('WP_Widget_Simple_Post_Slider');
}