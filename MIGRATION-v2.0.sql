-- ================================================================
-- SERSOLTEC v2.0 - DATABASE MIGRATION
-- New tables for library functionality
-- ================================================================

-- ================================================================
-- 1. LOGIN ATTEMPTS (for Auth class - account locking)
-- ================================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 2. PASSWORD RESETS (for Auth class)
-- ================================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 3. UPDATE USERS TABLE (add verification fields)
-- ================================================================

-- Check and add verification_token column
SET @query = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE users ADD COLUMN verification_token VARCHAR(64) NULL',
        'SELECT "Column verification_token already exists" AS message'
    )
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'users' 
    AND column_name = 'verification_token'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add email_verified_at column
SET @query = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL',
        'SELECT "Column email_verified_at already exists" AS message'
    )
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'users' 
    AND column_name = 'email_verified_at'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add last_login column
SET @query = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE users ADD COLUMN last_login DATETIME NULL',
        'SELECT "Column last_login already exists" AS message'
    )
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'users' 
    AND column_name = 'last_login'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for verification token (if not exists)
SET @query = (
    SELECT IF(
        COUNT(*) = 0,
        'CREATE INDEX idx_verification_token ON users(verification_token)',
        'SELECT "Index idx_verification_token already exists" AS message'
    )
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'users' 
    AND index_name = 'idx_verification_token'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================================================
-- 4. WISHLIST TABLE
-- ================================================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    INDEX idx_user (user_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 5. PRODUCT COMPARISONS TABLE
-- ================================================================
CREATE TABLE IF NOT EXISTS product_comparisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 6. PRODUCT REVIEWS TABLE
-- ================================================================
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NULL,
    author_name VARCHAR(100) NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title VARCHAR(200),
    comment TEXT,
    verified_purchase TINYINT(1) DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product_status (product_id, status),
    INDEX idx_rating (rating),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 7. BLOG POSTS TABLE
-- ================================================================
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    author_id INT NOT NULL,
    category VARCHAR(100),
    featured_image VARCHAR(255),
    status ENUM('draft', 'published') DEFAULT 'draft',
    published_at DATETIME NULL,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES admin_users(id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_published (published_at),
    FULLTEXT idx_search (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 8. BLOG COMMENTS TABLE (optional)
-- ================================================================
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NULL,
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_post (post_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- VERIFICATION QUERIES
-- ================================================================

-- Check if all tables were created
SELECT 
    'login_attempts' as table_name,
    COUNT(*) as row_count 
FROM login_attempts
UNION ALL
SELECT 'password_resets', COUNT(*) FROM password_resets
UNION ALL
SELECT 'wishlist', COUNT(*) FROM wishlist
UNION ALL
SELECT 'product_comparisons', COUNT(*) FROM product_comparisons
UNION ALL
SELECT 'product_reviews', COUNT(*) FROM product_reviews
UNION ALL
SELECT 'blog_posts', COUNT(*) FROM blog_posts
UNION ALL
SELECT 'blog_comments', COUNT(*) FROM blog_comments;

-- ================================================================
-- SAMPLE DATA (optional - uncomment to use)
-- ================================================================

/*
-- Sample blog post
INSERT INTO blog_posts (slug, title, excerpt, content, author_id, category, status, published_at) VALUES
(
    'jak-wybrac-okna-do-domu',
    'Jak wybrać idealne okna do domu?',
    'Kompleksowy poradnik wyboru okien - wszystko co musisz wiedzieć przed zakupem.',
    '<h2>Wprowadzenie</h2><p>Wybór okien to jedna z najważniejszych decyzji podczas budowy lub remontu domu...</p>',
    1,
    'Porady',
    'published',
    NOW()
);
*/

-- ================================================================
-- ROLLBACK (if needed - run manually)
-- ================================================================
/*
DROP TABLE IF EXISTS blog_comments;
DROP TABLE IF EXISTS blog_posts;
DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS product_comparisons;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS login_attempts;

ALTER TABLE users 
DROP COLUMN IF EXISTS verification_token,
DROP COLUMN IF EXISTS email_verified_at,
DROP COLUMN IF EXISTS last_login;
*/