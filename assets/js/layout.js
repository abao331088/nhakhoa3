/* layout.js - dựng header/footer (trang công khai) hoặc sidebar (trang sau đăng nhập).
   <body data-layout="public|app" data-active="khóa menu đang chọn"> */
(function () {
  const { esc, ROOT } = DCAS;
  const LOGO = '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M10 4C6.4 4 4 6.6 4 10c0 2.3.9 3.8 1.6 5.8.9 2.6.9 5.3 1.7 8 .5 1.7 1.3 3.7 2.800 3.700 1.900 0 2.100-3.800 3.200-6.400.4-.9 1-1.300 1.700-1.300s1.300.4 1.700 1.300c1.100 2.600 1.300 6.400 3.200 6.400 1.500 0 2.300-2 2.800-3.700.8-2.700.8-5.400 1.700-8C27.100 13.800 28 12.300 28 10c0-3.400-2.400-6-6-6-2.300 0-3.500 1.100-6 1.100S12.300 4 10 4z" fill="#9fb6ff"/></svg>';
  const brand = (href) => `<a class="brand" href="${href}">${LOGO}<span>Nha Khoa Smile</span></a>`;

  const PUBLIC_NAV = [['index.html', 'Trang chủ', 'home'], ['services.html', 'Dịch vụ', 'services'], ['about.html', 'Giới thiệu', 'about'], ['contact.html', 'Liên hệ', 'contact']];
  const MENUS = {
    patient: [['patient/dashboard.html', 'Tổng quan', 'dashboard'], ['patient/booking.html', 'Đặt lịch khám', 'booking'], ['patient/my-appointments.html', 'Lịch khám của tôi', 'my-appointments'], ['patient/history.html', 'Lịch sử khám', 'history'], ['patient/profile.html', 'Thông tin cá nhân', 'profile']],
    dentist: [['dentist/dashboard.html', 'Tổng quan', 'dashboard'], ['dentist/appointments.html', 'Lịch khám của tôi', 'appointments'], ['dentist/history.html', 'Lịch sử khám', 'history'], ['dentist/profile.html', 'Thông tin cá nhân', 'profile']],
    admin: [['admin/dashboard.html', 'Tổng quan', 'dashboard'], ['admin/patients.html', 'Bệnh nhân', 'patients'], ['admin/dentists.html', 'Nha sĩ', 'dentists'], ['admin/services.html', 'Dịch vụ khám', 'services'], ['admin/admins.html', 'Quản trị viên', 'admins'], ['admin/history.html', 'Lịch sử khám', 'history'], ['admin/profile.html', 'Thông tin cá nhân', 'profile']],
  };
  const active = document.body.dataset.active || '';
  const initial = (n) => esc((n || '?').trim().split(/\s+/).pop().charAt(0).toUpperCase());
  const avatarHtml = (u) => u.avatar ? `<img class="avatar" src="${ROOT}${esc(u.avatar)}" alt="">` : `<span class="avatar">${initial(u.full_name)}</span>`;

  async function logout() {
    await DCAS.api.post('logout.php');
    DCAS.resetSession();
    DCAS.go('login.html');
  }
  window.dcasLogout = logout;

  // ---------- Trang công khai ----------
  function renderPublic() {
    const h = document.getElementById('site-header');
    const f = document.getElementById('site-footer');
    if (h) {
      h.className = 'site-header';
      h.innerHTML = `<div class="inner">${brand(ROOT + 'index.html')}
        <nav class="site-nav" aria-label="Điều hướng chính">${PUBLIC_NAV.map(([p, t, k]) => `<a href="${ROOT}${p}"${k === active ? ' class="active"' : ''}>${t}</a>`).join('')}</nav>
        <div class="header-actions" id="header-actions"></div>
        <button class="menu-btn" type="button" aria-label="Mở menu">☰</button></div>`;
      h.querySelector('.menu-btn').addEventListener('click', () => h.classList.toggle('open'));
      const box = h.querySelector('#header-actions');
      box.innerHTML = `<a class="btn btn-ghost btn-sm" href="${ROOT}login.html">Đăng nhập</a><a class="btn btn-sm" href="${ROOT}register.html">Đăng ký</a>`;
      DCAS.getMe().then((me) => {
        if (me.logged_in) box.innerHTML = `<a class="btn btn-sm" href="${ROOT}${me.home}">Vào hệ thống</a><button class="btn btn-ghost btn-sm" type="button" id="hdr-logout">Đăng xuất</button>`;
        const b = document.getElementById('hdr-logout'); if (b) b.addEventListener('click', logout);
      });
    }
    if (f) {
      f.className = 'site-footer';
      f.innerHTML = `<div class="inner"><div><strong>Nha Khoa Smile</strong><br>Hệ thống đặt lịch khám nha khoa trực tuyến</div>
        <div><strong>Liên hệ</strong><br>Hotline: 0900 000 000<br>Email: lienhe@nhakhoasmile.vn</div>
        <div><strong>Giờ làm việc</strong><br>Thứ 2 - Thứ 7: 08:00 - 17:30</div></div>`;
    }
  }

  // ---------- Trang sau đăng nhập ----------
  function renderApp(u) {
    const content = document.getElementById('content');
    if (!content) return;
    const menu = MENUS[u.role] || [];
    const shell = document.createElement('div');
    shell.className = 'shell';
    shell.innerHTML = `<aside class="sidebar">${brand(ROOT + 'index.html')}
        <span class="role-tag">${esc(DCAS.ROLE_LABEL[u.role])}</span>
        <nav class="side-nav" aria-label="Menu chức năng">${menu.map(([p, t, k]) => `<a href="${ROOT}${p}"${k === active ? ' class="active"' : ''}>${t}</a>`).join('')}</nav></aside>
      <div class="main"><header class="topbar"><button class="menu-btn" type="button" aria-label="Mở menu">☰</button>
        <div class="who">${avatarHtml(u)}<span>${esc(u.full_name)}</span><button class="btn btn-ghost btn-sm" type="button" id="app-logout">Đăng xuất</button></div></header>
        <div id="slot"></div></div>`;
    document.body.prepend(shell);
    shell.querySelector('#slot').appendChild(content);
    content.classList.add('content');
    shell.querySelector('.menu-btn').addEventListener('click', () => shell.classList.toggle('open'));
    shell.querySelector('.main').addEventListener('click', () => shell.classList.remove('open'));
    shell.querySelector('#app-logout').addEventListener('click', logout);
    document.querySelectorAll('[data-user-name]').forEach((el) => { el.textContent = u.full_name; });
  }

  if (document.body.dataset.layout === 'app') DCAS.guard.then(renderApp);
  else document.addEventListener('DOMContentLoaded', renderPublic);
  if (document.body.dataset.layout === 'app') DCAS.ready = DCAS.guard;
})();
