/* setup.js - tạo Quản trị viên đầu tiên bằng mã khởi tạo */
(function () {
  const form = document.getElementById('setup-form');
  const msg = document.getElementById('msg');
  const done = document.getElementById('already');

  DCAS.api.get('setup-admin.php').then((res) => {
    if (!res.ok) { DCAS.showMsg(msg, 'error', res.message); return; }
    if (res.needs_setup) form.hidden = false; else done.hidden = false;
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    DCAS.clearErrors(form); msg.hidden = true;
    const d = {
      setup_code: form.setup_code.value, full_name: form.full_name.value.trim(), email: form.email.value.trim(),
      phone: form.phone.value.trim(), password: form.password.value, confirm_password: form.confirm_password.value,
    };
    const errs = {};
    if (!d.setup_code) errs.setup_code = 'Vui lòng nhập mã khởi tạo';
    if (!d.full_name) errs.full_name = 'Vui lòng nhập họ tên';
    if (!DCAS.v.email(d.email)) errs.email = 'Địa chỉ email không hợp lệ';
    if (d.phone && !DCAS.v.phone(d.phone)) errs.phone = 'Số điện thoại gồm đúng 10 chữ số';
    if (!DCAS.v.password(d.password)) errs.password = 'Mật khẩu từ 6 đến 72 ký tự';
    else if (d.password !== d.confirm_password) errs.confirm_password = 'Mật khẩu xác nhận không trùng khớp';
    if (Object.keys(errs).length) { DCAS.setErrors(form, errs); return; }

    const btn = form.querySelector('button[type=submit]');
    DCAS.busy(btn, true, 'Đang tạo...');
    const res = await DCAS.api.post('setup-admin.php', d);
    DCAS.busy(btn, false);
    if (!res.ok) {
      DCAS.setErrors(form, res.errors); DCAS.showMsg(msg, 'error', res.message);
      if (res.code === 'ALREADY_SETUP') { form.hidden = true; done.hidden = false; }
      return;
    }
    DCAS.showMsg(msg, 'success', res.message);
    form.hidden = true;
    setTimeout(() => DCAS.go('login.html'), 1200);
  });
})();
