<?php

/**
 * Description of Attachment
 *
 * @author Ali2Woo Team
 */

// phpcs:ignoreFile WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

namespace AliNext_Lite;;

use DOMDocument;
use Exception;
use function WP_Error;

class Attachment
{

    public const KEY_ATTACHED_FILE = '_wp_a2w_attached_file';

    private $utils;
    private $use_external_image_urls = false;

    public function __construct($use_external = 'cfg')
    {
        $this->utils = new Utils();
        if ('external' === $use_external || 'local' === $use_external) {
            $this->use_external_image_urls = ('external' === $use_external);
        } else {
            $this->use_external_image_urls = get_setting('use_external_image_urls');
        }
    }

    public function create_attachment($post_id, $image_path, $params = [])
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $title = $params['title'] ?? "";
        $alt = $params['alt'] ?? "";
        $check_duplicate = !isset($params['check_duplicate']) || $params['check_duplicate'];
        $edit_images = $params['edit_images'] ?? [];
        $use_external_image_urls = $params['use_external_image_urls'] ?? $this->use_external_image_urls;

        $image_path = Utils::clear_image_url($image_path);
        // remove _640x640.jpg from image url filename.jpg_640x640.jpg
        //$image_path = preg_replace("/(.+)(.jpg)(_[0-9]+x[0-9]+.jpg)/", "$1$2", $image_path);
        // $image_path = preg_replace("/(.+?)(.jpg|.jpeg)(.*)/", "$1$2", $image_path);
        $image_id = Utils::buildImageIdFromPath($image_path);
        $image_name = preg_replace('/\.[^.]+$/', '', basename($image_path));

        if (!empty($edit_images[$image_id])) {
            $attachment_id = $edit_images[$image_id]['attachment_id'];

            wp_update_post([
                'ID' => $attachment_id,
                'post_title' => empty($title) ? $image_name : $title,
                'post_excerpt' => empty($title) ? $image_name : $title,
                'meta_input' => ['_a2w_external_image_url' => $edit_images[$image_id]['external_image_url']],
            ]);

            if (!empty($alt)) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
            }

