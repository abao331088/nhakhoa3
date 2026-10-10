/* api.js - gọi API PHP + tiện ích dùng chung. Nạp đầu tiên ở MỌI trang. */
(function () {
  // Gốc của dự án (vd http://localhost/DCAS/) suy ra từ đường dẫn của chính file này -> trang nằm ở thư mục con vẫn chạy đúng
  const ROOT = document.currentScript.src.replace(/assets\/js\/api\.js.*$/, '');
  const DCAS = (window.DCAS = { ROOT, csrf: null, _me: null });

  DCAS.ROLE_LABEL = { patient: 'Bệnh nhân', dentist: 'Nha sĩ', admin: 'Quản trị viên' };
  DCAS.esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  DCAS.go = (path) => { location.href = ROOT + path; };

  async function request(endpoint, method, data) {
    const opts = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
    if (method !== 'GET') {
      if (!DCAS.csrf) await DCAS.getMe(true);
      opts.headers['X-CSRF-Token'] = DCAS.csrf;
      if (data instanceof FormData) opts.body = data;
      else if (data) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(data); }
    }
    let res;
    try { res = await fetch(ROOT + 'api/' + endpoint, opts); }
    catch (e) { return { ok: false, status: 0, errors: {}, message: 'Không kết nối được máy chủ. Hãy kiểm tra Apache và MySQL (XAMPP) đã bật chưa, và trang được mở qua http://localhost/...' }; }
    const json = await res.json().catch(() => null);
    if (!json) return { ok: false, status: res.status, errors: {}, message: 'Máy chủ trả về dữ liệu không hợp lệ (có thể lỗi PHP hoặc chưa import database/dcas.sql).' };
    json.status = res.status;
    if (json.csrf) DCAS.csrf = json.csrf;
    if (res.status === 419) { DCAS.csrf = null; DCAS._me = null; }   // token hết hạn -> lần sau tự lấy lại
    return json;
  }
  DCAS.api = { get: (e) => request(e, 'GET'), post: (e, d) => request(e, 'POST', d) };

  // Thông tin phiên đăng nhập (cache theo trang)
  DCAS.getMe = (force) => {
    if (!DCAS._me || force) DCAS._me = request('me.php', 'GET');
    return DCAS._me;
  };
  DCAS.resetSession = () => { DCAS.csrf = null; DCAS._me = null; };

  // ---------- Tiện ích form ----------
  DCAS.showMsg = (el, type, text) => {            // type: error | success | info
    el.className = 'alert alert-' + type;
    el.textContent = text;
    el.hidden = false;
  };
  DCAS.clearErrors = (form) => {
    form.querySelectorAll('.field').forEach((f) => { f.classList.remove('invalid'); const e = f.querySelector('.err'); if (e) e.textContent = ''; });
  };
  DCAS.setErrors = (form, errors) => {            // errors: { tên_input: 'thông báo' }
    let first = null;
    Object.entries(errors || {}).forEach(([name, msg]) => {
      const input = form.elements[name];
      const f = input && input.closest('.field');
      if (!f) return;
      f.classList.add('invalid');
      const e = f.querySelector('.err');
      if (e) e.textContent = msg;
      first = first || input;
    });
    if (first) first.focus();
  };
  DCAS.busy = (btn, on, text) => {
    if (on) { btn.dataset.label = btn.textContent; btn.textContent = text || 'Đang xử lý...'; btn.disabled = true; }
    else { btn.textContent = btn.dataset.label || btn.textContent; btn.disabled = false; }
  };
  // Nút Hiện/Ẩn mật khẩu: gắn vào mọi .field có thuộc tính data-pw
  DCAS.initPwToggles = () => {
    document.querySelectorAll('.field[data-pw]').forEach((f) => {
      if (f.querySelector('.pw-toggle')) return;
      f.classList.add('has-toggle');
      const b = document.createElement('button');
      b.type = 'button'; b.className = 'pw-toggle'; b.textContent = 'Hiện'; b.setAttribute('aria-label', 'Hiện hoặc ẩn mật khẩu');
      b.addEventListener('click', () => {
        const i = f.querySelector('input');
        const show = i.type === 'password';
        i.type = show ? 'text' : 'password';
        b.textContent = show ? 'Ẩn' : 'Hiện';
      });
      f.appendChild(b);
    });
  };
  // Kiểm tra phía trình duyệt (chỉ để báo lỗi nhanh - server vẫn kiểm tra lại)
  DCAS.v = {
    email: (s) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s),
    phone: (s) => /^\d{10}$/.test(s),
    password: (s) => s.length >= 6 && s.length <= 72,
  };
  document.addEventListener('DOMContentLoaded', DCAS.initPwToggles);
})();
