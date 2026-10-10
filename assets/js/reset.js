/* reset.js - UC003 (bước 2) đặt mật khẩu mới từ liên kết */
(function () {
  const form = document.getElementById('reset-form');
  const msg = document.getElementById('msg');
  const token = new URLSearchParams(location.search).get('token') || '';

  async function init() {
    const res = token ? await DCAS.api.get('reset-password.php?token=' + encodeURIComponent(token)) : { ok: false, message: 'Liên kết không hợp lệ. Vui lòng yêu cầu lại.' };
    if (res.ok) { form.hidden = false; return; }
    DCAS.showMsg(msg, 'error', res.message);
    document.getElementById('retry').hidden = false;
  }
  init();

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    DCAS.clearErrors(form); msg.hidden = true;
    const password = form.password.value, confirm_password = form.confirm_password.value;
    const errs = {};
    if (!DCAS.v.password(password)) errs.password = 'Mật khẩu từ 6 đến 72 ký tự';
    else if (password !== confirm_password) errs.confirm_password = 'Mật khẩu xác nhận không trùng khớp';
    if (Object.keys(errs).length) { DCAS.setErrors(form, errs); return; }

    const btn = form.querySelector('button[type=submit]');
    DCAS.busy(btn, true, 'Đang lưu...');
    const res = await DCAS.api.post('reset-password.php', { token, password, confirm_password });
    DCAS.busy(btn, false);
    if (!res.ok) { DCAS.setErrors(form, res.errors); DCAS.showMsg(msg, 'error', res.message); return; }
    DCAS.go('login.html?reset=1');
  });
})();
