<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Surat Tugas - BPS Provinsi Kalimantan Barat</title>
<link rel="stylesheet" href="<?= base_url('assets/css/beranda.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/beranda.css') ?>">
</head>
<body>
<?php $this->load->view('layout/ikon'); ?>
<header class="atas">
    <div class="wadah atas-isi">
        <a class="merek" href="<?= site_url('/') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="">
            <span><strong>Surat Tugas</strong><small>BPS Provinsi Kalimantan Barat</small></span>
        </a>
        <nav aria-label="Menu halaman">
            <a class="tautan" href="#fitur">Fitur</a>
            <a class="tautan" href="#alur">Cara kerja</a>
            <a class="tautan" href="#aman">Keamanan</a>
            <a class="tb tb-kecil" href="<?= site_url('login') ?>">Masuk</a>
        </nav>
    </div>
</header>
<main>
<section class="hero">
    <div class="wadah hero-isi">
        <div class="hero-teks">
            <span class="lencana"><?= ikon('info') ?>Aplikasi internal BPS Provinsi Kalimantan Barat</span>
            <h1>Surat tugas terbit rapi, bernomor urut, dan tidak pernah bentrok.</h1>
            <p>Satu aplikasi untuk menomori, menerbitkan, dan mengarsipkan surat tugas. Menggantikan mail merge Google Sheets dan Docs yang dikerjakan manual.</p>
            <div class="aksi">
                <a class="tb" href="<?= site_url('login') ?>">Masuk ke aplikasi<?= ikon('panah-kanan') ?></a>
                <a class="tb tb-netral" href="#alur">Lihat cara kerja</a>
            </div>
            <ul class="hero-poin">
                <li><?= ikon('sukses') ?>Nomor anti-ganda</li>
                <li><?= ikon('sukses') ?>PDF &amp; Word</li>
                <li><?= ikon('sukses') ?>Log aktivitas</li>
            </ul>
        </div>
        <div class="peta" aria-label="Contoh tampilan peta nomor">
            <div class="peta-kepala">
                <span class="kecil">Nomor berikutnya</span>
                <span class="peta-tag">Contoh tampilan</span>
            </div>
            <div class="peta-nomor">B-25/61513/KP.650/<?= date('Y') ?></div>
            <?php $st = [10 => 'b', 18 => 'x', 25 => 'n']; ?>
            <div class="g" role="img" aria-label="Contoh peta nomor: nomor terpakai, kosong, dibatalkan, dan berikutnya">
                <?php for ($i = 1; $i <= 30; $i++): $k = isset($st[$i]) ? $st[$i] : ($i < 25 ? 'p' : 'f'); ?>
                <i class="<?= $k ?>"><?= $i ?></i>
                <?php endfor; ?>
            </div>
            <ul class="legenda-peta">
                <li><i class="p"></i>Terpakai</li>
                <li><i class="b"></i>Kosong</li>
                <li><i class="x"></i>Dibatalkan</li>
                <li><i class="n"></i>Berikutnya</li>
            </ul>
        </div>
    </div>
</section>

