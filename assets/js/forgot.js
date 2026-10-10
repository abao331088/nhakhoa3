/* forgot.js - UC003 (bước 1) yêu cầu liên kết đặt lại mật khẩu */
(function () {
  const form = document.getElementById('forgot-form');
  const msg = document.getElementById('msg');
  const demo = document.getElementById('demo-link');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    DCAS.clearErrors(form); msg.hidden = true; demo.hidden = true;
    const email = form.email.value.trim();
    if (!email || !DCAS.v.email(email)) { DCAS.setErrors(form, { email: 'Vui lòng nhập email hợp lệ' }); return; }

    const btn = form.querySelector('button[type=submit]');
    DCAS.busy(btn, true, 'Đang gửi...');
    const res = await DCAS.api.post('forgot-password.php', { email });
    DCAS.busy(btn, false);

    if (!res.ok) { DCAS.setErrors(form, res.errors); DCAS.showMsg(msg, 'error', res.message); return; }
    DCAS.showMsg(msg, 'success', res.message);
    if (res.reset_link) {   // chế độ mô phỏng (chưa cấu hình gửi mail thật)
      demo.hidden = false;
      demo.innerHTML = 'Chế độ mô phỏng - chưa gửi email thật. Mở liên kết này để đặt lại mật khẩu:<a class="linkbox" href="' + DCAS.esc(res.reset_link) + '">' + DCAS.esc(res.reset_link) + '</a>';
    }
  });
})();
