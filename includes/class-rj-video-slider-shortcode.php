<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class RJ_Video_Slider_Shortcode {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('rj_video_slider', array($this, 'render_slider'));
    }

    public function render_slider($atts) {
        // Get videos from the custom post type
        $videos = get_posts(array(
            'post_type' => 'rj_video',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        // If no videos found, use default video
        if (empty($videos)) {
            $default_video = array(
                'url' => RJVRS_PLUGIN_URL . 'assets/videos/first.mp4',
                'shop_now_link' => '#'
            );
            $videos_data = array_fill(0, 4, $default_video);
        } else {
            $videos_data = array();
            foreach ($videos as $video) {
                $video_url = get_post_meta($video->ID, '_rj_video_url', true);
                $shop_now_link = get_post_meta($video->ID, '_rj_shop_now_link', true);
                
                if ($video_url) {
                    $videos_data[] = array(
                        'url' => $video_url,
                        'shop_now_link' => $shop_now_link ? $shop_now_link : '#'
                    );
                }
            }

            // If less than 4 videos, repeat videos to fill
            $original_videos = $videos_data;
            while (count($videos_data) < 4) {
                $videos_data = array_merge($videos_data, $original_videos);
            }

            // Trim to exactly 4 videos if we have more
            $videos_data = array_slice($videos_data, 0, 4);
        }

        // Start output buffering
        ob_start();
        ?>
        <!-- Story section Start -->
        <section class="al__story--section">
            <div class="story__conntainer swiper myvideoSwiper">
                <div class="al__story--inner swiper-wrapper">
                    <?php foreach ($videos_data as $video) : ?>
                    <div class="al__story--details swiper-slide">
                        <div class="al__story--image">
                            <video src="<?php echo esc_url($video['url']); ?>" muted="muted" autoplay="autoplay" loop></video>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="myvideoswiper__button">
                    <div class="swiper-button-next myvideoswiper__next"></div>
                    <div class="swiper-button-prev myvideoswiper__prev"></div>
                </div>
            </div>
        </section>
        <!-- Story section End -->

        <!-- Modal Popup Start -->
        <div class="story--modal--popup">
            <div class="al__spopup--inner">
                <div class="swiper storySwiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($videos_data as $video) : ?>
                        <div class="swiper-slide">
                            <div class="al__slider--data">
                                <div class="al__slider--video">
                                    <video src="<?php echo esc_url($video['url']); ?>" controls loop></video>
                                    <a href="<?php echo esc_url($video['shop_now_link']); ?>" class="video-shope-button">Shop Now</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="al__spopu-btn">
                    <div class="swiper-button-prev al__spopu-botton">
                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" height="30" width="30" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"></path>
                        </svg>
                    </div>
                    <div class="swiper-button-next al__spopu-botton">
                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" height="30" width="30" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="al__popup--close">
                <p>X</p>
            </div>
        </div>
        <!-- Modal Popup End -->
        <?php
        return ob_get_clean();
    }
} 