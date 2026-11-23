# Etalase Digital Stella (gabungan)

Gabungan dua proyek:

| Bagian | Sumber |
|---|---|
| Tampilan (Bootstrap 5, navbar, kartu galeri, form, halaman kelola siswa) | `etalase_stella` |
| Backend (PDO + prepared statement, middleware role, 3 tabel `users`/`students`/`karya`, login username) | `etalase_stella_lkpd` |
| Popup (detail project & form tambah admin) | `etalase_stella_lkpd` |
| **Baru:** halaman *Kelola User* khusus Super Admin untuk menambah admin | – |

## Instalasi (XAMPP)
1. Salin folder `etalase_stella` ke `htdocs/`.
2. Buka phpMyAdmin → **Import** → pilih `database.sql`.
3. Cek `config/kuis_db.php` (host/user/password MySQL).
4. Buka `http://localhost/etalase_stella/auth/login.php`.

## Akun default (password: `password123`)
| Username | Role |
|---|---|
| superadmin | super_admin |
| admin | admin |
| siswa | siswa |

Segera ganti password akun default setelah instalasi.

## Hak akses
| Fitur | Siswa | Admin | Super Admin |
|---|:-:|:-:|:-:|
| Lihat galeri, tambah project | ✔ | ✔ | ✔ |
| Edit/hapus project | milik sendiri | semua | semua |
| Kelola Siswa (`admin/siswa.php`) | – | ✔ | ✔ |
| Kelola User + tambah admin (`admin/users.php`) | – | – | ✔ |

## Struktur database (3 tabel)
| Tabel | Isi |
|---|---|
| `users` | `id_users`, `nama`, `username` (unik), `password`, `role` (`super_admin`/`admin`/`siswa`) |
| `students` | `id_siswa`, `id_users` (unik, FK ke users), `nis` (unik), `nama_lengkap`, `kelas_jurusan`, `foto_profil` |
| `karya` | `id_karya`, `id_siswa` (FK ke students), `judul`, `deskripsi`, `kategori`, `gambar`, `status` (`draft`/`published`), `tanggal_upload` |

Relasi: `users 1 - 1 students 1 - N karya` (semua `ON DELETE CASCADE`).

## Catatan perubahan
- Database dipecah menjadi 3 tabel; nama database kini `etalase_stella` (di `database.sql` dan `config/kuis_db.php`).
- Login & pendaftaran memakai **username**, bukan email. Pendaftaran siswa mengisi nama, username, NIS, kelas/jurusan, dan membuat baris `users` + `students` dalam satu transaksi.
- Kolom `link_project` tidak ada lagi di tabel `karya`, jadi input & tombol link project dihapus. Sebagai gantinya ada **kategori** dan **status** (draft/published).
- Karya `published` tampil di galeri untuk semua; `draft` hanya untuk pemiliknya dan admin/super admin.
- Karya selalu terhubung ke `students`. Jika admin/super admin mengunggah karya, baris `students` otomatis dibuat untuknya.
- Leaderboard hanya menghitung siswa dan karya berstatus `published`.
- Kolom `foto_profil` sudah ada di tabel, tetapi belum ada fitur unggah fotonya.
- `reset.php` dihapus (bisa dipakai siapa saja tanpa login untuk mereset password).
- Hapus/reset lewat POST + token CSRF; upload gambar divalidasi (JPG/PNG/WEBP, maks. 2MB) saat tambah maupun edit.
- Reset password siswa oleh admin menjadi `654321` (ubah di `DEFAULT_RESET_PASSWORD`, `middleware/auth_check.php`).
