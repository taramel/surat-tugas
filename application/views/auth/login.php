<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - Surat Tugas</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/app.css') ?>">
</head>
<body class="halaman-login">
<?php $this->load->view('layout/ikon'); ?>
<div class="login-rangka">
    <aside class="login-merek" aria-hidden="false">
        <div class="login-merek-isi">
            <div class="login-instansi">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="">
                <div><strong>Badan Pusat Statistik</strong><span>Provinsi Kalimantan Barat</span></div>
            </div>
            <h2>Sistem Pengelolaan<br>Surat Tugas</h2>
            <p>Penomoran, penerbitan, dan pengarsipan surat tugas dalam satu aplikasi yang terstruktur.</p>
            <ul class="login-fitur">
                <li><?= ikon('nomor') ?><span><strong>Penomoran otomatis</strong>Nomor surat tidak akan ganda.</span></li>
                <li><?= ikon('surat') ?><span><strong>Surat siap cetak</strong>Unduh PDF resmi atau dokumen Word.</span></li>
                <li><?= ikon('register') ?><span><strong>Register terpusat</strong>Seluruh riwayat surat tercatat rapi.</span></li>
            </ul>
        </div>
        <div class="login-merek-kaki">&copy; <?= date('Y') ?> BPS Provinsi Kalimantan Barat</div>
    </aside>

    <main class="login-sisi">
        <div class="login-kartu">
            <img class="login-logo-kecil" src="<?= base_url('assets/img/logo.png') ?>" alt="">
            <h1>Masuk ke akun Anda</h1>
            <p class="muted">Gunakan username dan kata sandi yang diberikan administrator.</p>
            <?php if ($error): ?><div class="flash dengan-ikon flash-bad" role="alert"><?= ikon('peringatan') ?><div class="flash-isi"><?= e($error) ?></div></div><?php endif; ?>
            <form method="post" action="<?= site_url('login') ?>" class="form">
                <?= csrf_field() ?>
                <label class="field"><span>Username</span>
                    <span class="input-ikon"><?= ikon('pegawai') ?>
                    <input type="text" name="username" autocomplete="username" placeholder="Masukkan username" required autofocus></span></label>
                <label class="field"><span>Kata sandi</span>
                    <span class="input-ikon"><?= ikon('kunci') ?>
                    <input type="password" name="password" id="login-sandi" autocomplete="current-password" placeholder="Masukkan kata sandi" required>
                    <button type="button" class="tombol-lihat" id="login-lihat" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi" aria-pressed="false"><?= ikon('lihat') ?></button></span></label>
                <button class="btn btn-primary btn-blok btn-besar" type="submit">Masuk</button>
            </form>
            <p class="masuk-kaki">Hanya untuk pegawai yang berwenang.</p>
        </div>
    </main>
</div>
<script src="<?= base_url('assets/js/ui.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/ui.js') ?>"></script>
<script src="<?= base_url('assets/js/login.js') ?>"></script>
</body>
</html>
