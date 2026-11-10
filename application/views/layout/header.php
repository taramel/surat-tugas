<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> - Surat Tugas</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/app.css') ?>">
</head>
<body data-base="<?= base_url() ?>">
<?php $this->load->view('layout/ikon'); ?>
<a class="lewati" href="#isi">Lewati ke isi</a>
<div class="rangka">
<aside class="sisi" id="sisi">
    <div class="merek">
        <img class="merek-logo" src="<?= base_url('assets/img/logo.png') ?>" alt="">
        <div>
            <span class="merek-nama">Surat Tugas</span>
            <span class="merek-sub">BPS Provinsi Kalimantan Barat</span>
        </div>
    </div>
    <nav aria-label="Menu utama">
        <?php $seg1 = $this->uri->segment(1); $seg2 = $this->uri->segment(2);
        $tautan = function ($url, $ikon, $teks, $aktif) {
            return '<a href="' . site_url($url) . '"' . ($aktif ? ' class="aktif" aria-current="page"' : '') . '>' . ikon($ikon) . '<span>' . e($teks) . '</span></a>';
        }; ?>
        <div class="menu-grup">Utama</div>
        <?= $tautan('dashboard', 'dashboard', 'Ringkasan', $seg1 === 'dashboard') ?>
        <?= $tautan('surat', 'surat', 'Surat tugas', in_array($seg1, ['surat', 'ambil'], true)) ?>
        <div class="menu-grup">Data &amp; arsip</div>
        <?= $tautan('pegawai', 'pegawai', 'Pegawai', $seg1 === 'pegawai') ?>
        <?= $tautan('register', 'register', 'Register', $seg1 === 'register') ?>
        <?= $tautan('template', 'template', 'Template Word', $seg1 === 'template' && $seg2 !== 'kelola') ?>
        <?= $tautan('laporan', 'laporan', 'Laporan', $seg1 === 'laporan') ?>
        <?php if ($user['role'] === 'admin'): ?>
        <div class="menu-grup">Administrasi</div>
        <details class="menu-lipat"<?= in_array($seg1, ['master', 'impor', 'riwayat', 'pembaruan'], true) || $seg2 === 'kelola' ? ' open' : '' ?>>
            <summary><?= ikon('kelola') ?><span>Pengelolaan</span><?= ikon('panah-bawah', 'panah') ?></summary>
            <div class="submenu">
                <a href="<?= site_url('template/kelola') ?>"<?= $seg2 === 'kelola' ? ' class="aktif" aria-current="page"' : '' ?>>Kelola template</a>
                <a href="<?= site_url('master/penandatangan') ?>"<?= $seg2 === 'penandatangan' ? ' class="aktif" aria-current="page"' : '' ?>>Penandatangan</a>
                <a href="<?= site_url('master/peran') ?>"<?= $seg2 === 'peran' ? ' class="aktif" aria-current="page"' : '' ?>>Peran penugasan</a>
                <a href="<?= site_url('master/dasar_hukum') ?>"<?= $seg2 === 'dasar_hukum' ? ' class="aktif" aria-current="page"' : '' ?>>Dasar hukum</a>
                <a href="<?= site_url('master/pengaturan') ?>"<?= $seg2 === 'pengaturan' ? ' class="aktif" aria-current="page"' : '' ?>>Pengaturan</a>
                <a href="<?= site_url('master/pengguna') ?>"<?= $seg2 === 'pengguna' ? ' class="aktif" aria-current="page"' : '' ?>>Pengguna</a>
                <a href="<?= site_url('impor') ?>"<?= $seg1 === 'impor' ? ' class="aktif" aria-current="page"' : '' ?>>Impor register lama</a>
                <a href="<?= site_url('riwayat') ?>"<?= $seg1 === 'riwayat' ? ' class="aktif" aria-current="page"' : '' ?>>Impor riwayat surat</a>
                <a href="<?= site_url('master/log') ?>"<?= $seg2 === 'log' ? ' class="aktif" aria-current="page"' : '' ?>>Log aktivitas</a>
                <a href="<?= site_url('master/cadangan') ?>"<?= $seg2 === 'cadangan' ? ' class="aktif" aria-current="page"' : '' ?>>Cadangan</a>
            </div>
        </details>
        <?php endif; ?>
    </nav>
    <div class="akun">
        <a href="<?= site_url('akun/password') ?>"><?= ikon('kunci') ?><span>Ganti kata sandi</span></a>
        <a href="<?= site_url('logout') ?>"><?= ikon('keluar') ?><span>Keluar</span></a>
    </div>
</aside>
<div class="sisi-tutup" id="sisi-tutup"></div>
<div class="utama">
<header class="bilah">
    <button type="button" class="btn btn-ikon menu-tombol" id="menu-tombol" aria-controls="sisi" aria-expanded="false" aria-label="Buka menu" title="Menu"><?= ikon('menu') ?></button>
    <h1><?= e($judul) ?></h1>
    <div class="pengguna">
        <div class="pengguna-info">
            <span class="pengguna-nama"><?= e($user['nama']) ?></span>
            <span class="pengguna-peran"><?= $user['role'] === 'admin' ? 'Admin' : 'Operator' ?></span>
        </div>
        <span class="pengguna-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim($user['nama']), 0, 1))) ?></span>
    </div>
</header>
<main id="isi">
<?php $flash = $this->session->flashdata('flash'); if ($flash):
    $ikonFlash = ['ok' => 'sukses', 'bad' => 'peringatan', 'warn' => 'peringatan']; ?>
    <div class="flash dengan-ikon flash-<?= e($flash['jenis']) ?>" role="status">
        <?= ikon(isset($ikonFlash[$flash['jenis']]) ? $ikonFlash[$flash['jenis']] : 'info') ?>
        <div class="flash-isi"><?= e($flash['pesan']) ?></div>
        <button type="button" class="flash-tutup" aria-label="Tutup pesan" title="Tutup"><?= ikon('tutup') ?></button>
    </div>
<?php endif; ?>
