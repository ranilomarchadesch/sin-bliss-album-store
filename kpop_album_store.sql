USE kpop_album_store;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    age INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    record_id INT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL
);

ALTER TABLE stores
ADD COLUMN added_by INT NULL,
ADD COLUMN last_updated_by INT NULL,
ADD COLUMN last_updated TIMESTAMP NULL DEFAULT NULL
    ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE albums
ADD COLUMN added_by INT NULL,
ADD COLUMN last_updated_by INT NULL,
ADD COLUMN last_updated TIMESTAMP NULL DEFAULT NULL
    ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE stores
ADD CONSTRAINT fk_stores_added_by
FOREIGN KEY (added_by) REFERENCES users(user_id)
ON DELETE SET NULL;

ALTER TABLE stores
ADD CONSTRAINT fk_stores_updated_by
FOREIGN KEY (last_updated_by) REFERENCES users(user_id)
ON DELETE SET NULL;

ALTER TABLE albums
ADD CONSTRAINT fk_albums_added_by
FOREIGN KEY (added_by) REFERENCES users(user_id)
ON DELETE SET NULL;

ALTER TABLE albums
ADD CONSTRAINT fk_albums_updated_by
FOREIGN KEY (last_updated_by) REFERENCES users(user_id)
ON DELETE SET NULL;