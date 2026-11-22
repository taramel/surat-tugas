<?php if ($errors): ?>
<div class="flash dengan-ikon flash-bad" role="alert"><?= ikon('peringatan') ?><div class="flash-isi"><?= e($errors[0]) ?></div></div>
<?php endif; ?>
<?php if ($hasil): ?>
<div class="panel">
    <h2>Hasil impor</h2>
    <p><strong><?= (int) $hasil['dibuat'] ?></strong> catatan dibuat<?= $hasil['kembar'] ? ', ' . (int) $hasil['kembar'] . ' dilewati karena sudah ada (kembar)' : '' ?>.</p>
    <?php if ($hasil['tanggal_rusak'] || $hasil['tanpa_kegiatan']): ?>
    <div class="flash dengan-ikon flash-warn"><?= ikon('peringatan') ?><div class="flash-isi">
        <?= (int) $hasil['tanggal_rusak'] ? (int) $hasil['tanggal_rusak'] . ' baris dilewati karena tanggal mulai tidak terbaca. ' : '' ?>
        <?= (int) $hasil['tanpa_kegiatan'] ? (int) $hasil['tanpa_kegiatan'] . ' baris dilewati karena nama kegiatan kosong.' : '' ?></div></div>
    <?php endif; ?>
    <?php if ($hasil['tanpa_pegawai']): ?>
    <div class="flash dengan-ikon flash-warn"><?= ikon('peringatan') ?><div class="flash-isi">Nama berikut tidak ada di daftar pegawai aktif sehingga dilewati:
        <ul><?php foreach ($hasil['tanpa_pegawai'] as $n): ?><li><?= e($n) ?></li><?php endforeach; ?></ul></div></div>
    <?php endif; ?>
    <div class="aksi"><a class="btn btn-primary" href="<?= site_url('bangkom') ?>">Lihat catatan Bangkom</a></div>
</div>
<?php endif; ?>
<form method="post" action="<?= site_url('bangkom/impor') ?>" enctype="multipart/form-data" class="panel lebar-form form">
    <?= csrf_field() ?>
    <p>Unduh sheet <strong>6100</strong> (bukan Sheet1) sebagai CSV. Kolom yang dibaca: Nama, Nama Pengembangan Kompetensi, Tanggal Mulai/Selesai, JP, Bentuk, Kategori, Jenis, Penyelenggara, Nama Penyelenggara, Tautan Sertifikat.
       Format tanggal yang bercampur (6-Okt-2026, 6-Oct-2026, 9/25/2026) dibaca otomatis. Catatan yang sama (pegawai, kegiatan, tanggal mulai) tidak digandakan, jadi aman diimpor ulang.</p>
    <div class="grid-form">
        <label class="field"><span>Tahun bawaan <small>(untuk tahun yang rusak pada sheet)</small></span><input type="number" name="tahun" value="<?= (int) date('Y') ?>" min="2000" max="2100"></label>
        <label class="field lebar"><span>Berkas CSV</span><input type="file" name="berkas" accept=".csv" required></label>
    </div>
    <div class="aksi aksi-simpan"><button class="btn btn-primary" type="submit"><?= ikon('unggah') ?>Impor</button></div>
</form>
