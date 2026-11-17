<?php $bulanNama = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; ?>
<?php if ($errors): ?>
<div class="flash dengan-ikon flash-bad" role="alert"><?= ikon('peringatan') ?><div class="flash-isi"><?= e($errors[0]) ?></div></div>
<?php endif; ?>
<?php if ($hasil): ?>
<div class="panel">
    <h2>Hasil impor</h2>
    <p><strong><?= (int) $hasil['dibuat'] ?></strong> catatan dibuat<?= $hasil['dihapus'] ? ', ' . (int) $hasil['dihapus'] . ' catatan impor lama pada bulan itu diganti' : '' ?>.</p>
    <?php if ($hasil['tanpa_pegawai']): ?>
    <div class="flash dengan-ikon flash-warn"><?= ikon('peringatan') ?><div class="flash-isi">Nama berikut tidak ada di daftar pegawai aktif sehingga dilewati:
        <ul><?php foreach ($hasil['tanpa_pegawai'] as $n): ?><li><?= e($n) ?></li><?php endforeach; ?></ul></div></div>
    <?php endif; ?>
    <?php if ($hasil['kode_asing']): ?>
    <div class="flash dengan-ikon flash-warn"><?= ikon('peringatan') ?><div class="flash-isi">Kode berikut belum terdaftar di <a href="<?= site_url('cuti/jenis') ?>">Jenis cuti</a> sehingga dilewati: <strong><?= e(implode(', ', $hasil['kode_asing'])) ?></strong>.</div></div>
    <?php endif; ?>
    <div class="aksi"><a class="btn btn-primary" href="<?= site_url('cuti') ?>">Lihat catatan cuti</a></div>
</div>
<?php endif; ?>
<form method="post" action="<?= site_url('cuti/impor') ?>" enctype="multipart/form-data" class="panel lebar-form form">
    <?= csrf_field() ?>
    <p>Unduh <strong>satu sheet bulan</strong> dari rekap cuti sebagai CSV (File &rarr; Unduh &rarr; Nilai dipisahkan koma). Baris tanggal (1, 2, 3, ...) dan kode per tanggal akan dibaca. Tanggal berurutan dengan kode sama digabung menjadi satu catatan.</p>
    <div class="grid-form">
        <label class="field"><span>Bulan sheet</span><select name="bulan"><?php foreach ($bulanNama as $i => $n): ?><option value="<?= $i + 1 ?>"<?= (int) date('n') === $i + 1 ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Tahun</span><input type="number" name="tahun" value="<?= (int) date('Y') ?>" min="2000" max="2100"></label>
        <label class="field lebar"><span>Berkas CSV</span><input type="file" name="berkas" accept=".csv" required></label>
    </div>
    <label class="centang"><input type="checkbox" name="ganti" value="1" checked>Ganti catatan hasil impor sebelumnya pada bulan ini (catatan manual tidak disentuh)</label>
    <div class="aksi aksi-simpan"><button class="btn btn-primary" type="submit"><?= ikon('unggah') ?>Impor</button></div>
</form>
