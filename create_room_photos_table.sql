-- Create room_photos table for hotel room image management
CREATE TABLE room_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    photo_name VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Create index for better performance
CREATE INDEX idx_room_photos_room_id ON room_photos(room_id);
CREATE INDEX idx_room_photos_primary ON room_photos(room_id, is_primary);