CERATE DATABASE futsal_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('Admin', 'User') NOT NULL DEFAULT 'User',
  profile_image VARCHAR(255) DEFAULT 'https://placehold.co/200x200',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lapangan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  lokasi VARCHAR(150) NOT NULL,
  harga INT NOT NULL,
  status ENUM('Tersedia', 'Dibooking', 'Dipakai') NOT NULL DEFAULT 'Tersedia',
  gambar VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pemesanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  lapangan_id INT NOT NULL,
  tanggal DATE NOT NULL,
  waktu_mulai TIME NOT NULL,
  waktu_selesai TIME NOT NULL,
  total_jam INT NOT NULL DEFAULT 0,
  total_harga INT NOT NULL,
  status ENUM('Dibooking', 'Dipakai', 'Selesai') DEFAULT 'Dibooking',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (lapangan_id) REFERENCES lapangan(id)
);


