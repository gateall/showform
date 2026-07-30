(function () {
    'use strict';

    var COLLAPSE_KEY = 'sf_manager_sidebar_collapsed';
    var GROUPS_KEY = 'sf_manager_open_groups'; // sessionStorage: 같은 세션(탭) 안에서만 유지
    var app = document.getElementById('mgrApp');
    if (!app) return;

    /* ---------- 사이드바 펼침/축소(PC) ---------- */
    var collapseBtn = document.getElementById('mgrSidebarCollapse');

    function applyCollapsedState(collapsed) {
        app.classList.toggle('is-sidebar-collapsed', collapsed);
    }

    try {
        applyCollapsedState(localStorage.getItem(COLLAPSE_KEY) === '1');
    } catch (e) { /* localStorage 미지원 환경은 기본(펼침) 상태로 둔다 */ }

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            var next = !app.classList.contains('is-sidebar-collapsed');
            applyCollapsedState(next);
            try { localStorage.setItem(COLLAPSE_KEY, next ? '1' : '0'); } catch (e) {}
        });
    }

    /* ---------- 메뉴 그룹 아코디언(사이드바 + 모바일 드로어 공용) ---------- */
    function readOpenGroups() {
        try {
            var raw = sessionStorage.getItem(GROUPS_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }
    function writeOpenGroups(state) {
        try { sessionStorage.setItem(GROUPS_KEY, JSON.stringify(state)); } catch (e) {}
    }

    var openGroups = readOpenGroups();

    function setGroupExpanded(groupId, expanded) {
        var toggles = document.querySelectorAll('[data-mgr-group-toggle="' + groupId + '"]');
        for (var i = 0; i < toggles.length; i++) {
            toggles[i].setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
    }

    // 서버가 이미 렌더링 시점에 현재 메뉴가 속한 그룹을 펼쳐서 내려주므로(is-active),
    // 여기서는 그 상태를 sessionStorage에 반영만 하고 별도로 처음부터 다시 펼치지 않는다.
    var initialToggles = document.querySelectorAll('[data-mgr-group-toggle]');
    for (var j = 0; j < initialToggles.length; j++) {
        var t = initialToggles[j];
        var gid = t.getAttribute('data-mgr-group-toggle');
        if (Object.prototype.hasOwnProperty.call(openGroups, gid)) {
            setGroupExpanded(gid, !!openGroups[gid]);
        } else if (t.getAttribute('aria-expanded') === 'true') {
            openGroups[gid] = true;
        }
    }
    writeOpenGroups(openGroups);

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-mgr-group-toggle]');
        if (!toggle) return;

        var groupId = toggle.getAttribute('data-mgr-group-toggle');
        var next = toggle.getAttribute('aria-expanded') !== 'true';
        setGroupExpanded(groupId, next);
        openGroups[groupId] = next;
        writeOpenGroups(openGroups);
    });

    // PC ↔ 모바일 폭 전환 시 상태 충돌 방지: 화면 크기가 바뀌면 모바일 드로어를
    // 강제로 닫아 두 레이아웃의 열림 상태가 서로 어긋나지 않게 한다.
    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (window.innerWidth > 768) {
                app.classList.remove('is-mobile-nav-open');
                document.body.classList.remove('mgr-scroll-lock');
            }
        }, 150);
    });
})();
