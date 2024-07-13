<?php
/**
 * Plugin Name: Custom Media Renamer
 * Description: Rimuove i trattini dai nomi dei file media, li sostituisce con spazi, rimuove le estensioni e aggiorna il testo alternativo.
 * Version: 1.1
 * Author: Sviluppando sul Web
 * Author URI: https://sviluppandosulweb.com
 */

function custom_media_renamer_menu() {
    add_media_page('Custom Media Renamer', 'Media Renamer', 'manage_options', 'custom-media-renamer', 'custom_media_renamer_page');
}
add_action('admin_menu', 'custom_media_renamer_menu');

function custom_media_renamer_page() {
    ?>
    <div class="wrap">
        <h1>Custom Media Renamer</h1>
        <button id="start-renaming" class="button button-primary">Start</button>
        <button id="pause-renaming" class="button">Pause</button>
        <div id="progress-bar" style="margin-top: 20px; width: 100%; background-color: #f3f3f3;">
            <div id="progress" style="width: 0%; height: 30px; background-color: #4caf50;"></div>
        </div>
        <div id="status" style="margin-top: 10px;"></div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($){
            var paused = false;
            var ajaxOffset = 0;
            $('#start-renaming').click(function(){
                paused = false;
                renameMedia();
            });
            $('#pause-renaming').click(function(){
                paused = true;
            });
            function renameMedia() {
                if (paused) return;
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'rename_media_files',
                        offset: ajaxOffset,
                    },
                    success: function(response) {
                        ajaxOffset += response.count;
                        var progress = (response.total > 0) ? (ajaxOffset / response.total) * 100 : 100;
                        $('#progress').css('width', progress + '%');
                        $('#status').text('Renamed ' + ajaxOffset + ' of ' + response.total + ' media files.');
                        if (ajaxOffset < response.total) {
                            renameMedia();
                        } else {
                            $('#status').text('All media files have been renamed.');
                        }
                    }
                });
            }
        });
    </script>
    <?php
}

function custom_media_renamer_ajax() {
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $media_query = new WP_Query(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 10,
        'offset' => $offset,
    ));
    $total = $media_query->found_posts;
    $count = 0;
    while ($media_query->have_posts()) : $media_query->the_post();
        $post_ID = get_the_ID();
        $title = get_the_title($post_ID);
        $decoded_title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        $new_title = preg_replace('/\.[^.]+$/', '', $decoded_title);
        $new_title = str_replace('-', ' ', $new_title);
        $args = array(
            'ID'         => $post_ID,
            'post_title' => $new_title,
        );
        wp_update_post($args);
        update_post_meta($post_ID, '_wp_attachment_image_alt', $new_title);
        $count++;
    endwhile;
    wp_reset_postdata();
    wp_send_json(array('count' => $count, 'total' => $total));
}
add_action('wp_ajax_rename_media_files', 'custom_media_renamer_ajax');
