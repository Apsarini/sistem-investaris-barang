<?php
session_start();

// Inisialisasi CSRF Token untuk keamanan form POST
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Data awal simulasi inventaris barang
if (!isset($_SESSION['inventaris'])) {
    $_SESSION['inventaris'] = [
        [
            'kode' => 'BRG-001',
            'nama' => 'Arduino Uno R3',
            'kategori' => 'Elektronik',
            'stok' => 15,
            'harga' => 125000
        ],
        [
            'kode' => 'BRG-002',
            'nama' => 'Kabel Jumper Male-Female',
            'kategori' => 'Aksesoris',
            'stok' => 4,
            'harga' => 18000
        ]
    ];
}

// Pemrosesan Form POST + PRG Pattern (Post-Redirect-Get)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_barang'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Akses ditolak: Token CSRF tidak valid!");
    }

    $kode = trim($_POST['kode'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $stok_raw = $_POST['stok'] ?? '';
    $harga_raw = $_POST['harga'] ?? '';

    $errors = [];

    if (strlen($kode) < 3) {
        $errors[] = "Kode barang minimal 3 karakter.";
    }
    if (strlen($nama) < 2) {
        $errors[] = "Nama barang wajib diisi minimal 2 karakter.";
    }
    if (!in_array($kategori, ['Elektronik', 'Aksesoris', 'Alat Tulis'], true)) {
        $errors[] = "Kategori barang tidak valid.";
    }

    $stok = filter_var($stok_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($stok === false) {
        $errors[] = "Stok harus berupa bilangan bulat minimal 0.";
    }

    $harga = filter_var($harga_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 100]]);
    if ($harga === false) {
        $errors[] = "Harga harus berupa bilangan bulat minimal Rp 100.";
    }

    if (empty($errors)) {
        $_SESSION['inventaris'][] = [
            'kode' => $kode,
            'nama' => $nama,
            'kategori' => $kategori,
            'stok' => $stok,
            'harga' => $harga
        ];
        $_SESSION['flash_success'] = "Barang <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong> (" . htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') . ") berhasil disimpan!";
    } else {
        $_SESSION['flash_errors'] = $errors;
        $_SESSION['old'] = [
            'kode' => $kode,
            'nama' => $nama,
            'kategori' => $kategori,
            'stok' => $stok_raw,
            'harga' => $harga_raw
        ];
    }

    header("Location: index.php");
    exit;
}

