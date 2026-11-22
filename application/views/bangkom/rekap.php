<form class="saring" method="get" action="<?= site_url('bangkom/rekap') ?>">
    <label class="field"><span>Tahun</span><input type="number" name="tahun" value="<?= (int) $tahun ?>" min="2000" max="2100"></label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button></div>
</form>
<?php $memenuhi = 0; foreach ($rows as $r) { if ((int) $r['jp'] >= $target) { $memenuhi++; } } ?>
<section class="ringkas ringkas-kecil" aria-label="Ringkasan">
    <div><span class="ikon-kartu"><?= ikon('pegawai') ?></span><span class="angka"><?= count($rows) ?></span><span class="label">Pegawai aktif</span></div>
    <div><span class="ikon-kartu ok"><?= ikon('sukses') ?></span><span class="angka"><?= $memenuhi ?></span><span class="label">Sudah memenuhi <?= (int) $target ?> JP</span></div>
    <div><span class="ikon-kartu waspada"><?= ikon('peringatan') ?></span><span class="angka"><?= count($rows) - $memenuhi ?></span><span class="label">Belum memenuhi</span></div>
</section>
<?php if ($user['role'] === 'admin'): ?>
<form method="post" action="<?= site_url('bangkom/target') ?>" class="saring">
    <?= csrf_field() ?><input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
    <label class="field"><span>Target JP per pegawai per tahun</span><input type="text" name="target" inputmode="numeric" value="<?= (int) $target ?>"></label>
    <div class="aksi"><button class="btn" type="submit">Simpan target</button></div>
</form>
<?php endif; ?>
<div class="tabel-bungkus">
    <table class="data">
        <thead><tr><th>Pegawai</th><th>Tim</th><th class="angka-kanan">Kegiatan</th><th class="angka-kanan">Total JP</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $ok = (int) $r['jp'] >= $target; ?>
            <tr>
                <td><a href="<?= site_url('bangkom?tahun=' . (int) $tahun . '&q=' . rawurlencode($r['nama'])) ?>"><?= e($r['nama']) ?></a></td>
                <td class="muted"><?= e($r['unit_kerja']) ?></td>
                <td class="angka-kanan"><?= (int) $r['kegiatan'] ?></td>
                <td class="angka-kanan"><?= (int) $r['jp'] ?></td>
                <td><?= $ok ? '<span class="badge badge-ok">Sudah memenuhi</span>' : '<span class="badge badge-warn">Belum memenuhi</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
