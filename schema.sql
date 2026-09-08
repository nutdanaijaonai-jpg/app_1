-- LANE 3 database schema (MySQL / MariaDB)
-- Import with: mysql -u root -p lane3 < schema.sql

CREATE DATABASE IF NOT EXISTS lane3 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lane3;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  brand ENUM('nike','adidas') NOT NULL,
  category VARCHAR(80) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  description TEXT,
  image_url VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id VARCHAR(30) PRIMARY KEY,
  user_id INT NULL,
  customer_name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  address TEXT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  shipping DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(20) NOT NULL,
  status ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(30) NOT NULL,
  product_id INT NOT NULL,
  name VARCHAR(160) NOT NULL,
  brand VARCHAR(20) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  qty INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Seed admin account -> username: admin / password: admin123
INSERT INTO users (name, email, username, password_hash, role) VALUES
('Store Admin', 'admin@lane3.demo', 'admin', '$2y$10$yVnBa0GNsYDIFdMaCopbE.5B.4aukZUM7KXiILa.16I7I0ORTdgoO', 'admin')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed products with real images
INSERT INTO products (name, brand, category, price, stock, description, image_url) VALUES
('Nike Dri-FIT Running Tee', 'nike', 'เสื้อยืด', 1290, 24, 'เสื้อวิ่งผ้า Dri-FIT ระบายเหงื่อไว น้ำหนักเบา ใส่สบายทุกสภาพอากาศ', 'https://www.jdsports.co.th/cdn/shop/files/jd_IF2083-010_a.jpg?v=1777212393&width=1007'),
('Nike Tech Fleece Hoodie', 'nike', 'เสื้อฮู้ด/แจ็คเก็ต', 2990, 12, 'ฮู้ดผ้า Tech Fleece ให้ความอุ่นโดยไม่หนัก ดีไซน์เพรียวบาง', 'https://static.nike.com/a/images/t_web_pdp_535_v2/f_auto/dbcb43d1-d508-4275-b239-f988667d5001/AS+M+NK+TCH+FLC+FZ+WR+HOODIE.png'),
('Nike Sportswear Shorts', 'nike', 'กางเกง', 990, 30, 'กางเกงขาสั้นทรงคลาสสิก ผ้าถักนุ่ม ใส่ได้ทั้งออกกำลังกายและลำลอง', 'https://i.ebayimg.com/images/g/VLEAAOSw2zRkDnxA/s-l1600.webp'),
('Nike Windrunner Jacket', 'nike', 'เสื้อฮู้ด/แจ็คเก็ต', 3490, 8, 'แจ็คเก็ตกันลมลายไอคอนิก ระบายอากาศดี พับเก็บง่าย', 'https://static.nike.com/a/images/t_web_pdp_535_v2/f_auto,u_9ddf04c7-2a9a-4d76-add1-d15af8f0263d,c_scale,fl_relative,w_1.0,h_1.0,fl_layer_apply/743c31f2-94fa-4885-b386-30953c1c4943/AS+M+NK+DF+TCH+WVN+WR+FZ+JKT.png'),
('Nike Everyday Crew Socks (3คู่)', 'nike', 'ถุงเท้า/แอคเซสซอรี่', 490, 50, 'ถุงเท้ากีฬาคุณภาพสูง เนื้อผ้าหนานุ่ม เซ็ต 3 คู่', 'https://static.nike.com/a/images/t_web_pw_592_v2/f_auto/u_9ddf04c7-2a9a-4d76-add1-d15af8f0263d,c_scale,fl_relative,w_1.0,h_1.0,fl_layer_apply/4256bf42-ee37-4c45-9c5d-a2caafd5ace3/Y+NK+EVERYDY+CUSH+CREW+6PR+108.png'),
('Adidas Essentials 3-Stripes Tee', 'adidas', 'เสื้อยืด', 1190, 20, 'เสื้อยืดลาย 3 แถบสัญลักษณ์ ผ้าคอตตอนใส่สบาย', 'https://assets.adidas.com/images/h_2000,f_auto,q_auto,fl_lossy,c_fill,g_auto/86390f8e4a0d482897ffaf000092c1d6_9366/Train_Essentials_3-Stripes_Training_Tee_Black_IB8150_01_laydown.jpg'),
('Adidas Tiro Track Jacket', 'adidas', 'เสื้อฮู้ด/แจ็คเก็ต', 2490, 15, 'แจ็คเก็ตแทร็คสูทคลาสสิก ซิปเต็มตัว ใส่ซ้อนได้ทุกฤดู', 'https://assets.adidas.com/images/h_2000,f_auto,q_auto,fl_lossy,c_fill,g_auto/586616f5d3224f3dbcac00433722d4b7_9366/Adizero_Essentials_Running_Jacket_Black_IT7585_HM1.jpg'),
('Adidas Own The Run Tights', 'adidas', 'กางเกง', 1590, 18, 'เลกกิ้งวิ่งกระชับกล้ามเนื้อ ผ้ายืดหยุ่นสูง แห้งไว', 'https://assets.adidas.com/images/h_2000,f_auto,q_auto,fl_lossy,c_fill,g_auto/04619dfaf2c44413a59eb65341c0427c_9366/adi365_Running_Essentials_Tights_Black_JY5495_21_model.jpg'),
('Adidas Firebird Track Pants', 'adidas', 'กางเกง', 2190, 10, 'กางเกงแทร็คแบบซิปข้าง ลายคลาสสิกยุค 90s', 'https://assets.adidas.com/images/h_2000,f_auto,q_auto,fl_lossy,c_fill,g_auto/1651f914a4f0430490ed636fb21d1cf6_9366/Firebird_Track_Pants_Black_KD8315_21_model.jpg'),
('Adidas Crew Socks (3คู่)', 'adidas', 'ถุงเท้า/แอคเซสซอรี่', 450, 0, 'ถุงเท้าลาย 3 แถบ เนื้อนุ่ม ระบายอากาศดี', 'https://assets.adidas.com/images/h_2000,f_auto,q_auto,fl_lossy,c_fill,g_auto/28f94ed29bee428695c887366a74c0af_9366/Unisex_Linear_2-Pack_Crew_Black_KD8392_01_01_00_standard.jpg'),
('Nike Club Fleece Joggers', 'nike', 'กางเกง', 1890, 16, 'กางเกงจ๊อกเกอร์ผ้าฟลีซนุ่ม ขอบขาปลายรัด', 'https://static.nike.com/a/images/t_web_pw_592_v2/f_auto/2fc93d19-819c-4f2b-95a0-10b706a12b53/G+NSW+CLUB+FLC+LOOSE+PANT+LBR.png'),
('Adidas Adicolor Hoodie', 'adidas', 'เสื้อฮู้ด/แจ็คเก็ต', 2790, 9, 'ฮู้ดผ้าหนานุ่ม ทรง oversized ลาย Trefoil', 'https://assets.adidas.com/images/h_2000,f_auto,q_auto,fl_lossy,c_fill,g_auto/fc0b5ffee6d34e67a073aefc01259b3c_9366/Adicolor_Hoodie_Blue_IC3144_01_laydown.jpg');
