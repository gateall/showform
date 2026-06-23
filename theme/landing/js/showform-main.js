/* ShowForm Main Renewal JS */
document.addEventListener('DOMContentLoaded', function(){
    if (window.Swiper) {
        new Swiper('.sfHeroSwiper', { 
            loop: true, 
            autoplay: { delay:4000, disableOnInteraction:false }, 
            speed: 800,
            pagination: {
                el: '.sf-indicator',
                clickable: true,
                bulletActiveClass: 'active',
                renderBullet: function (index, className) {
                    return '<span class="' + className + '"></span>';
                }
            }
        });
        
        new Swiper('.sfReviewSwiper', { 
            loop: true, 
            autoplay: { delay:3000, disableOnInteraction:false }, 
            slidesPerView: 1, 
            spaceBetween: 20, 
            breakpoints: { 
                768: { slidesPerView: 2 }, 
                1200: { slidesPerView: 3 } 
            } 
        });
    }
});
