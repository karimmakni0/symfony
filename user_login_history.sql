-- Create user_login_history table for tracking logins

CREATE TABLE user_login_history (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    device_type VARCHAR(50) DEFAULT NULL,
    platform VARCHAR(50) DEFAULT NULL,
    browser VARCHAR(50) DEFAULT NULL,
    login_time DATETIME NOT NULL,
    PRIMARY KEY(id),
    INDEX IDX_LOGIN_USER (user_id),
    CONSTRAINT FK_LOGIN_USER_ID FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
