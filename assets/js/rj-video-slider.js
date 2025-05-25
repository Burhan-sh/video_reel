jQuery(document).ready(function($) {
    // Initialize main video slider
    var swiper = new Swiper(".myvideoSwiper", {
        slidesPerView: 4,
        spaceBetween: 30,
        loop: true,
        navigation: {
            nextEl: ".myvideoswiper__next",
            prevEl: ".myvideoswiper__prev",
        },
    });

    // Initialize popup slider
    var storySwiper = new Swiper(".storySwiper", {
        slidesPerView: 1,
        spaceBetween: 0,
        centeredSlides: true,
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        breakpoints: {
            991: {
                spaceBetween: 30,
                slidesPerView: "3",
            },
            1600: {
                slidesPerView: "3",
            },
        },
    });

    // Handle popup open/close
    $(document).on('click', '.al__story--details', function() {
        $('.story--modal--popup').addClass('al__open--modal');
        
        // Pause all videos in main slider
        $('.myvideoSwiper video').each(function() {
            this.pause();
        });
    });

    $(document).on('click', '.al__popup--close p', function() {
        $('.story--modal--popup').removeClass('al__open--modal');
        
        // Resume playing videos in main slider
        $('.myvideoSwiper video').each(function() {
            this.play();
        });
    });
}); 