/* register.js - UC002 Đăng ký tài khoản Bệnh nhân */
(function () {
  const form = document.getElementById('register-form');
  const msg = document.getElementById('msg');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    DCAS.clearErrors(form); msg.hidden = true;
    const d = {
      full_name: form.full_name.value.trim(), email: form.email.value.trim(), phone: form.phone.value.trim(),
      password: form.password.value, confirm_password: form.confirm_password.value,
    };

    const errs = {};
    if (!d.full_name) errs.full_name = 'Vui lòng nhập họ tên';
    else if (d.full_name.length > 255) errs.full_name = 'Họ tên không quá 255 ký tự';
    if (!d.email) errs.email = 'Vui lòng nhập email';
    else if (!DCAS.v.email(d.email)) errs.email = 'Địa chỉ email không hợp lệ';
    if (!d.phone) errs.phone = 'Vui lòng nhập số điện thoại';
    else if (!DCAS.v.phone(d.phone)) errs.phone = 'Số điện thoại gồm đúng 10 chữ số';
    if (!d.password) errs.password = 'Vui lòng nhập mật khẩu';
    else if (!DCAS.v.password(d.password)) errs.password = 'Mật khẩu từ 6 đến 72 ký tự';
    if (!d.confirm_password) errs.confirm_password = 'Vui lòng nhập lại mật khẩu';
    else if (!errs.password && d.password !== d.confirm_password) errs.confirm_password = 'Mật khẩu xác nhận không trùng khớp';
    if (Object.keys(errs).length) { DCAS.setErrors(form, errs); return; }

    const btn = form.querySelector('button[type=submit]');
    DCAS.busy(btn, true, 'Đang tạo tài khoản...');
    const res = await DCAS.api.post('register.php', d);
    DCAS.busy(btn, false);

    if (!res.ok) { DCAS.setErrors(form, res.errors); DCAS.showMsg(msg, 'error', res.message); return; }
    DCAS.go('login.html?registered=1');
  });
})();