// Ambil Flash Message & Sticky Data lalu hapus agar tidak muncul berulang
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_errors = $_SESSION['flash_errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['flash_success'], $_SESSION['flash_errors'], $_SESSION['old']);

// Pemrosesan Form GET (Pencarian & Filter Kategori)
$keyword = trim($_GET['cari'] ?? '');
$filter_kategori = trim($_GET['filter_kategori'] ?? '');

$data_tampil = array_filter($_SESSION['inventaris'], function ($item) use ($keyword, $filter_kategori) {
    $cocok_nama = $keyword === '' || stripos($item['nama'], $keyword) !== false || stripos($item['kode'], $keyword) !== false;
    $cocok_kategori = $filter_kategori === '' || $item['kategori'] === $filter_kategori;
    return $cocok_nama && $cocok_kategori;
});

// Fungsi bantu sanitasi XSS
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Data Interaktif - Inventaris Barang</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-flex">
            <div>
                <h1>Sistem Inventaris Barang</h1>
                <p>Sistem Manajemen Data Interaktif &bull; Pemrograman Web 1</p>
            </div>
            <button type="button" id="theme-toggle" class="btn-secondary">Mode Gelap</button>
        </div>
    </header>

    <nav class="site-nav" aria-label="Navigasi Utama">
        <div class="container nav-flex">
            <a href="#form-input">Input Barang (POST)</a>
            <a href="#daftar-inventaris">Daftar &amp; Cari Barang (GET)</a>
        </div>
    </nav>

    <main class="container main-grid">
        <section id="form-input" class="card">
            <h2>Input Data Barang Baru</h2>
            <p class="subtitle">Metode <strong>POST</strong> dengan proteksi CSRF, Validasi Server-Side, &amp; PRG Pattern.</p>

            <?php if ($flash_success !== ''): ?>
                <div class="alert success" role="status"><?= $flash_success ?></div>
            <?php endif; ?>

            <?php if (!empty($flash_errors)): ?>
                <div class="alert danger" role="alert">
                    <ul>
                        <?php foreach ($flash_errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="inventory-form" action="index.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="kode">Kode Barang</label>
                    <input type="text" id="kode" name="kode" value="<?= e($old['kode'] ?? '') ?>" placeholder="Contoh: BRG-003" required>
                    <small class="error-msg" id="err-kode"></small>
                </div>

                <div class="form-group">
                    <label for="nama">Nama Barang</label>
                    <input type="text" id="nama" name="nama" value="<?= e($old['nama'] ?? '') ?>" placeholder="Masukkan nama barang" required>
                    <small class="error-msg" id="err-nama"></small>
                </div>

                <div class="form-group">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Elektronik" <?= ($old['kategori'] ?? '') === 'Elektronik' ? 'selected' : '' ?>>Elektronik</option>
                        <option value="Aksesoris" <?= ($old['kategori'] ?? '') === 'Aksesoris' ? 'selected' : '' ?>>Aksesoris</option>
                        <option value="Alat Tulis" <?= ($old['kategori'] ?? '') === 'Alat Tulis' ? 'selected' : '' ?>>Alat Tulis</option>
                    </select>
                    <small class="error-msg" id="err-kategori"></small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="stok">Jumlah Stok</label>
                        <input type="number" id="stok" name="stok" value="<?= e((string)($old['stok'] ?? '')) ?>" min="0" placeholder="0" required>
                        <small class="error-msg" id="err-stok"></small>
                    </div>

                    <div class="form-group">
                        <label for="harga">Harga Satuan (Rp)</label>
                        <input type="number" id="harga" name="harga" value="<?= e((string)($old['harga'] ?? '')) ?>" min="100" placeholder="10000" required>
                        <small class="error-msg" id="err-harga"></small>
                    </div>
                </div>

                <button type="submit" name="tambah_barang" class="btn-primary">Simpan ke Inventaris</button>
            </form>
        </section>

        <section id="daftar-inventaris" class="card">
            <h2>Daftar Stok Inventaris</h2>
            <p class="subtitle">Pencarian &amp; filter menggunakan metode <strong>GET</strong> (Query String di URL).</p>

            <form action="index.php#daftar-inventaris" method="GET" class="filter-bar">
                <input type="text" name="cari" value="<?= e($keyword) ?>" placeholder="Cari kode atau nama barang...">
                <select name="filter_kategori">
                    <option value="">Semua Kategori</option>
                    <option value="Elektronik" <?= $filter_kategori === 'Elektronik' ? 'selected' : '' ?>>Elektronik</option>
                    <option value="Aksesoris" <?= $filter_kategori === 'Aksesoris' ? 'selected' : '' ?>>Aksesoris</option>
                    <option value="Alat Tulis" <?= $filter_kategori === 'Alat Tulis' ? 'selected' : '' ?>>Alat Tulis</option>
                </select>
                <button type="submit" class="btn-primary">Cari</button>
                <?php if ($keyword !== '' || $filter_kategori !== ''): ?>
                    <a href="index.php#daftar-inventaris" class="btn-reset">Reset</a>
                <?php endif; ?>
            </form>

            <div class="inventory-list">
                <?php if (empty($data_tampil)): ?>
                    <p class="empty-state">Tidak ada data barang yang sesuai dengan pencarian.</p>
                <?php else: ?>
                    <?php foreach ($data_tampil as $barang): ?>
                        <article class="item-card">
                            <div class="item-header">
                                <span class="badge-code"><?= e($barang['kode']) ?></span>
                                <span class="badge-category"><?= e($barang['kategori']) ?></span>
                            </div>
                            <h3><?= e($barang['nama']) ?></h3>
                            <div class="item-meta">
                                <span>Harga: <strong>Rp <?= number_format($barang['harga'], 0, ',', '.') ?></strong></span>
                                <span class="stok-label <?= $barang['stok'] <= 5 ? 'stok-rendah' : '' ?>">
                                    Stok: <?= $barang['stok'] ?> unit <?= $barang['stok'] <= 5 ? '(Menipis)' : '' ?>
                             </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; 2026 &bull; Apsarini Syifa Ardiningrum &bull; UTS Pemrograman Web 1 &bull; Universitas Adhirajasa Reswara Sanjaya</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>