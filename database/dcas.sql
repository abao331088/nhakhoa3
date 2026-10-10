-- ============================================================
-- DCAS - Hệ thống Đặt lịch khám Nha khoa Trực tuyến
-- Import bằng phpMyAdmin (tab Import) hoặc: mysql -u root < dcas.sql
-- Yêu cầu: MySQL 5.7+ / MariaDB 10.2+ (XAMPP mới đều đáp ứng)
-- ============================================================
CREATE DATABASE IF NOT EXISTS dcas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dcas;

-- ---------- Người dùng (Bệnh nhân / Nha sĩ / Quản trị viên) ----------
CREATE TABLE IF NOT EXISTS users (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name            VARCHAR(255) NOT NULL,
  email                VARCHAR(190) NOT NULL,
  phone                VARCHAR(10)  NULL,
  password_hash        VARCHAR(255) NOT NULL,
  role                 ENUM('patient','dentist','admin') NOT NULL DEFAULT 'patient',
  -- pending: chờ QTV xác nhận | active: dùng được | locked: bị khóa
  status               ENUM('pending','active','locked') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = đang dùng mật khẩu tạm, bắt buộc đổi
  birthday             DATE NULL,
  gender               ENUM('male','female','other') NULL,
  avatar               VARCHAR(255) NULL,
  created_by           INT UNSIGNED NULL,               -- QTV đã tạo tài khoản (nha sĩ / QTV)
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB;

-- ---------- Chống dò mật khẩu ----------
CREATE TABLE IF NOT EXISTS login_attempts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(190) NOT NULL,
  ip         VARCHAR(45)  NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_la_email (email, created_at),
  KEY idx_la_ip (ip, created_at)
) ENGINE=InnoDB;

-- ---------- Đặt lại mật khẩu (UC003) - chỉ lưu hash của token ----------
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used       TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pr_token (token_hash),
  KEY idx_pr_user (user_id, created_at),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Dịch vụ khám (UC007, UC013) ----------
CREATE TABLE IF NOT EXISTS services (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(255) NOT NULL,
  description  TEXT NULL,
  price        INT UNSIGNED NOT NULL,          -- VND
  duration_min INT UNSIGNED NOT NULL,          -- phút
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Nha sĩ phụ trách dịch vụ nào
CREATE TABLE IF NOT EXISTS dentist_services (
  dentist_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (dentist_id, service_id),
  CONSTRAINT fk_ds_dentist FOREIGN KEY (dentist_id) REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_ds_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Giờ làm việc của nha sĩ (để sinh khung giờ trống). weekday: 1=Thứ 2 ... 7=Chủ nhật
CREATE TABLE IF NOT EXISTS dentist_schedules (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dentist_id INT UNSIGNED NOT NULL,
  weekday    TINYINT UNSIGNED NOT NULL,
  start_time TIME NOT NULL,
  end_time   TIME NOT NULL,
  CONSTRAINT fk_sch_dentist FOREIGN KEY (dentist_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Lịch khám (UC008, UC009, UC010) ----------
-- Vòng đời: pending -> confirmed/rejected -> completed ; pending/confirmed -> cancelled
CREATE TABLE IF NOT EXISTS appointments (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id       INT UNSIGNED NOT NULL,
  dentist_id       INT UNSIGNED NOT NULL,
  service_id       INT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  slot_start       TIME NOT NULL,
  slot_end         TIME NOT NULL,
  note             TEXT NULL,
  status           ENUM('pending','confirmed','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  reject_reason    TEXT NULL,
  result_note      TEXT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Chống đặt trùng khung giờ NGAY TRONG DATABASE (kể cả khi 2 người bấm cùng lúc).
  -- Lịch đã hủy / bị từ chối có active_slot = NULL nên không chặn việc đặt lại khung giờ đó.
  active_slot VARCHAR(40) GENERATED ALWAYS AS (
    IF(status IN ('pending','confirmed','completed'),
       CONCAT(dentist_id, '|', appointment_date, '|', slot_start), NULL)
  ) STORED,
  UNIQUE KEY uq_active_slot (active_slot),
  KEY idx_ap_patient (patient_id, appointment_date),
  KEY idx_ap_dentist (dentist_id, appointment_date, status),
  CONSTRAINT fk_ap_patient FOREIGN KEY (patient_id) REFERENCES users(id),
  CONSTRAINT fk_ap_dentist FOREIGN KEY (dentist_id) REFERENCES users(id),
  -- RESTRICT: không xóa được dịch vụ đã có lịch (UC013 5b - phần "chưa hoàn thành" kiểm tra thêm ở API)
  CONSTRAINT fk_ap_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------- Dữ liệu mẫu: dịch vụ ----------
INSERT INTO services (name, description, price, duration_min) VALUES
 ('Khám tổng quát',  'Khám và tư vấn tình trạng răng miệng tổng quát.', 150000, 30),
 ('Lấy cao răng',    'Làm sạch cao răng và mảng bám, đánh bóng răng.',   200000, 30),
 ('Trám răng',       'Trám răng sâu bằng vật liệu composite.',           300000, 45),
 ('Nhổ răng',        'Nhổ răng lung lay hoặc răng khôn mọc lệch.',       500000, 60),
 ('Tẩy trắng răng',  'Tẩy trắng răng tại phòng khám.',                  1500000, 60),
 ('Chỉnh nha',       'Tư vấn và theo dõi niềng răng.',                   400000, 45);

-- Lưu ý: KHÔNG tạo sẵn tài khoản Quản trị viên ở đây.
-- QTV đầu tiên được tạo qua trang setup.html bằng mã khởi tạo (SETUP_CODE trong includes/config.php).
