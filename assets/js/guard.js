/* guard.js - chặn người chưa đăng nhập / sai vai trò.
   Dùng: <script src="../assets/js/guard.js" data-role="patient"></script>
   LƯU Ý: đây chỉ để điều hướng cho tiện. Bảo vệ THẬT nằm ở API (require_role trong PHP). */
(function () {
  const role = document.currentScript.dataset.role;
  const hold = new Promise(() => {});                    // promise không bao giờ xong -> dừng trang khi đang chuyển hướng
  document.documentElement.style.visibility = 'hidden';  // ẩn trang đến khi kiểm tra xong, tránh nháy nội dung

  DCAS.guard = DCAS.getMe().then((me) => {
    const base = new URL(DCAS.ROOT).pathname;
    const here = location.pathname.slice(base.length);   // vd 'patient/booking.html'
    if (!me.logged_in) { DCAS.go('login.html?next=' + encodeURIComponent(here)); return hold; }
    const u = me.user;
    if (role && u.role !== role) { DCAS.go(me.home); return hold; }
    if (u.must_change_password && !/profile\.html$/.test(here)) { DCAS.go(u.role + '/profile.html?force=1'); return hold; }
    document.documentElement.style.visibility = '';
    return u;
  });
})();
