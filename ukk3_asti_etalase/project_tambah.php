<?php
require_once 'config/kuis_db.php';
require_once 'middleware/auth_check.php';
require_once 'includes/helpers.php';

check_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kategori  = trim($_POST['kategori'] ?? '');
    $status    = $_POST['status'] ?? 'published';

    if ($judul === '' || $deskripsi === '' || $kategori === '') {
        $error = "Semua kolom input wajib diisi!";
    } elseif (mb_strlen($kategori) > 50) {
        $error = "Kategori maksimal 50 karakter!";
    } elseif (!in_array($status, ['draft', 'published'], true)) {
        $error = "Status tidak valid!";
    } else {
        $gambar = save_project_image($_FILES['gambar'] ?? [], UPLOAD_DIR, $error);

        if ($gambar !== null) {
            try {
                $idSiswa = get_student_id($pdo, (int) $_SESSION['user_id']);
                $stmt = $pdo->prepare("INSERT INTO karya (id_siswa, judul, deskripsi, kategori, gambar, status, tanggal_upload)
                                       VALUES (:id_siswa, :judul, :deskripsi, :kategori, :gambar, :status, NOW())");
                $stmt->execute([
                    ':id_siswa'  => $idSiswa,
                    ':judul'     => $judul,
                    ':deskripsi' => $deskripsi,
                    ':kategori'  => $kategori,
                    ':gambar'    => $gambar,
                    ':status'    => $status,
                ]);
                set_flash('success', 'Karya berhasil ditambahkan.');
                header('Location: index.php');
                exit();
            } catch (PDOException $ex) {
                delete_project_image(UPLOAD_DIR, $gambar);
                $error = "Terjadi kesalahan sistem saat menyimpan karya.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Project - Stella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card card-form p-4">
                <h3 class="fw-bold mb-4"><i class="bi bi-file-earmark-plus text-success"></i> Tambah Project Baru</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Project</label>
                        <input type="text" name="judul" class="form-control" maxlength="150" placeholder="Contoh: Aplikasi Kasir Cafe Stella" value="<?= e($_POST['judul'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Project</label>
                        <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan fitur utama dan teknologi yang digunakan..." required><?= e($_POST['deskripsi'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Gambar (Preview / Mockup, maks. 2MB)</label>
                        <input type="file" id="gambar" name="gambar" class="form-control" accept="image/png, image/jpeg, image/webp" required>
                        <div class="preview-box" id="previewBox">
                            <p class="small text-muted mb-1">Pratinjau Gambar:</p>
                            <img id="imagePreview" src="" alt="Preview Gambar">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <input type="text" name="kategori" class="form-control" maxlength="50" list="daftarKategori" placeholder="Pilih atau ketik kategori" value="<?= e($_POST['kategori'] ?? '') ?>" required>
                        <datalist id="daftarKategori">
                            <?php foreach (kategori_karya() as $kat): ?><option value="<?= e($kat) ?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status</label>
                        <?php $stSel = $_POST['status'] ?? 'published'; ?>
                        <select name="status" class="form-select">
                            <option value="published" <?= $stSel === 'published' ? 'selected' : '' ?>>Published &ndash; tampil di galeri</option>
                            <option value="draft" <?= $stSel === 'draft' ? 'selected' : '' ?>>Draft &ndash; hanya terlihat oleh saya &amp; admin</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-lg"></i> Simpan Project</button>
                        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('gambar').addEventListener('change', function () {
    var box = document.getElementById('previewBox');
    var img = document.getElementById('imagePreview');
    var file = this.files[0];
    if (!file) { img.removeAttribute('src'); box.style.display = 'none'; return; }
    var reader = new FileReader();
    reader.addEventListener('load', function () { img.src = reader.result; box.style.display = 'block'; });
    reader.readAsDataURL(file);
});
</script>

</body>
</html>
