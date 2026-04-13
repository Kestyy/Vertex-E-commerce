-- Schema updates for Vertex (run once after importing initial schema)

-- 1) Add role + verification fields to users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS verification_code VARCHAR(10),
  ADD COLUMN IF NOT EXISTS verification_expires_at DATETIME,
  ADD COLUMN IF NOT EXISTS last_verification_sent_at DATETIME,
  ADD COLUMN IF NOT EXISTS email_verified_at DATETIME,
  ADD COLUMN IF NOT EXISTS avatar VARCHAR(255),
  ADD COLUMN IF NOT EXISTS full_name VARCHAR(100),
  ADD COLUMN IF NOT EXISTS gender VARCHAR(20);

-- 2) Add status & updated_at to products (if missing)
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS sale_price DECIMAL(10,2),
  ADD COLUMN IF NOT EXISTS discount_percentage INT DEFAULT 0;

-- 3) Add wishlist table for saved favorites
CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);
