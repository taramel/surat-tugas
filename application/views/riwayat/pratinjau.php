<?php $r = $h['ringkas']; ?>
<section class="panel">
    <h2>Pratinjau: <?= e($nama_berkas) ?></h2>
    <p class="muted">Belum ada data yang diubah. Periksa ringkasan di bawah, lalu konfirmasi bila sudah sesuai.</p>
    <ul class="ringkas ringkas-kecil">
        <li><span class="angka"><?= (int) $r['impor'] ?></span><span class="label">Surat akan diimpor</span></li>
        <li><span class="angka"><?= (int) $r['dilewati'] ?></span><span class="label">Baris dilewati</span></li>
        <li><span class="angka"><?= (int) $r['tunggal'] ?></span><span class="label">Satu penerima</span></li>
        <li><span class="angka"><?= (int) $r['banyak'] ?></span><span class="label">Banyak penerima</span></li>
    </ul>
    <dl class="kv">
        <dt>Tanggal pelaksanaan</dt>
        <dd><?= (int) $r['rentang_terbaca'] ?> terbaca, <?= (int) $r['rentang_kosong'] ?> kosong di sheet, <?= (int) $r['rentang_gagal'] ?> tidak terbaca (teks aslinya disimpan pada catatan surat)</dd>
        <dt>Tanggal pengesahan</dt>
        <dd>dibaca apa adanya dari sheet; <?= (int) $r['tanpa_tgl'] ?> surat tanpa tanggal pengesahan dilewati</dd>
        <dt>Data baru yang dibuat</dt>
        <dd><?= (int) $h['baru']['pegawai'] ?> pegawai (dari surat satu penerima),
            <?= count($h['baru']['peran']) ?> peran<?= $h['baru']['peran'] ? ' (' . e(implode(', ', $h['baru']['peran'])) . ')' : '' ?>,
            <?= count($h['baru']['ttd']) ?> penandatangan<?= $h['baru']['ttd'] ? ' (' . e(implode(', ', $h['baru']['ttd'])) . ')' : '' ?></dd>
    </dl>
    <div class="flash flash-warn">
        Pada sheet lama, surat dengan banyak penerima hanya mencatat nama pertama ("... dkk"); daftar lengkapnya tidak tersimpan.
        Surat seperti itu diimpor sebagai arsip: dapat dicari dan direkap, tetapi cetak ulang PDF dari aplikasi dinonaktifkan.
        Tautan ke dokumen aslinya di Google Docs disimpan pada suratnya.
    </div>
</section>

<?php if ($h['masalah']): ?>
<section class="panel">
    <h2>Perlu diperhatikan (<?= count($h['masalah']) ?>)</h2>
    <ul class="catatan"><?php foreach (array_slice($h['masalah'], 0, 60) as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    <?php if (count($h['masalah']) > 60): ?><p class="muted">Menampilkan 60 dari <?= count($h['masalah']) ?> catatan.</p><?php endif; ?>
</section>
<?php endif; ?>

<section class="panel">
    <h2>Contoh hasil (<?= min(12, count($h['surat'])) ?> pertama)</h2>
    <div class="tabel-bungkus"><table class="data kecil">
        <thead><tr><th>Nomor</th><th>Tanggal surat</th><th>Penerima</th><th>Kegiatan</th><th>Pelaksanaan</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($h['surat'], 0, 12) as $s): ?>
            <tr><td class="nomor"><?= e($s['nomor']) ?></td><td><?= e(tgl_indo($s['tgl_surat'])) ?></td>
                <td><?= e($s['nama']) ?></td><td class="potong"><?= e(mb_strimwidth($s['untuk'], 0, 70, '...')) ?></td>
                <td><?= $s['rentang'] ? e(rentang_tanggal($s['rentang'])) : '<span class="muted">tidak ada</span>' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>

<?php if ($r['impor'] > 0): ?>
<form method="post" action="<?= site_url('riwayat/terapkan') ?>" class="aksi aksi-simpan"
      data-konfirmasi="Impor <?= (int) $r['impor'] ?> surat ke dalam aplikasi? Data baru tidak dapat dibatalkan sekaligus.">
    <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <button class="btn btn-primary" type="submit">Impor <?= (int) $r['impor'] ?> surat</button>
    <a class="btn" href="<?= site_url('riwayat') ?>">Batal</a>
</form>
<?php else: ?>
<p class="kosong">Tidak ada surat baru yang dapat diimpor dari berkas ini.</p>
<a class="btn" href="<?= site_url('riwayat') ?>">Kembali</a>
<?php endif; ?>
