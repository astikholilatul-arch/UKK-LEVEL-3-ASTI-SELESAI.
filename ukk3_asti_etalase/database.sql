-- Etalase Digital Stella
CREATE DATABASE IF NOT EXISTS db_etalase_stella
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_etalase_stella;

CREATE TABLE IF NOT EXISTS users (
    id_users INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'siswa') DEFAULT 'siswa'
);

CREATE TABLE IF NOT EXISTS students (
    id_siswa INT AUTO_INCREMENT PRIMARY KEY,
    id_users INT UNIQUE,
    nis VARCHAR(30) UNIQUE,
    nama_lengkap VARCHAR(100),
    kelas_jurusan VARCHAR(50),
    foto_profil VARCHAR(255) NULL,
    FOREIGN KEY (id_users) REFERENCES users(id_users) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS karya (
    id_karya INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    kategori VARCHAR(50),
    gambar VARCHAR(255), -- lokasi file gambar karya
    status ENUM('draft', 'published') DEFAULT 'draft',
    tanggal_upload DATETIME,
    FOREIGN KEY (id_siswa) REFERENCES students(id_siswa) ON DELETE CASCADE
);

-- Akun default (password semuanya: password123) -- segera ganti setelah login pertama
INSERT IGNORE INTO users (nama, username, password, role) VALUES
('Super Admin Stella', 'superadmin', '$2y$10$7C8r/agxCXnosP8mnSlZbOsWoUaW06UUJtuIFkUHuShVNBJ2pz0S.', 'super_admin'),
('Admin Stella',       'admin',      '$2y$10$7C8r/agxCXnosP8mnSlZbOsWoUaW06UUJtuIFkUHuShVNBJ2pz0S.', 'admin'),
('Siswa Contoh',       'siswa',      '$2y$10$7C8r/agxCXnosP8mnSlZbOsWoUaW06UUJtuIFkUHuShVNBJ2pz0S.', 'siswa');

-- Profil siswa untuk akun "siswa" contoh
INSERT IGNORE INTO students (id_users, nis, nama_lengkap, kelas_jurusan)
SELECT id_users, '0000000001', 'Siswa Contoh', 'XII RPL 1' FROM users WHERE username = 'siswa';
