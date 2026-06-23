(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('landingInquiryForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                var agree = form.querySelector('input[name="agree"]');
                if (!agree || !agree.checked) {
                    e.preventDefault();
                    alert('개인정보 동의 후 상담 신청이 가능합니다.');
                    return;
                }
            });
        }
    });
})();
