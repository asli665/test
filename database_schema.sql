CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_username VARCHAR(50) NOT NULL,
    passenger_username VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_username) REFERENCES users(username),
    FOREIGN KEY (passenger_username) REFERENCES users(username)
);