            return $attachment_id;
        }

        if ($check_duplicate) {
            if ($use_external_image_urls) {
                $query = "SELECT ID FROM $wpdb->posts p INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id) " .
                         "WHERE p.post_parent=%d AND pm.meta_key='_a2w_external_image_url' AND pm.meta_value=%s " .
                         "LIMIT 1";
                $old_attachment_id = $wpdb->get_var(
                    $wpdb->prepare($query, $post_id, $image_path)
                );
            } else {
                $query = "SELECT ID FROM $wpdb->posts p INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id) " .
                        "LEFT JOIN $wpdb->postmeta pm1 ON (p.ID = pm1.post_id AND pm1.meta_key=%s AND pm1.meta_value='1') " .
                        "WHERE p.post_parent=%d AND pm.meta_key='_a2w_external_image_url' " .
                        "AND pm.meta_value=%s AND pm1.meta_id is null LIMIT 1";

                $old_attachment_id = $wpdb->get_var(
                    $wpdb->prepare($query, self::KEY_ATTACHED_FILE, $post_id, $image_path)
                );
            }

            if ($old_attachment_id) {
                return $old_attachment_id;
            }
        }

        if ($use_external_image_urls) {
            // attach image as remote url
            if (empty($post_id) || empty($image_path)) {
                return false;
            }

            // remove _640x640.jpg from image url filename.jpg_640x640.jpg
            //$image_path = preg_replace("/(.+)(.jpg)(_[0-9]+x[0-9]+.jpg)/", "$1$2", $image_path);
            //$image_path = preg_replace("/(.+?)(.jpg|.jpeg)(.*)/", "$1$2", $image_path);

            $wp_filetype = wp_check_filetype(basename($image_path), null);

            //$image_name = preg_replace('/\.[^.]+$/', '', basename($image_path));
            $image_name = sanitize_file_name($image_name);
            $image_name = preg_replace("/[^a-zA-Z0-9-]/", "", $image_name);
            $image_name = substr($image_name, 0, 200);

            $attachment = array(
                'guid' => $image_path,
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => empty($title) ? $image_name : $title,
                'post_excerpt' => empty($title) ? $image_name : $title,
                'post_content' => '',
                'post_status' => 'inherit',
            );
            $attach_id = wp_insert_attachment($attachment, $image_path, $post_id);

            if (!$attach_id) {
                return false;
            }

            $this->set_attachment_metadata($attach_id, $image_path);

            update_post_meta($attach_id, '_a2w_external_image_url', $image_path);

            if (!empty($alt)) {
                update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
            }

            return $attach_id;
        } else {
            // attach image as upload
            $image = $this->download_url($image_path);
            if ($image) {
                $file_array = [
                    'name' => basename($image),
                    'size' => filesize($image),
                    'tmp_name' => $image,
                ];

                if (isset($params['inner_post_id'])) {
                    $file_array['inner_post_id'] = $params['inner_post_id'];
                }

                if (isset($params['inner_attach_type'])) {
                    $file_array['inner_attach_type'] = $params['inner_attach_type'];
                }

                $attach_id = media_handle_sideload($file_array, $post_id, $title);

                if ($attach_id) {
                    update_post_meta($attach_id, '_a2w_external_image_url', $image_path);
                    if (!empty($title)) {
                        wp_update_post([
                            'ID' => $attach_id,
                            'post_excerpt' => $title
                        ]);
                    }
                    if (!empty($alt)) {
                        update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
                    }
                }

                return $attach_id;
            } else {
                return false;
            }
        }
    }

    public function create_attachment_from_data($post_id, $file_data, $params = array())
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $title = isset($params['title']) ? $params['title'] : "";
        $alt = isset($params['alt']) ? $params['alt'] : "";

        $matches = array();
        preg_match('/data:(.*?);/', $file_data, $matches);
        $ext = Attachment::mime2ext($matches[1]);
        if (!$ext) {
            return WP_Error('upload_error', "Can't find file ext");
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $image_data = file_get_contents($file_data);

        $wp_upload_dir = wp_upload_dir();
        $image_filename = wp_unique_filename($wp_upload_dir['path'], wp_rand() . "." . $ext);
        $image = $wp_upload_dir['path'] . '/' . $image_filename;

        file_put_contents($image, $image_data);

        if (!file_exists($image)) {
            return WP_Error('upload_error', 'File not created');
        }

        $file_array = array('name' => basename($image), 'size' => filesize($image), 'tmp_name' => $image);

        if (isset($params['inner_post_id'])) {
            $file_array['inner_post_id'] = $params['inner_post_id'];
        }

        if (isset($params['inner_attach_type'])) {
            $file_array['inner_attach_type'] = $params['inner_attach_type'];
        }

        $attach_id = media_handle_sideload($file_array, $post_id, $title);

        if ($attach_id) {
            if (!empty($title)) {
                wp_update_post(array('ID' => $attach_id, 'post_excerpt' => $title));
            }
            if (!empty($alt)) {
                update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
            }
        }

        return $attach_id;
    }

    public function create_attachment_from_file($post_id, $file, $params = [])
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file_temp = $file['tmp_name'];
        $file_mime = $file['type'];

        $title = $params['title'] ?? "";
        $alt = $params['alt'] ?? "";

        $ext = Attachment::mime2ext($file_mime);
        if (!$ext) {
            return WP_Error('upload_error', "Can't find file ext");
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $image_data = file_get_contents($file_temp);

        $wp_upload_dir = wp_upload_dir();
        $image_filename = wp_unique_filename($wp_upload_dir['path'], wp_rand() . "." . $ext);
        $image = $wp_upload_dir['path'] . '/' . $image_filename;

        file_put_contents($image, $image_data);

        if (!file_exists($image)) {
            return WP_Error('upload_error', 'File not created');
        }

        $file_array = [
            'name' => basename($image),
            'size' => filesize($image),
            'tmp_name' => $image
        ];

        if (isset($params['inner_post_id'])) {
            $file_array['inner_post_id'] = $params['inner_post_id'];
        }

        if (isset($params['inner_attach_type'])) {
            $file_array['inner_attach_type'] = $params['inner_attach_type'];
        }

        $attach_id = media_handle_sideload($file_array, $post_id, $title);

        if ($attach_id) {
            if (!empty($title)) {
                wp_update_post([
                    'ID' => $attach_id,
                    'post_excerpt' => $title
                ]);
            }
            if (!empty($alt)) {
                update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
            }
        }

        return $attach_id;
    }

    private function download_url($url)
    {
        $wp_upload_dir = wp_upload_dir();
        $parsed_url = wp_parse_url($url);
        $pathinfo = pathinfo($parsed_url['path']);
        if (!$pathinfo || !isset($pathinfo['extension'])) {
            return false;
        }
        $dest_filename = wp_unique_filename($wp_upload_dir['path'], wp_rand() . "." . $pathinfo['extension']);

        $dest_path = $wp_upload_dir['path'] . '/' . $dest_filename;

        $response = a2wl_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        } elseif (!in_array($response['response']['code'], array(404, 403))) {
            file_put_contents($dest_path, $response['body']);
        }

        if (!file_exists($dest_path)) {
            return false;
        } else {
            return $dest_path;
        }
    }

    private function set_attachment_metadata($attach_id, $image_url)
    {
        update_post_meta($attach_id, self::KEY_ATTACHED_FILE, 1);

        $pi = pathinfo($image_url);

        $image_sizes = array();
        if (!empty($pi['extension'])) {
            $image_sizes['thumbnail'] = array('url' => $image_url . '_50x50.' . $pi['extension'], 'width' => 50, 'height' => 50);
            $image_sizes['small1'] = array('url' => $image_url . '_100x100.' . $pi['extension'], 'width' => 100, 'height' => 100);
            $image_sizes['small2'] = array('url' => $image_url . '_200x200.' . $pi['extension'], 'width' => 200, 'height' => 200);
            $image_sizes['medium'] = array('url' => $image_url . '_350x350.' . $pi['extension'], 'width' => 350, 'height' => 350);
            $image_sizes['medium_large'] = array('url' => $image_url . '_640x640.' . $pi['extension'], 'width' => 640, 'height' => 640);
        }

        $image_sizes['large'] = array('url' => $image_url, 'width' => 800, 'height' => 800);

        $attach_data = array(
            'file' => 0,
            'width' => 0,
            'height' => 0,
            'sizes' => array(),
            'image_meta' => array(
                'aperture' => '0',
                'credit' => '',
                'camera' => '',
                'caption' => '',
                'created_timestamp' => '0',
                'copyright' => '',
                'focal_length' => '0',
                'iso' => '0',
                'shutter_speed' => '0',
                'title' => '',
                'orientation' => '0',
                'keywords' => array(),
            ),
        );

        $attach_data = array_replace_recursive($attach_data, array(
            'file' => $image_sizes['large']['url'],
            'width' => $image_sizes['large']['width'],
            'height' => $image_sizes['large']['height'],
        ));

        $wp_sizes = $this->utils->get_image_sizes();
        foreach ($wp_sizes as $size => $props) {
            $found_size = $this->_choose_image_size_from_aliexpress($props, $image_sizes);

            if (!empty($found_size)) {
                $wp_filetype = wp_check_filetype(basename($found_size['url']), null);
                $attach_data['sizes']["$size"] = array(
                    'file' => basename($found_size['url']),
                    'width' => $found_size['width'],
                    'height' => $found_size['height'],
                    'mime-type' => $wp_filetype['type'],
                );
            }
        }

        wp_update_attachment_metadata($attach_id, $attach_data);
    }

    private function _choose_image_size_from_aliexpress($size, $image_sizes = array())
    {
        if (empty($image_sizes)) {
            return false;
        }

        $min_size = $max_size = false;
        foreach ($image_sizes as $props) {
            if ((int) $size['width'] == (int) $props['width']) {
                return $props;
            }

            if (intval($size['width']) < intval($props['width']) && (!$min_size || intval($min_size['width']) > intval($props['width']))) {
                $min_size = $props;
            }

            if (!$max_size || (intval($max_size['width']) < intval($props['width']))) {
                $max_size = $props;
            }
        }

        return !$min_size ? $max_size : $min_size;
    }

    public static function find_products_with_external_images()
    {
        global $wpdb;

        $result_ids = [];
        $query = "SELECT DISTINCT IF(p.post_parent = 0, p.id, p.post_parent) AS id FROM $wpdb->posts p, " .
                 "(SELECT DISTINCT p1.post_parent AS id FROM $wpdb->posts p1 " .
                     "LEFT JOIN $wpdb->postmeta pm1 ON (p1.id = pm1.post_id) " .
                    "WHERE p1.post_type = 'attachment' AND pm1.meta_key=%s " .
                    "AND pm1.meta_value='1') pp " .
                "WHERE p.ID = pp.id";
        $tmp_product_ids = $wpdb->get_results(
            $wpdb->prepare($query, self::KEY_ATTACHED_FILE),
            ARRAY_N
        );
        foreach ($tmp_product_ids as $row) {
            $result_ids[] = $row[0];
        }
        return $result_ids;
    }

    public static function calc_total_external_images()
    {
        global $wpdb;

        $query = "SELECT count(id) FROM (SELECT DISTINCT p1.id AS id FROM $wpdb->posts p1 " .
                 "LEFT JOIN $wpdb->postmeta pm1 ON (p1.id = pm1.post_id) WHERE p1.post_type = 'attachment' " .
                 "AND pm1.meta_key=%s AND pm1.meta_value='1') AS pp";

        $cnt = $wpdb->get_var($wpdb->prepare($query, self::KEY_ATTACHED_FILE));

        $query = "SELECT count(ID) FROM $wpdb->posts WHERE post_type = 'product' AND post_content LIKE '%.alicdn.com%'";
        $cnt += $wpdb->get_var($query);

        return $cnt;
    }

    public static function find_external_images(int $page_size = 1000, $post_id = false): array
    {
        global $wpdb;

        $result_ids = [];
        //1. find external images in the attachments
        $post_filter = $post_id && intval($post_id) > 0 ? " AND p1.post_parent=" . intval($post_id) . " " : "";
        $sql = "SELECT DISTINCT p1.id AS id FROM $wpdb->posts p1 " .
            "LEFT JOIN $wpdb->postmeta pm1 ON(p1.id = pm1.post_id) " .
            "WHERE p1.post_type = 'attachment' AND pm1.meta_key=%s AND pm1.meta_value='1' " .
            $post_filter . " LIMIT %d";

        $tmp_product_ids = $wpdb->get_results(
            $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
                self::KEY_ATTACHED_FILE,
                $page_size
            ),
            ARRAY_N
        );

        foreach ($tmp_product_ids as $row) {
            $result_ids[] = $row[0];
        }

        $posts_limit = $page_size - count($result_ids);
        if ($posts_limit > 0) {
            //2. find products with external images in the product description
            $post_filter = $post_id && intval($post_id) > 0 ? " AND ID=" . intval($post_id) . " " : "";
            $sql = "SELECT ID FROM $wpdb->posts " .
            "WHERE post_type = 'product' AND post_content LIKE %s $post_filter" . " " .
            "LIMIT %d";
            $tmp_product_ids = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql,
                    '%' . 'alicdn.com' . '%',
                    $posts_limit
                ),
                ARRAY_N
            );

            foreach ($tmp_product_ids as $row) {
                $result_ids[] = $row[0];
            }
        }

        return $result_ids;
    }

    /**
     * @throws Exception
     */
    public function load_external_image($post_id): void
    {
        global $wpdb;

        if ($post_id) {
            $post_id = intval($post_id);
            $post = get_post($post_id);
            if ($post->post_type === 'attachment') {
                //load external images for attachments
                $tmp = get_post_meta($post_id, self::KEY_ATTACHED_FILE, true);
                if ($tmp && intval($tmp) === 1) {

                    $new_image_id = $this->create_attachment($post->post_parent, $post->guid, array('inner_post_id' => $post_id, 'title' => $post->post_title, 'alt' => $post->post_title));
                    if ($new_image_id) {
                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE $wpdb->postmeta SET meta_value = %s " .
                                    "WHERE meta_key = '_thumbnail_id' AND meta_value = %d",
                                $new_image_id,
                                $post_id
                            )
                        );

                        $res = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT meta_id, post_id, meta_key, meta_value FROM $wpdb->postmeta " .
                                                 "WHERE meta_key=%s",
                                '_product_image_gallery'
                            ),
                            ARRAY_A);
                        foreach ($res as $row) {
                            $tmp_id_list = explode(',', $row['meta_value']);
                            $tmp_id_list_res = array();
                            foreach ($tmp_id_list as $id_str) {
                                if (intval($id_str) > 0) {
                                    if (intval($id_str) === $post_id) {
                                        $tmp_id_list_res[] = $new_image_id;
                                    } else {
                                        $tmp_id_list_res[] = intval($id_str);
                                    }
                                }
                            }

                            $wpdb->query(
                                $wpdb->prepare(
                                    "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %s",
                                    implode(',', $tmp_id_list_res),
                                    $row['meta_id']
                                )
                            );
                        }

                        // update swatch
                        $sql = "SELECT meta_id, post_id, meta_key, meta_value FROM $wpdb->postmeta " .
                               "WHERE meta_key='_swatch_type_options' AND meta_value like '%\"" . $post_id . "\"%'";

                        $res = $wpdb->get_results(
                            $wpdb->prepare(
                                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                                $sql
                            ),
                            ARRAY_A
                        );
                        foreach ($res as $row) {
                            $swatch_type_options = unserialize($row['meta_value']);
                            foreach ($swatch_type_options as $k => $v) {
                                foreach ($v['attributes'] as $ak => $a) {
                                    if (isset($a['image']) && $a['image'] == $post_id) {
                                        $swatch_type_options[$k]['attributes'][$ak]['image'] = $new_image_id;
                                    }
                                }
                            }
                            delete_post_meta($row['post_id'], '_swatch_type_options');
                            update_post_meta($row['post_id'], "_swatch_type_options", $swatch_type_options);
                        }

                        Utils::delete_attachment($post_id, true);
                        $wpdb->query($wpdb->prepare(
                            "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d",
                            $post_id
                        ));
                        wp_delete_post($post_id, true);
                    }
                }
            } else if ($post->post_content && class_exists('DOMDocument')) {
                //load external images from the product description
                if (function_exists('libxml_use_internal_errors')) {
                    libxml_use_internal_errors(true);
                }
                $dom = new DOMDocument();
                @$dom->loadHTML($post->post_content);
                $dom->formatOutput = true;

                $elements = $dom->getElementsByTagName('img');
                $replace_map = [];
                for ($i = $elements->length; --$i >= 0;) {
                    $e = $elements->item($i);
                    $old_url = $e->getAttribute('src');

                    if (str_contains($old_url, '.alicdn.com')) {
                        $attachment_id = $this->create_attachment(
                            $post_id,
                            $e->getAttribute('src'),
                            ['inner_post_id' => $post_id, 'title' => $post->post_title, 'alt' => $post->post_title]
                        );
                        $new_url = wp_get_attachment_url($attachment_id);
                        $replace_map[$old_url] = $new_url;
                    }
                }
                $post->post_content = str_replace(
                    array_keys($replace_map),
                    array_values($replace_map),
                    $post->post_content
                );
                wp_update_post(['ID' => $post_id, 'post_content' => $post->post_content]);
            }
        } else {
            throw new Exception("load_external_image: waiting for ID...");
        }
    }

    public static function mime2ext($mime)
    {
        $all_mimes = '{"png":["image\/png","image\/x-png"],"bmp":["image\/bmp","image\/x-bmp","image\/x-bitmap","image\/x-xbitmap","image\/x-win-bitmap","image\/x-windows-bmp","image\/ms-bmp","image\/x-ms-bmp","application\/bmp","application\/x-bmp","application\/x-win-bitmap"],"gif":["image\/gif"],"jpeg":["image\/jpeg","image\/pjpeg"],"xspf":["application\/xspf+xml"],"vlc":["application\/videolan"],"wmv":["video\/x-ms-wmv","video\/x-ms-asf"],"au":["audio\/x-au"],"ac3":["audio\/ac3"],"flac":["audio\/x-flac"],"ogg":["audio\/ogg","video\/ogg","application\/ogg"],"kmz":["application\/vnd.google-earth.kmz"],"kml":["application\/vnd.google-earth.kml+xml"],"rtx":["text\/richtext"],"rtf":["text\/rtf"],"jar":["application\/java-archive","application\/x-java-application","application\/x-jar"],"zip":["application\/x-zip","application\/zip","application\/x-zip-compressed","application\/s-compressed","multipart\/x-zip"],"7zip":["application\/x-compressed"],"xml":["application\/xml","text\/xml"],"svg":["image\/svg+xml"],"3g2":["video\/3gpp2"],"3gp":["video\/3gp","video\/3gpp"],"mp4":["video\/mp4"],"m4a":["audio\/x-m4a"],"f4v":["video\/x-f4v"],"flv":["video\/x-flv"],"webm":["video\/webm"],"aac":["audio\/x-acc"],"m4u":["application\/vnd.mpegurl"],"pdf":["application\/pdf","application\/octet-stream"],"pptx":["application\/vnd.openxmlformats-officedocument.presentationml.presentation"],"ppt":["application\/powerpoint","application\/vnd.ms-powerpoint","application\/vnd.ms-office","application\/msword"],"docx":["application\/vnd.openxmlformats-officedocument.wordprocessingml.document"],"xlsx":["application\/vnd.openxmlformats-officedocument.spreadsheetml.sheet","application\/vnd.ms-excel"],"xl":["application\/excel"],"xls":["application\/msexcel","application\/x-msexcel","application\/x-ms-excel","application\/x-excel","application\/x-dos_ms_excel","application\/xls","application\/x-xls"],"xsl":["text\/xsl"],"mpeg":["video\/mpeg"],"mov":["video\/quicktime"],"avi":["video\/x-msvideo","video\/msvideo","video\/avi","application\/x-troff-msvideo"],"movie":["video\/x-sgi-movie"],"log":["text\/x-log"],"txt":["text\/plain"],"css":["text\/css"],"html":["text\/html"],"wav":["audio\/x-wav","audio\/wave","audio\/wav"],"xhtml":["application\/xhtml+xml"],"tar":["application\/x-tar"],"tgz":["application\/x-gzip-compressed"],"psd":["application\/x-photoshop","image\/vnd.adobe.photoshop"],"exe":["application\/x-msdownload"],"js":["application\/x-javascript"],"mp3":["audio\/mpeg","audio\/mpg","audio\/mpeg3","audio\/mp3"],"rar":["application\/x-rar","application\/rar","application\/x-rar-compressed"],"gzip":["application\/x-gzip"],"hqx":["application\/mac-binhex40","application\/mac-binhex","application\/x-binhex40","application\/x-mac-binhex40"],"cpt":["application\/mac-compactpro"],"bin":["application\/macbinary","application\/mac-binary","application\/x-binary","application\/x-macbinary"],"oda":["application\/oda"],"ai":["application\/postscript"],"smil":["application\/smil"],"mif":["application\/vnd.mif"],"wbxml":["application\/wbxml"],"wmlc":["application\/wmlc"],"dcr":["application\/x-director"],"dvi":["application\/x-dvi"],"gtar":["application\/x-gtar"],"php":["application\/x-httpd-php","application\/php","application\/x-php","text\/php","text\/x-php","application\/x-httpd-php-source"],"swf":["application\/x-shockwave-flash"],"sit":["application\/x-stuffit"],"z":["application\/x-compress"],"mid":["audio\/midi"],"aif":["audio\/x-aiff","audio\/aiff"],"ram":["audio\/x-pn-realaudio"],"rpm":["audio\/x-pn-realaudio-plugin"],"ra":["audio\/x-realaudio"],"rv":["video\/vnd.rn-realvideo"],"jp2":["image\/jp2","video\/mj2","image\/jpx","image\/jpm"],"tiff":["image\/tiff"],"eml":["message\/rfc822"],"pem":["application\/x-x509-user-cert","application\/x-pem-file"],"p10":["application\/x-pkcs10","application\/pkcs10"],"p12":["application\/x-pkcs12"],"p7a":["application\/x-pkcs7-signature"],"p7c":["application\/pkcs7-mime","application\/x-pkcs7-mime"],"p7r":["application\/x-pkcs7-certreqresp"],"p7s":["application\/pkcs7-signature"],"crt":["application\/x-x509-ca-cert","application\/pkix-cert"],"crl":["application\/pkix-crl","application\/pkcs-crl"],"pgp":["application\/pgp"],"gpg":["application\/gpg-keys"],"rsa":["application\/x-pkcs7"],"ics":["text\/calendar"],"zsh":["text\/x-scriptzsh"],"cdr":["application\/cdr","application\/coreldraw","application\/x-cdr","application\/x-coreldraw","image\/cdr","image\/x-cdr","zz-application\/zz-winassoc-cdr"],"wma":["audio\/x-ms-wma"],"vcf":["text\/x-vcard"],"srt":["text\/srt"],"vtt":["text\/vtt"],"ico":["image\/x-icon","image\/x-ico","image\/vnd.microsoft.icon"],"csv":["text\/x-comma-separated-values","text\/comma-separated-values","application\/vnd.msexcel"],"json":["application\/json","text\/json"]}';
        $all_mimes = json_decode($all_mimes, true);
        foreach ($all_mimes as $key => $value) {
            if (array_search($mime, $value) !== false) {
                return $key;
            }

        }
        return false;
    }

}
