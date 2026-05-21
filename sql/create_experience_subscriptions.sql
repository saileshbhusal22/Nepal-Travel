-- Experience section subscription (5 free posts, then paid)
-- Run once in nepal_travel database

CREATE TABLE IF NOT EXISTS experience_subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    duration_days INT NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_experience_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('pending','active','cancelled','expired') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(30) DEFAULT NULL,
    payment_ref VARCHAR(255) DEFAULT NULL,
    amount_paid DECIMAL(10,2) DEFAULT NULL,
    starts_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME DEFAULT NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_expires (expires_at)
);

INSERT INTO experience_subscription_plans (name, display_name, description, price, duration_days, is_active)
SELECT * FROM (
    SELECT 'monthly' AS name, 'Monthly Creator' AS display_name,
           'Unlimited experience posts for 30 days after your 5 free posts.' AS description,
           499.00 AS price, 30 AS duration_days, 1 AS is_active
    UNION ALL
    SELECT 'yearly', 'Yearly Creator',
           'Unlimited experience posts for 1 year — best value for active travelers.',
           3999.00, 365, 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM experience_subscription_plans LIMIT 1);
