<?php
/** Daftar saran kategori karya (kolom karya.kategori bebas diisi, maks. 50 karakter). */
function kategori_karya(): array {
    return ['Web', 'Mobile', 'Desain Grafis', 'UI/UX', 'Game', 'Animasi', 'Fotografi', 'Lainnya'];
}

/** Username: huruf, angka, titik, underscore; 3-50 karakter. */
function valid_username(string $username): bool {
    return (bool) preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username);
}

/**
 * Validasi & simpan gambar karya. Mengembalikan nama file baru,
 * atau null (dan mengisi $error) jika gagal.
 */
function save_project_image(array $file, string $uploadDir, ?string &$error): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = "Pilih gambar karya digital terlebih dahulu!";
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $error = "Ukuran berkas gambar tidak boleh lebih dari 2MB!";
        return null;
    }

    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $info    = @getimagesize($file['tmp_name']);
    if ($info === false || !isset($allowed[$info[2]])) {
        $error = "Format gambar harus berupa JPG, JPEG, PNG, atau WEBP!";
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newName = uniqid('project_', true) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], rtrim($uploadDir, '/\\') . '/' . $newName)) {
        $error = "Gagal mengunggah berkas gambar ke server.";
        return null;
    }
    return $newName;
}

function delete_project_image(string $uploadDir, string $fileName): void {
    $path = rtrim($uploadDir, '/\\') . '/' . basename($fileName);
    if ($fileName !== '' && is_file($path)) {
        unlink($path);
    }
}

/**
 * Buat akun di tabel users. Jika $student diisi (nis, kelas_jurusan),
 * baris profil di tabel students dibuat sekaligus dalam satu transaksi.
 * Mengembalikan id_users.
 */
function create_user(PDO $pdo, string $nama, string $username, string $password, string $role, ?array $student = null): int {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role)
                               VALUES (:nama, :username, :password, :role)");
        $stmt->execute([
            ':nama'     => $nama,
            ':username' => $username,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':role'     => $role,
        ]);
        $idUsers = (int) $pdo->lastInsertId();

        if ($student !== null) {
            $st = $pdo->prepare("INSERT INTO students (id_users, nis, nama_lengkap, kelas_jurusan)
                                 VALUES (:id_users, :nis, :nama, :kelas)");
            $st->execute([
                ':id_users' => $idUsers,
                ':nis'      => $student['nis'],
                ':nama'     => $nama,
                ':kelas'    => $student['kelas_jurusan'],
            ]);
        }
        $pdo->commit();
        return $idUsers;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Ambil id_siswa milik sebuah user. Karya selalu terhubung ke tabel students,
 * jadi jika user (mis. admin) belum punya profil, baris students dibuat otomatis.
 */
function get_student_id(PDO $pdo, int $idUsers): int {
    $stmt = $pdo->prepare("SELECT id_siswa FROM students WHERE id_users = :id");
    $stmt->execute([':id' => $idUsers]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int) $id;
    }

    $u = $pdo->prepare("SELECT nama FROM users WHERE id_users = :id");
    $u->execute([':id' => $idUsers]);
    $nama = (string) $u->fetchColumn();

    $ins = $pdo->prepare("INSERT INTO students (id_users, nama_lengkap) VALUES (:id, :nama)");
    $ins->execute([':id' => $idUsers, ':nama' => $nama]);
    return (int) $pdo->lastInsertId();
}

/**
 * Hapus user (hanya jika rolenya ada di $allowedRoles) beserta file gambar karya miliknya.
 * Baris students & karya ikut terhapus lewat ON DELETE CASCADE.
 */
function delete_user_with_files(PDO $pdo, int $id, array $allowedRoles): bool {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id_users = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if (!$user || !in_array($user['role'], $allowedRoles, true)) {
        return false;
    }

    $imgs = $pdo->prepare("SELECT k.gambar
                           FROM karya k
                           JOIN students s ON k.id_siswa = s.id_siswa
                           WHERE s.id_users = :id");
    $imgs->execute([':id' => $id]);
    $files = $imgs->fetchAll(PDO::FETCH_COLUMN);

    $del = $pdo->prepare("DELETE FROM users WHERE id_users = :id");
    $del->execute([':id' => $id]);

    foreach ($files as $f) {
        delete_project_image(UPLOAD_DIR, (string) $f);
    }
    return true;
}

/** True jika error PDO adalah duplikat (username / NIS sudah terdaftar). */
function is_duplicate_error(PDOException $e): bool {
    return ($e->errorInfo[1] ?? 0) === 1062;
}

/** Pesan duplikat yang spesifik (username atau NIS). */
function duplicate_message(PDOException $e): string {
    $msg = (string) ($e->errorInfo[2] ?? '');
    if (preg_match("/for key '([^']+)'/", $msg, $m)) {
        $key = strtolower(substr(strrchr('.' . $m[1], '.'), 1));
        if ($key === 'nis') {
            return "NIS sudah terdaftar!";
        }
        if ($key === 'username') {
            return "Username sudah terdaftar!";
        }
    }
    return "Data sudah terdaftar!";
}
