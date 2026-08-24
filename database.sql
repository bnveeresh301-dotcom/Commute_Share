CREATE DATABASE IF NOT EXISTS commute_share
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE commute_share;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30) DEFAULT '',
  vehicle VARCHAR(100) DEFAULT '',
  vehicle_no VARCHAR(30) DEFAULT '',
  city VARCHAR(100) DEFAULT 'Bengaluru',
  verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rides (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  driver_id INT UNSIGNED NOT NULL,
  from_location VARCHAR(150) NOT NULL,
  to_location VARCHAR(150) NOT NULL,
  ride_date DATE NOT NULL,
  departure_time TIME NOT NULL,
  seats_total INT UNSIGNED NOT NULL,
  seats_available INT UNSIGNED NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  pickup_details VARCHAR(255) DEFAULT '',
  from_lat DECIMAL(10,7) DEFAULT NULL,
  from_lng DECIMAL(10,7) DEFAULT NULL,
  to_lat DECIMAL(10,7) DEFAULT NULL,
  to_lng DECIMAL(10,7) DEFAULT NULL,
  note TEXT,
  status ENUM('active','cancelled','completed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rides_driver FOREIGN KEY(driver_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_rides_search(from_location,to_location,ride_date,status)
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ride_id INT UNSIGNED NOT NULL,
  rider_id INT UNSIGNED NOT NULL,
  seats INT UNSIGNED NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  status ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ride_rider(ride_id,rider_id),
  CONSTRAINT fk_bookings_ride FOREIGN KEY(ride_id) REFERENCES rides(id) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_rider FOREIGN KEY(rider_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE conversations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('direct','group') NOT NULL,
  name VARCHAR(150) DEFAULT NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_conversations_creator FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE conversation_members (
  conversation_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(conversation_id,user_id),
  CONSTRAINT fk_cm_conversation FOREIGN KEY(conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_cm_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_conversation FOREIGN KEY(conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_sender FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_messages_conversation(conversation_id,id)
) ENGINE=InnoDB;


CREATE TABLE ride_locations (
  ride_id INT UNSIGNED PRIMARY KEY,
  driver_id INT UNSIGNED NOT NULL,
  lat DECIMAL(10,7) NOT NULL,
  lng DECIMAL(10,7) NOT NULL,
  accuracy DECIMAL(10,2) DEFAULT NULL,
  heading DECIMAL(6,2) DEFAULT NULL,
  speed DECIMAL(8,2) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_location_ride FOREIGN KEY(ride_id) REFERENCES rides(id) ON DELETE CASCADE,
  CONSTRAINT fk_location_driver FOREIGN KEY(driver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
