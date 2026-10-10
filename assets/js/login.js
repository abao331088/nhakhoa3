/* login.js - UC001 Đăng nhập */
(function () {
  const form = document.getElementById('login-form');
  const msg = document.getElementById('msg');
  const params = new URLSearchParams(location.search);

  if (params.get('registered')) DCAS.showMsg(msg, 'success', 'Đăng ký thành công. Hãy đăng nhập để tiếp tục.');
  if (params.get('reset')) DCAS.showMsg(msg, 'success', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');

  // Đã đăng nhập rồi thì đưa thẳng vào hệ thống
  DCAS.getMe().then((me) => { if (me.logged_in && !me.user.must_change_password) DCAS.go(me.home); });

  // Chỉ chấp nhận ?next= trỏ vào đúng khu vực của vai trò (tránh chuyển hướng ra ngoài)
  function safeNext(role) {
    const n = params.get('next') || '';
    return /^[a-z-]+\/[a-z-]+\.html$/.test(n) && n.startsWith(role + '/') ? n : null;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    DCAS.clearErrors(form); msg.hidden = true;
    const email = form.email.value.trim();
    const password = form.password.value;

    const errs = {};
    if (!email) errs.email = 'Vui lòng nhập email';
    else if (!DCAS.v.email(email)) errs.email = 'Email không đúng định dạng';
    if (!password) errs.password = 'Vui lòng nhập mật khẩu';
    if (Object.keys(errs).length) { DCAS.setErrors(form, errs); return; }

    const btn = form.querySelector('button[type=submit]');
    DCAS.busy(btn, true, 'Đang đăng nhập...');
    const res = await DCAS.api.post('login.php', { email, password });
    DCAS.busy(btn, false);

    if (!res.ok) {
      DCAS.setErrors(form, res.errors);
      DCAS.showMsg(msg, res.code === 'PENDING' ? 'info' : 'error', res.message);
      if (res.status !== 422) form.password.value = '';
      return;
    }
    DCAS.resetSession();
    const u = res.user;
    DCAS.go(u.must_change_password ? res.redirect : (safeNext(u.role) || res.redirect));
  });
})();
