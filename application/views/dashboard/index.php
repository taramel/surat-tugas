<section class="ringkas" aria-label="Ringkasan">
    <div><span class="ikon-kartu"><?= ikon('surat') ?></span><span class="angka"><?= (int) $total ?></span><span class="label">Surat aktif tahun <?= $tahun ?></span></div>
    <div><span class="ikon-kartu"><?= ikon('register') ?></span><span class="angka"><?= (int) $bulan_ini ?></span><span class="label">Terbit bulan ini</span></div>
    <div><span class="ikon-kartu"><?= ikon('nomor') ?></span><span class="angka">B-<?= (int) $terakhir ?></span><span class="label">Nomor terakhir</span></div>
    <div><span class="ikon-kartu ok"><?= ikon('sukses') ?></span><span class="angka"><?= (int) $bebas ?></span><span class="label">Nomor kosong dapat dipakai</span></div>
    <div><span class="ikon-kartu bahaya"><?= ikon('batal') ?></span><span class="angka"><?= (int) $dibatalkan ?></span><span class="label">Dibatalkan</span></div>
</section>

<div class="aksi-atas">
    <a class="btn btn-primary" href="<?= site_url('surat/tambah') ?>"><?= ikon('tambah') ?>Buat surat tugas</a>
    <a class="btn" href="<?= site_url('nomor') ?>"><?= ikon('register') ?>Lihat register nomor</a>
</div>

<?php if (!empty($kep)): ?>
<section class="panel">
    <h2>Kepegawaian hari ini</h2>
    <div class="kolom-tiga">
        <div>
            <h3>Sedang cuti (<?= count($kep['cuti']) ?>)</h3>
            <?php if (!$kep['cuti']): ?><p class="muted">Tidak ada pegawai yang tercatat cuti.</p><?php else: ?>
            <ul class="catatan"><?php foreach (array_slice($kep['cuti'], 0, 8) as $c): ?>
                <li><span class="kode kode-<?= e($c['kategori']) ?>"><?= e($c['kode']) ?></span><span><?= e($c['nama_pegawai']) ?> <span class="muted">s.d. <?= e(tgl_indo($c['selesai'])) ?></span></span></li>
            <?php endforeach; ?></ul>
            <?php if (count($kep['cuti']) > 8): ?><p class="muted">dan <?= count($kep['cuti']) - 8 ?> lainnya. <a href="<?= site_url('cuti/kalender') ?>">Lihat kalender</a></p><?php endif; ?>
            <?php endif; ?>
        </div>
        <div>
            <h3>Sedang bertugas (<?= count($kep['bertugas']) ?>)</h3>
            <?php if (!$kep['bertugas']): ?><p class="muted">Tidak ada penugasan dari surat tugas hari ini.</p><?php else: ?>
            <ul class="catatan"><?php foreach (array_slice($kep['bertugas'], 0, 8) as $t): ?>
                <li><span class="kode kode-surat">ST</span><span><a href="<?= site_url('pegawai/riwayat/' . $t['id']) ?>"><?= e($t['nama']) ?></a> <a class="muted" href="<?= site_url('surat/detail/' . $t['surat_id']) ?>"><?= e($t['nomor_surat']) ?></a></span></li>
            <?php endforeach; ?></ul>
            <?php if (count($kep['bertugas']) > 8): ?><p class="muted">dan <?= count($kep['bertugas']) - 8 ?> lainnya.</p><?php endif; ?>
            <?php endif; ?>
        </div>
        <div>
            <h3>Sisa cuti menipis</h3>
            <?php if (!$kep['saldo']): ?><p class="muted">Tidak ada saldo yang menipis atau saldo belum diatur.</p><?php else: ?>
            <ul class="catatan"><?php foreach ($kep['saldo'] as $r): ?>
                <li><span class="badge <?= $r['sisa'] < 0 ? 'badge-bad' : 'badge-warn' ?>"><?= e(angka_hari($r['sisa'])) ?></span><span><a href="<?= site_url('cuti/saldo_ubah/' . $r['id'] . '?tahun=' . (int) $tahun) ?>"><?= e($r['nama']) ?></a></span></li>
            <?php endforeach; ?></ul>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php endif; ?>
<section class="panel">
    <h2>Perlu diperiksa</h2>
    <?php if (!$bebas && !$tanpa_uraian && !$terlambat): ?>
        <p class="muted"><?= ikon('sukses') ?> Tidak ada yang perlu diperiksa.</p>
    <?php else: ?>
    <ul class="catatan">
        <?php if ($bebas): ?><li><?= ikon('peringatan') ?><span><?= (int) $bebas ?> nomor kosong tahun <?= $tahun ?> belum dipakai. <a href="<?= site_url('nomor') ?>">Buka register nomor</a></span></li><?php endif; ?>
        <?php if ($tanpa_uraian): ?><li><?= ikon('peringatan') ?><span><?= (int) $tanpa_uraian ?> nomor terpakai tanpa uraian. <a href="<?= site_url('nomor') ?>">Periksa di register</a></span></li><?php endif; ?>
        <?php if ($terlambat): ?><li><?= ikon('peringatan') ?><span><?= (int) $terlambat ?> surat bertanggal setelah pelaksanaan pertama. <a href="<?= site_url('surat?flag=terlambat') ?>">Lihat daftar</a></span></li><?php endif; ?>
    </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Surat per bulan, <?= $tahun ?></h2>
    <?php $maks = max(1, max($rekap)); ?>
    <div class="batang" role="img" aria-label="Jumlah surat aktif per bulan">
        <?php foreach ($rekap as $b => $j): ?>
        <div class="batang-kolom" title="<?= e(bulan_indo($b)) ?>: <?= $j ?> surat">
            <span class="batang-nilai"><?= $j ?></span>
            <span class="batang-isi" style="height: <?= round($j / $maks * 100) ?>%"></span>
            <span class="batang-label"><?= e(substr(bulan_indo($b), 0, 3)) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <h2>Surat terbaru</h2>
    <?php if (!$terbaru): ?>
        <p class="kosong"><?= ikon('kosong') ?>Belum ada surat tugas tahun ini. <a href="<?= site_url('surat/tambah') ?>">Buat surat pertama</a>
        atau <a href="<?= site_url('nomor/impor') ?>">impor register lama</a>.</p>
    <?php else: ?>
    <div class="tabel-bungkus"><table class="data">
        <thead><tr><th>Nomor</th><th>Penerima</th><th>Pelaksanaan</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($terbaru as $r): ?>
            <tr>
                <td class="nomor"><a href="<?= site_url('surat/detail/' . $r['id']) ?>"><?= e($r['nomor_surat']) ?></a></td>
                <td><?= e($r['penerima']) ?></td>
                <td><?= e($r['rentang']) ?></td>
                <td><?= label_status($r['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