<section id="fitur" class="blok">
    <div class="wadah">
        <div class="judul-blok">
            <span class="lencana-kecil">Fitur</span>
            <h2>Semua yang dibutuhkan dari nomor sampai arsip</h2>
        </div>
        <div class="fg">
            <article><span class="ikon-fitur"><?= ikon('nomor') ?></span><h3>Penomoran anti-ganda</h3><p>Dua operator menerbitkan surat bersamaan, nomornya tetap berbeda. Format baku B-{urut}/61513/KP.650/{tahun} dapat diubah di menu Pengaturan.</p></article>
            <article><span class="ikon-fitur"><?= ikon('pegawai') ?></span><h3>Satu orang atau banyak</h3><p>Penugasan satu pegawai langsung di badan surat. Lebih dari satu pegawai otomatis dilengkapi tabel lampiran. Surat massal diberi nomor berurutan.</p></article>
            <article><span class="ikon-fitur"><?= ikon('surat') ?></span><h3>PDF dan Word</h3><p>PDF F4 dengan kop BPS siap cetak, atau berkas Word dengan penanda yang sudah terisi. Beberapa surat dapat dicetak dalam satu PDF.</p></article>
            <article><span class="ikon-fitur"><?= ikon('cari') ?></span><h3>Data pegawai terkelola</h3><p>Cari pegawai berdasarkan nama, NIP, atau jabatan. Perbarui lewat impor CSV. Pegawai nonaktif tidak merusak riwayat surat lama.</p></article>
            <article><span class="ikon-fitur"><?= ikon('register') ?></span><h3>Dasar hukum per tanggal berlaku</h3><p>Surat lama tetap mencetak dasar hukum yang berlaku pada masanya, surat baru mengikuti peraturan terbaru.</p></article>
            <article><span class="ikon-fitur"><?= ikon('data') ?></span><h3>Register dan riwayat</h3><p>Ekspor register ke CSV, impor register lama dari Excel, dan simpan tautan arsip dokumen asli dari mail merge sebelumnya.</p></article>
        </div>
    </div>
</section>

<section id="alur" class="blok alur">
    <div class="wadah">
        <div class="judul-blok">
            <span class="lencana-kecil">Cara kerja</span>
            <h2>Empat langkah sampai surat siap ditandatangani</h2>
        </div>
        <ol class="ag">
            <li><b>1</b><h3>Pilih pegawai</h3><p>Ketik nama, NIP, atau jabatan. Hasil muncul seketika, lalu tentukan perannya.</p></li>
            <li><b>2</b><h3>Isi uraian dan tanggal</h3><p>Tanggal pelaksanaan boleh lebih dari satu rentang, misalnya 16 April dan 20 April.</p></li>
            <li><b>3</b><h3>Nomor terbit otomatis</h3><p>Sistem mengambil nomor urut berikutnya, atau Anda memilih nomor kosong yang tersedia.</p></li>
            <li><b>4</b><h3>Unduh PDF atau Word</h3><p>Surat langsung tersimpan di register dan dapat dicetak kapan saja.</p></li>
        </ol>
    </div>
</section>

<section id="aman" class="blok">
    <div class="wadah aman">
        <div>
            <span class="lencana-kecil">Keamanan</span>
            <h2>Dibuat untuk dipakai bersama, dengan jejak yang jelas</h2>
            <p>Operator menerbitkan surat dan mengambil nomor. Admin mengelola pengguna, penandatangan, dasar hukum, dan pemulihan nomor yang dibatalkan.</p>
        </div>
        <ul class="daftar-aman">
            <li><?= ikon('kunci') ?><span><strong>Kata sandi terenkripsi</strong>Disimpan dengan bcrypt.</span></li>
            <li><?= ikon('sukses') ?><span><strong>Formulir terlindungi</strong>Setiap formulir memakai token CSRF.</span></li>
            <li><?= ikon('peringatan') ?><span><strong>Percobaan masuk dibatasi</strong>Percobaan masuk yang gagal dibatasi.</span></li>
            <li><?= ikon('register') ?><span><strong>Log aktivitas</strong>Setiap aksi pengguna tercatat.</span></li>
        </ul>
    </div>
</section>

<section class="ajakan">
    <div class="wadah ajakan-isi">
        <div><h2>Siap menerbitkan surat tugas berikutnya?</h2><p>Masuk dengan akun yang diberikan administrator.</p></div>
        <a class="tb tb-putih" href="<?= site_url('login') ?>">Masuk ke aplikasi<?= ikon('panah-kanan') ?></a>
    </div>
</section>
</main>
<footer class="kaki">
    <div class="wadah kaki-isi">
        <span>&copy; <?= date('Y') ?> Aplikasi Surat Tugas, BPS Provinsi Kalimantan Barat</span>
        <span>CodeIgniter 3, MySQL, dan Dompdf</span>
    </div>
</footer>
</body>
</html>
