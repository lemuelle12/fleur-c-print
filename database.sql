

CREATE DATABASE IF NOT EXISTS fleur_c_print CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fleur_c_print;


CREATE TABLE customers (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  phone         VARCHAR(20),
  email         VARCHAR(100),
  order_count   INT DEFAULT 0,
  total_spent   DECIMAL(10,2) DEFAULT 0.00,
  notes         TEXT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE services (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  slug          VARCHAR(50) UNIQUE,
  name          VARCHAR(100) NOT NULL,
  base_price    DECIMAL(10,2) NOT NULL,
  unit_label    VARCHAR(30),
  est_minutes   INT DEFAULT 15,
  is_active     TINYINT(1) DEFAULT 1,
  sort_order    INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE orders (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  ref_code        VARCHAR(20) UNIQUE NOT NULL,
  customer_id     INT,
  customer_name   VARCHAR(100) NOT NULL,
  customer_phone  VARCHAR(20),
  service_id      INT,
  service_name    VARCHAR(100) NOT NULL,
  quantity        INT DEFAULT 1,
  paper_size      VARCHAR(30),
  total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status          ENUM('pending','in-progress','ready','completed','cancelled') DEFAULT 'pending',
  payment_status  ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
  payment_method  ENUM('Cash','GCash','') DEFAULT '',
  paid_amount     DECIMAL(10,2) DEFAULT 0.00,
  gcash_ref       VARCHAR(50) DEFAULT '',
  notes           TEXT,
  notified        TINYINT(1) DEFAULT 0,
  started_at      DATETIME NULL,
  completed_at    DATETIME NULL,
  actual_minutes  INT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (service_id)  REFERENCES services(id)  ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE order_files (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  order_id      INT NOT NULL,
  file_name     VARCHAR(255) NOT NULL,       -- original display name
  file_path     VARCHAR(255) NOT NULL,       -- UUID-based stored name
  file_size     INT DEFAULT 0,
  mime_type     VARCHAR(100),
  uploaded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE payment_log (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  order_id      INT NOT NULL,
  amount        DECIMAL(10,2) NOT NULL,
  method        ENUM('Cash','GCash'),
  gcash_ref     VARCHAR(50),
  recorded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;


INSERT INTO services (slug, name, base_price, unit_label, est_minutes, is_active, sort_order) VALUES
  ('doc_bw',      'Document Printing (B&W)',   3.00,   'per page',     5,  1, 1),
  ('doc_color',   'Document Printing (Color)', 8.00,   'per page',     5,  1, 2),
  ('photo_1x1',   '1x1 ID Photo',             15.00,   'per piece',   10,  1, 3),
  ('photo_2x2',   '2x2 ID Photo',             15.00,   'per piece',   10,  1, 4),
  ('brochure',    'Brochure Printing',         15.00,   'per piece',   15,  1, 5),
  ('tarpaulin',   'Tarpaulin Print',          450.00,   'per sq. meter',60, 1, 6),
  ('sticker',     'Sticker Printing',           4.80,   'per piece',   20,  1, 7),
  ('lamination',  'Lamination',               20.00,   'per page',     5,  1, 8);


INSERT INTO customers (name, phone, email, order_count, total_spent, notes) VALUES
  ('Maria Santos', '09171234567', 'maria@email.com', 4, 2400.00, 'Prefers matte finish'),
  ('Jose Reyes',   '09281234567', '',                2,  370.00, ''),
  ('Ana Cruz',     '09331234567', 'ana@email.com',   1,  550.00, 'School events only'),
  ('Pedro Lim',    '09091234567', '',                7, 1890.00, 'Always pays cash');

INSERT INTO orders (ref_code, customer_id, customer_name, customer_phone, service_id, service_name, quantity, paper_size, total_amount, status, payment_status, payment_method, paid_amount, notes) VALUES
  ('PRNT-2026-0001', 1, 'Maria Santos', '09171234567', 5, 'Brochure Printing',         50, 'A4',         750.00, 'ready',       'unpaid',  '',       0.00,   'Full color, matte finish'),
  ('PRNT-2026-0002', 2, 'Jose Reyes',   '09281234567', 3, '1x1 ID Photo',               8, '4R',         120.00, 'in-progress', 'paid',    'GCash',  120.00, 'White background'),
  ('PRNT-2026-0003', 3, 'Ana Cruz',     '09331234567', 6, 'Tarpaulin Print',             1, 'Custom',     550.00, 'pending',     'partial', 'Cash',   200.00, '3x5 feet, school banner'),
  ('PRNT-2026-0004', 4, 'Pedro Lim',    '09091234567', 1, 'Document Printing (B&W)',    30, 'Long (Legal)',90.00, 'completed',   'paid',    'Cash',    90.00, '');
