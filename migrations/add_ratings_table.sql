-- Host Ratings Table
-- Stores ratings and reviews given by evacuees to hosts upon checkout

CREATE TABLE IF NOT EXISTS host_ratings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    occupant_id INT NOT NULL,
    user_id     INT NOT NULL COMMENT 'The evacuee who submitted the rating',
    host_id     INT NOT NULL COMMENT 'The host being rated',
    shelter_id  INT NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (occupant_id) REFERENCES occupants(occupant_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(user_id)         ON DELETE CASCADE,
    FOREIGN KEY (host_id)     REFERENCES users(user_id)         ON DELETE CASCADE,
    FOREIGN KEY (shelter_id)  REFERENCES shelter(shelter_id)     ON DELETE CASCADE,

    UNIQUE KEY unique_occupant_rating (occupant_id),
    INDEX idx_host_id (host_id),
    INDEX idx_shelter_id (shelter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
