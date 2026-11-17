<?php if ($lap === null): ?>
<section class="panel sempit">
    <h2>Impor register nomor dari sheet lama</h2>
    <ol class="langkah">
        <li>Buka sheet "Agenda Surat Tugas", lalu pilih File, Download, Microsoft Excel (.xlsx). Cara lain: unduh satu tab sebagai CSV.</li>
        <li>Unggah berkas di bawah ini. Pada berkas Excel, setiap tab bernama "SURTU &lt;tahun&gt;" diimpor sekaligus dan tahunnya diambil dari nama tab.</li>
        <li>Kolom dikenali dari judulnya: No, No Surat Tugas, Tanggal Surat, Uraian Kegiatan, Yang Membuat.</li>
    </ol>
    <form method="post" action="<?= site_url('impor/proses') ?>" enctype="multipart/form-data" class="form">
        <?= csrf_field() ?>
        <label class="field"><span>Tahun register (khusus berkas CSV)</span>
            <input type="number" name="tahun" value="<?= date('Y') ?>" min="2000" max="2100" required>
            <small>Dipakai untuk baris kosong pada berkas CSV. Berkas Excel memakai tahun dari nama tab.</small></label>
        <label class="field"><span>Berkas Excel atau CSV</span><input type="file" name="berkas" accept=".xlsx,.csv,text/csv" required></label>
        <div class="aksi"><button class="btn btn-primary" type="submit">Impor</button><a class="btn" href="<?= site_url('register') ?>">Batal</a></div>
    </form>
    <p class="muted">Nomor yang sudah ada di aplikasi tidak ditimpa. Nomor ganda dan nomor berformat tidak baku dilaporkan setelah impor.</p>
</section>
<?php elseif ($lap['error']): ?>
<div class="flash flash-bad" role="alert"><?= e($lap['error']) ?></div>
<a class="btn" href="<?= site_url('impor') ?>">Coba lagi</a>
<?php else: ?>
<section class="panel">
    <h2>Hasil impor</h2>
    <ul class="ringkas ringkas-kecil">
        <li><span class="angka"><?= (int) $lap['baru'] ?></span><span class="label">Nomor diimpor</span></li>
        <li><span class="angka"><?= (int) $lap['tersedia'] ?></span><span class="label">Nomor kosong dicatat</span></li>
        <li><span class="angka"><?= (int) $lap['dilewati'] ?></span><span class="label">Baris dilewati</span></li>
        <li><span class="angka"><?= (int) $lap['tanggal_ragu'] ?></span><span class="label">Tanggal diragukan, tidak diimpor</span></li>
    </ul>
    <?php if (!empty($lap['sheet'])): ?>
    <div class="tabel-bungkus"><table class="data kecil">
        <thead><tr><th>Tab</th><th>Nomor diimpor</th><th>Kosong dicatat</th><th>Dilewati</th></tr></thead>
        <tbody><?php foreach ($lap['sheet'] as $nama => $j): ?>
            <tr><td><?= e($nama) ?></td><td class="angka-kanan"><?= (int) $j[0] ?></td><td class="angka-kanan"><?= (int) $j[1] ?></td><td class="angka-kanan"><?= (int) $j[2] ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
    <?php if ($lap['tanggal_ragu']): ?>
    <p class="muted">Sel tanggal pada sheet lama sering tertukar antara hari dan bulan (misalnya 2 Januari terbaca 1 Februari),
       jadi tanggal yang tidak bisa dipastikan sengaja dikosongkan. Nomor dan uraiannya tetap diimpor.</p>
    <?php endif; ?>
    <?php foreach ([['ganda', 'Nomor ganda'], ['tidak_baku', 'Format nomor tidak baku'], ['tanpa_uraian', 'Nomor terpakai tanpa uraian']] as $kel): list($k, $judul) = $kel; ?>
        <?php if ($lap[$k]): ?>
        <h3><?= $judul ?> (<?= count($lap[$k]) ?>)</h3>
        <ul class="catatan"><?php foreach (array_slice($lap[$k], 0, 60) as $baris): ?><li><?= e($baris) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="aksi"><a class="btn btn-primary" href="<?= site_url('register') ?>">Lihat register</a></div>
</section>
<?php endif; ?>
