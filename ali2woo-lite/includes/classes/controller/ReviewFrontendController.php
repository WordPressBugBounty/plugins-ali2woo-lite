<?php

/**
 * Description of ReviewFrontendController
 * 
 * @author MA_GROUP
 * 
 * @autoload: init
 */

namespace AliNext_Lite;;

class ReviewFrontendController
{

    function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_filter('comment_text', [$this, 'comment_text'], 10, 2);
        add_filter('get_avatar_url', array($this, 'get_avatar_url'), 1, 3);
    }

    function assets(): void
    {
        if (function_exists('is_product') && is_product()) {
            wp_enqueue_style(
                'a2wl-review--frontend-style',
                A2WL()->plugin_url() . '/assets/css/review/frontend_style.css',
                [], A2WL()->version
            );

            wp_enqueue_script(
                'a2wl-fancybox',
                A2WL()->plugin_url() . '/assets/js/fancybox/fancybox.umd.js',
                [], A2WL()->version, true
            );

            wp_enqueue_style(
                'a2wl-fancybox-style',
                A2WL()->plugin_url() . '/assets/css/fancybox/fancybox.css',
                [], A2WL()->version
            );

            wp_enqueue_script(
                'a2wl-review-frontend-script',
                A2WL()->plugin_url() . '/assets/js/review/frontend_script.js',
                [], A2WL()->version, true
            );
        }
    }

    function comment_text($comment_text, $comment = null) {
        if (is_null($comment)) {
            return $comment_text;
        }

        if (get_setting('review_show_image_list')) {
            $thumb_width = get_setting('review_thumb_width') . "px";
            $image_list = Review::get_comment_photos($comment->comment_ID);

            if ($image_list) {
                $comment_text .= "<div class='a2wl_review_images'>";
                foreach ($image_list as $img) {
                    if (is_array($img)) {
                        $comment_text .= "<a class='fancybox' data-fancybox-group='group{$comment->comment_ID}' href='{$img['image']}'><img width='{$thumb_width}' src='{$img['thumb']}'/></a>";
                    } else{
                        $comment_text .= "<a class='fancybox' data-fancybox-group='group{$comment->comment_ID}' href='{$img}'><img width='{$thumb_width}' src='{$img}'/></a>";
                    }
                }
                $comment_text .= "</div>";
            }
        }

        return $comment_text;
    }

    function get_avatar_url($url, $id_or_email, $args) {

        if ($id_or_email instanceof \WP_Comment) {
            $comment = $id_or_email;
            $comment_id = $comment->comment_ID;
            $image_path = get_comment_meta($comment_id, 'a2wl_avatar', true);
            
            $no_avatar_image = get_setting('review_noavatar_photo', A2WL()->plugin_url() . '/assets/img/noavatar.png');
            
            if (empty($image_path) || is_null($image_path)) {
                $image_path = $no_avatar_image;
            }else{
                
                if (is_numeric($image_path)){
                    //todo: maybe check url scheme too?
                    $photo_id = $image_path;
                    $image_path = wp_get_attachment_image_src($photo_id, 'thumbnail');
                    $image_path = $image_path ? $image_path[0] : $no_avatar_image;
                } else {
                    $image_path = set_url_scheme($image_path, 'https');    
                }
            
            }

            return $image_path;
        } 
        
        return $url;
    }

}
