(function () {
    'use strict';

    var app = document.getElementById('mgrApp');
    if (!app) return;

    /* ---------- 모바일 드로어 ---------- */
    var hamburger = document.getElementById('mgrHamburger');
    var mobileClose = document.getElementById('mgrMobileClose');
    var overlay = document.getElementById('mgrMobileOverlay');

    function openMobileNav() {
        app.classList.add('is-mobile-nav-open');
        document.body.classList.add('mgr-scroll-lock');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
    }
    function closeMobileNav() {
        app.classList.remove('is-mobile-nav-open');
        document.body.classList.remove('mgr-scroll-lock');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
    }
    if (hamburger) hamburger.addEventListener('click', openMobileNav);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileNav);
    if (overlay) overlay.addEventListener('click', closeMobileNav);
    document.addEventListener('keyup', function (e) {
        if (e.key === 'Escape') closeMobileNav();
    });
    // 하단 고정 메뉴의 "전체" 버튼 - 새 패널을 만들지 않고 기존 드로어를 그대로 연다.
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-mgr-open-mobile]')) {
            openMobileNav();
        }
    });

    /* ---------- 계정 드롭다운 ---------- */
    var accountBtn = document.getElementById('mgrAccountBtn');
    var accountMenu = document.getElementById('mgrAccountMenu');
    if (accountBtn && accountMenu) {
        accountBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = accountMenu.classList.toggle('is-open');
            accountBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', function () {
            accountMenu.classList.remove('is-open');
            accountBtn.setAttribute('aria-expanded', 'false');
        });
    }

    /* ---------- 모달 (data-mgr-modal-open="modalId" / data-mgr-modal-close) ---------- */
    document.addEventListener('click', function (e) {
        var openTrigger = e.target.closest('[data-mgr-modal-open]');
        if (openTrigger) {
            var modal = document.getElementById(openTrigger.getAttribute('data-mgr-modal-open'));
            if (modal) {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }
            return;
        }
        var closeTrigger = e.target.closest('[data-mgr-modal-close]');
        if (closeTrigger) {
            var openModal = closeTrigger.closest('.mgr-modal');
            if (openModal) {
                openModal.classList.remove('is-open');
                openModal.setAttribute('aria-hidden', 'true');
            }
        }
    });

    /* ---------- 토스트 ---------- */
    window.mgrToast = function (message, tone) {
        var container = document.getElementById('mgrToastContainer');
        if (!container) return;
        var el = document.createElement('div');
        el.className = 'mgr-toast' + (tone ? ' mgr-tone-' + tone : '');
        el.textContent = message;
        container.appendChild(el);
        setTimeout(function () {
            el.remove();
        }, 3200);
    };
})();
