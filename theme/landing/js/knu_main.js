(function () {
    'use strict';

    function initHeroSwiper() {
        var target = document.querySelector('.knu-hero-swiper');
        if (!target || typeof Swiper === 'undefined') return;

        return new Swiper(target, {
            loop: true,
            speed: 900,
            effect: 'fade',
            fadeEffect: { crossFade: true },
            autoplay: {
                delay: 5200,
                disableOnInteraction: false
            },
            pagination: {
                el: '.knu-hero-pagination',
                clickable: true
            },
            navigation: {
                nextEl: '.knu-hero-next',
                prevEl: '.knu-hero-prev'
            }
        });
    }

    function animateCounter(el) {
        if (!el || el.dataset.done === '1') return;

        var target = parseInt(el.getAttribute('data-count') || '0', 10);
        var suffix = el.getAttribute('data-suffix') || '';
        var duration = 1400;
        var startedAt = null;

        if (isNaN(target)) return;
        el.dataset.done = '1';

        function frame(time) {
            if (!startedAt) startedAt = time;
            var progress = Math.min((time - startedAt) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var value = Math.floor(target * eased);
            el.textContent = value.toLocaleString() + suffix;
            if (progress < 1) requestAnimationFrame(frame);
        }

        requestAnimationFrame(frame);
    }

    function initObserver() {
        var targets = document.querySelectorAll('[data-knu-fade]');
        if (!targets.length || typeof IntersectionObserver === 'undefined') {
            targets.forEach(function (el) {
                el.classList.add('is-show');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries, io) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-show');

                if (entry.target.classList.contains('knu-counter-item')) {
                    animateCounter(entry.target.querySelector('.knu-counter-num'));
                }

                io.unobserve(entry.target);
            });
        }, {
            threshold: 0.16,
            rootMargin: '0px 0px -8% 0px'
        });

        targets.forEach(function (el) {
            observer.observe(el);
        });
    }

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var id = anchor.getAttribute('href');
                if (!id || id === '#') return;
                var target = document.querySelector(id);
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function initParallax() {
        var heroSlides = document.querySelectorAll('.knu-hero-slide');
        if (!heroSlides.length) return;

        window.addEventListener('scroll', function () {
            var y = Math.min(window.scrollY * 0.08, 46);
            heroSlides.forEach(function (slide) {
                slide.style.backgroundPosition = 'center calc(50% + ' + y + 'px)';
            });
        }, { passive: true });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initHeroSwiper();
        initObserver();
        initSmoothScroll();
        initParallax();
    });
})();
