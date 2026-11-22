-- CampusConnect Pro database (MySQL 5.7+/MariaDB 10.4+). Import in phpMyAdmin.
CREATE DATABASE IF NOT EXISTS campusconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campusconnect;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('student','club','admin') NOT NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME NULL,
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email_role (email, role),
  KEY idx_role (role)
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  student_code VARCHAR(30) NOT NULL UNIQUE,
  department VARCHAR(100) NOT NULL,
  batch VARCHAR(30) NOT NULL,
  semester VARCHAR(30) NOT NULL,
  phone VARCHAR(30) NULL,
  avatar VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE clubs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL UNIQUE,
  category VARCHAR(60) NULL,
  description TEXT NULL,
  logo VARCHAR(255) NULL,
  cover VARCHAR(255) NULL,
  official_email VARCHAR(150) NOT NULL UNIQUE,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE club_emails (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  email VARCHAR(150) NOT NULL,
  status ENUM('verified','pending','rejected','replaced') NOT NULL DEFAULT 'pending',
  note VARCHAR(255) NULL,
  requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  KEY idx_club (club_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE club_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  student_code VARCHAR(30) NULL,
  position ENUM('President','Vice President','General Secretary','Joint Secretary','Event Executive','Media Executive','Finance Executive','General Member') NOT NULL DEFAULT 'General Member',
  can_post TINYINT(1) NOT NULL DEFAULT 0,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_club (club_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  created_by_member_id INT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(60) NULL,
  image VARCHAR(255) NULL,
  attachment VARCHAR(255) NULL,
  event_date DATE NULL,
  event_time TIME NULL,
  event_venue VARCHAR(150) NULL,
  extra_info TEXT NULL,
  status ENUM('draft','pending','approved','rejected','published','archived') NOT NULL DEFAULT 'draft',
  rejection_reason TEXT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  published_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status), KEY idx_club (club_id), KEY idx_pub (published_at),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_member_id) REFERENCES club_members(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE post_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_approvals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  action ENUM('submitted','resubmitted','approved','rejected','archived') NOT NULL,
  actor_user_id INT UNSIGNED NOT NULL,
  reason TEXT NULL,
  internal_note TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_post (post_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body VARCHAR(1000) NOT NULL,
  status ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_post (post_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_likes (
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id, user_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_saves (
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id, user_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  comment_id INT UNSIGNED NOT NULL,
  reporter_user_id INT UNSIGNED NOT NULL,
  reason VARCHAR(255) NOT NULL,
  status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_report (comment_id, reporter_user_id),
  FOREIGN KEY (comment_id) REFERENCES post_comments(id) ON DELETE CASCADE,
  FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  poster VARCHAR(255) NULL,
  category VARCHAR(60) NULL,
  event_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  venue VARCHAR(150) NOT NULL,
  max_participants INT UNSIGNED NULL,
  registration_deadline DATETIME NOT NULL,
  rules TEXT NULL,
  requirements TEXT NULL,
  eligible_dept VARCHAR(100) NULL,
  status ENUM('draft','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'draft',
  rejection_reason TEXT NULL,
  approved_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status), KEY idx_date (event_date), KEY idx_club (club_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE event_approvals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  action ENUM('submitted','resubmitted','approved','rejected','cancelled') NOT NULL,
  actor_user_id INT UNSIGNED NOT NULL,
  reason TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  status ENUM('registered','waitlist','cancelled') NOT NULL DEFAULT 'registered',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  cancelled_at DATETIME NULL,
  UNIQUE KEY uq_reg (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE event_tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_id INT UNSIGNED NOT NULL UNIQUE,
  ticket_code VARCHAR(30) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (registration_id) REFERENCES event_registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE attendance (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT UNSIGNED NOT NULL UNIQUE,
  event_id INT UNSIGNED NOT NULL,
  scanned_by_user_id INT UNSIGNED NOT NULL,
  scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES event_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE certificates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cert_code VARCHAR(30) NOT NULL UNIQUE,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cert (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  body VARCHAR(500) NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id, is_read),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  is_emergency TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE venues (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  location VARCHAR(150) NULL,
  capacity INT UNSIGNED NULL
) ENGINE=InnoDB;

CREATE TABLE email_verifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  KEY idx_hash (token_hash),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  role VARCHAR(20) NULL,
  action VARCHAR(100) NOT NULL,
  details VARCHAR(500) NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id), KEY idx_time (created_at)
) ENGINE=InnoDB;

CREATE TABLE settings (
  k VARCHAR(60) PRIMARY KEY,
  v VARCHAR(500) NOT NULL
) ENGINE=InnoDB;

INSERT INTO settings (k,v) VALUES ('allow_student_registration','1'),('university_email_domain','');
INSERT INTO venues (name,location,capacity) VALUES ('Main Auditorium','Building A',500),('Seminar Hall','Building B',150),('Open Field','Campus Ground',1000);
SET FOREIGN_KEY_CHECKS=1;

-- v2: event feedback & ratings
CREATE TABLE IF NOT EXISTS event_feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, event_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL, comment VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_fb (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
