<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class RJ_Video_Center {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => 'Video Center',
            'singular_name'      => 'Video',
            'menu_name'          => 'Video Center',
            'add_new'            => 'Add New Video',
            'add_new_item'       => 'Add New Video',
            'edit_item'          => 'Edit Video',
            'new_item'           => 'New Video',
            'view_item'          => 'View Video',
            'search_items'       => 'Search Videos',
            'not_found'          => 'No videos found',
            'not_found_in_trash' => 'No videos found in Trash'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => false,
            'publicly_queryable'  => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'video-center'),
            'capability_type'    => 'post',
            'menu_icon'          => 'dashicons-video-alt3',
            'supports'           => array('title')
        );

        register_post_type('rj_video', $args);
    }

    public function enqueue_admin_scripts($hook) {
        global $post;

        // Only enqueue on create/edit video screen
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ('rj_video' === $post->post_type) {
                wp_enqueue_media();
            }
        }
    }

    public function add_meta_boxes() {
        add_meta_box(
            'rj_video_details',
            'Video Details',
            array($this, 'render_meta_box'),
            'rj_video',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('rj_video_meta_box', 'rj_video_meta_box_nonce');

        // Get saved values
        $video_url = get_post_meta($post->ID, '_rj_video_url', true);
        $shop_now_link = get_post_meta($post->ID, '_rj_shop_now_link', true);

        // Meta box HTML
        ?>
        <div class="rj-video-meta-box">
            <p>
                <label for="rj_video_url"><strong>Video URL:</strong></label><br>
                <input type="text" id="rj_video_url" name="rj_video_url" value="<?php echo esc_attr($video_url); ?>" class="widefat" readonly>
                <button type="button" class="button button-secondary" id="rj_upload_video_button" style="margin-top: 5px;">Upload Video</button>
                <?php if ($video_url) : ?>
                    <video width="150" height="100" style="margin-top: 10px; display: block;" controls>
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>
            </p>
            <p>
                <label for="rj_shop_now_link"><strong>Shop Now Link:</strong></label><br>
                <input type="url" id="rj_shop_now_link" name="rj_shop_now_link" value="<?php echo esc_url($shop_now_link); ?>" class="widefat" placeholder="https://">
            </p>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#rj_upload_video_button').click(function(e) {
                    e.preventDefault();
                    var mediaUploader = wp.media({
                        title: 'Select Video',
                        button: {
                            text: 'Use this video'
                        },
                        multiple: false,
                        library: {
                            type: 'video'
                        }
                    });

                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#rj_video_url').val(attachment.url);
                        // Update video preview
                        var videoPreview = $('video');
                        if (videoPreview.length === 0) {
                            $('<video width="150" height="100" style="margin-top: 10px; display: block;" controls><source src="' + attachment.url + '" type="video/mp4">Your browser does not support the video tag.</video>').insertAfter('#rj_upload_video_button');
                        } else {
                            videoPreview.find('source').attr('src', attachment.url);
                            videoPreview[0].load();
                        }
                    });

                    mediaUploader.open();
                });
            });
        </script>
        <?php
    }

    public function save_meta_box_data($post_id) {
        // Check if nonce is set
        if (!isset($_POST['rj_video_meta_box_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['rj_video_meta_box_nonce'], 'rj_video_meta_box')) {
            return;
        }

        // If this is an autosave, don't do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save video URL
        if (isset($_POST['rj_video_url'])) {
            update_post_meta($post_id, '_rj_video_url', sanitize_text_field($_POST['rj_video_url']));
        }

        // Save shop now link
        if (isset($_POST['rj_shop_now_link'])) {
            update_post_meta($post_id, '_rj_shop_now_link', esc_url_raw($_POST['rj_shop_now_link']));
        }
    }
} 