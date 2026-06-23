document.addEventListener('DOMContentLoaded', function () {
    var deleteButtons = document.querySelectorAll('[data-landing-delete]');

    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('정말 삭제하시겠습니까?')) {
                e.preventDefault();
            }
        });
    });
});
