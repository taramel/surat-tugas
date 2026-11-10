<p>Template ini dipakai otomatis saat Anda <a href="<?= site_url('ambil') ?>">mengambil nomor</a>: file Word yang diunduh sudah berisi nomor, tanggal, penandatangan, dan uraian kegiatan.
   Gunakan halaman ini bila hanya membutuhkan berkas template kosong.</p>
<?php if (!$seri): ?>
    <p class="kosong">Belum ada template yang aktif.<?php if ($user['role'] === 'admin'): ?> <a href="<?= site_url('template/kelola') ?>">Tambahkan template</a>.<?php endif; ?></p>
<?php else: ?>
<div class="tabel-bungkus"><table class="data">
    <thead><tr><th>Jenis surat</th><th>Versi berlaku</th><th>Keterangan</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($seri as $t): ?>
        <tr><td><strong><?= e($t['nama']) ?></strong></td>
            <td>mulai <?= e(tgl_indo($t['berlaku_mulai'])) ?></td><td><?= e($t['catatan']) ?></td>
            <td class="aksi-baris"><a class="btn btn-primary" href="<?= site_url('template/unduh/' . $t['id']) ?>">Unduh template</a></td></tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
<p class="muted">Bagian yang disorot kuning pada template adalah isian yang diketik sendiri di Word.
   <?php if ($user['role'] === 'admin'): ?>Admin dapat mengganti atau menambah versi template di <a href="<?= site_url('template/kelola') ?>">Kelola template</a>.<?php endif; ?></p>
