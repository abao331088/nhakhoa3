/* profile.js - UC005 Cập nhật thông tin cá nhân + UC004 Đổi mật khẩu (dùng chung cho 3 vai trò) */
(function () {
  const infoForm = document.getElementById('info-form');
  const pwForm = document.getElementById('pw-form');
  const infoMsg = document.getElementById('info-msg');
  const pwMsg = document.getElementById('pw-msg');
  const force = document.getElementById('force-msg');
  const avatarBox = document.getElementById('avatar-preview');

  function showAvatar(u) {
    avatarBox.innerHTML = u.avatar
      ? '<img class="avatar" style="width:64px;height:64px" src="' + DCAS.ROOT + DCAS.esc(u.avatar) + '?t=' + Date.now() + '" alt="Ảnh đại diện">'
      : '<span class="avatar">' + DCAS.esc((u.full_name || '?').trim().split(/\s+/).pop().charAt(0).toUpperCase()) + '</span>';
  }
  function fill(u) {
    infoForm.full_name.value = u.full_name || '';
    infoForm.email.value = u.email || '';
    infoForm.phone.value = u.phone || '';
    infoForm.birthday.value = u.birthday || '';
    infoForm.gender.value = u.gender || '';
    showAvatar(u);
  }

  DCAS.guard.then((u) => {
    fill(u);
    if (u.must_change_password) {
      force.hidden = false;
      force.textContent = 'Bạn đang dùng mật khẩu tạm. Hãy đổi mật khẩu bên dưới để tiếp tục sử dụng hệ thống.';
      pwForm.scrollIntoView({ behavior: 'smooth' });
    }
  });

  infoForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    DCAS.clearErrors(infoForm); infoMsg.hidden = true;
    const name = infoForm.full_name.value.trim(), phone = infoForm.phone.value.trim();
    const errs = {};
    if (!name || name.length > 255) errs.full_name = 'Họ tên không được để trống và không quá 255 ký tự';
    if (phone && !DCAS.v.phone(phone)) errs.phone = 'Số điện thoại gồm đúng 10 chữ số';
    const f = infoForm.avatar.files[0];
    if (f && !/^image\/(png|jpeg)$/.test(f.type)) errs.avatar = 'Ảnh phải có định dạng png, jpg hoặc jpeg';
    else if (f && f.size > 2 * 1024 * 1024) errs.avatar = 'Ảnh tối đa 2MB';
    if (Object.keys(errs).length) { DCAS.setErrors(infoForm, errs); return; }

    const fd = new FormData();
    fd.append('full_name', name);
    fd.append('phone', phone);
    fd.append('birthday', infoForm.birthday.value);
    fd.append('gender', infoForm.gender.value);
    if (f) fd.append('avatar', f);

    const btn = infoForm.querySelector('button[type=submit]');
    DCAS.busy(btn, true, 'Đang lưu...');
    const res = await DCAS.api.post('profile.php', fd);
    DCAS.busy(btn, false);
    if (!res.ok) { DCAS.setErrors(infoForm, res.errors); DCAS.showMsg(infoMsg, 'error', res.message); return; }
    DCAS.showMsg(infoMsg, 'success', res.message);
    fill(res.user);
    infoForm.avatar.value = '';
    document.querySelectorAll('[data-user-name], .who > span:not(.avatar)').forEach((el) => { el.textContent = res.user.full_name; });
  });

  pwForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    DCAS.clearErrors(pwForm); pwMsg.hidden = true;
    const d = { old_password: pwForm.old_password.value, new_password: pwForm.new_password.value, confirm_password: pwForm.confirm_password.value };
    const errs = {};
    if (!d.old_password) errs.old_password = 'Vui lòng nhập mật khẩu cũ';
    if (!DCAS.v.password(d.new_password)) errs.new_password = 'Mật khẩu mới từ 6 đến 72 ký tự';
    else if (d.new_password === d.old_password) errs.new_password = 'Mật khẩu mới phải khác mật khẩu cũ';
    else if (d.new_password !== d.confirm_password) errs.confirm_password = 'Mật khẩu xác nhận không trùng khớp';
    if (Object.keys(errs).length) { DCAS.setErrors(pwForm, errs); return; }

    const btn = pwForm.querySelector('button[type=submit]');
    DCAS.busy(btn, true, 'Đang lưu...');
    const res = await DCAS.api.post('change-password.php', d);
    DCAS.busy(btn, false);
    if (!res.ok) { DCAS.setErrors(pwForm, res.errors); DCAS.showMsg(pwMsg, 'error', res.message); return; }
    pwForm.reset();
    force.hidden = true;
    DCAS.resetSession();   // phiên đã đổi ID ở server -> lấy lại CSRF token
    DCAS.showMsg(pwMsg, 'success', res.message);
    const me = await DCAS.getMe(true);
    if (new URLSearchParams(location.search).get('force') && me.logged_in) setTimeout(() => DCAS.go(me.home), 1200);
  });
})();
