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
