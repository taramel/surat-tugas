<form method="get" class="saring" action="<?= site_url('laporan') ?>">
    <label class="field"><span>Tahun</span>
        <select name="tahun"><option value="">Semua</option>
        <?php foreach ($tahun_opsi as $t): ?><option value="<?= $t ?>"<?= (string) $f['tahun'] === (string) $t ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></label>
    <label class="field"><span>Bulan</span>
        <select name="bulan"><option value="">Semua</option>
        <?php for ($b = 1; $b <= 12; $b++): ?><option value="<?= $b ?>"<?= (string) $f['bulan'] === (string) $b ? ' selected' : '' ?>><?= bulan_indo($b) ?></option><?php endfor; ?></select></label>
    <label class="field"><span>Status</span>
        <select name="status"><option value="">Semua</option>
            <option value="aktif"<?= $f['status'] === 'aktif' ? ' selected' : '' ?>>Aktif</option>
            <option value="dibatalkan"<?= $f['status'] === 'dibatalkan' ? ' selected' : '' ?>>Dibatalkan</option></select></label>
    <label class="field"><span>Cari</span><input type="search" name="q" value="<?= e($f['q']) ?>"></label>
    <div class="aksi"><button class="btn" type="submit">Terapkan</button>
        <a class="btn btn-primary" href="<?= site_url('laporan/ekspor') . '?' . e(http_build_query($f)) ?>">Unduh CSV</a></div>
</form>

<div class="dua-kolom">
<section class="panel"><h2>Surat aktif per bulan, <?= $tahun_rekap ?></h2>
    <table class="data kecil"><tbody>
    <?php foreach ($bulanan as $b => $j): ?><tr><td><?= bulan_indo($b) ?></td><td class="angka-kanan"><?= $j ?></td></tr><?php endforeach; ?>
    </tbody></table></section>
<section class="panel"><h2>Penugasan per peran, <?= $tahun_rekap ?></h2>
    <?php if (!$per_peran): ?><p class="kosong">Belum ada data.</p><?php else: ?>
    <table class="data kecil"><tbody>
    <?php foreach ($per_peran as $r): ?><tr><td><?= e($r['peran']) ?></td><td class="angka-kanan"><?= (int) $r['jml'] ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?></section>
<section class="panel"><h2>Paling sering ditugaskan, <?= $tahun_rekap ?></h2>
    <?php if (!$per_pegawai): ?><p class="kosong">Belum ada data.</p><?php else: ?>
    <table class="data kecil"><tbody>
    <?php foreach ($per_pegawai as $r): ?><tr><td><?= e($r['nama']) ?></td><td class="angka-kanan"><?= (int) $r['jml'] ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?></section>
</div>

<section class="panel">
    <h2><?= count($rows) ?> surat sesuai penyaringan</h2>
    <?php if (!$rows): ?><p class="kosong">Tidak ada data.</p><?php else: ?>
    <div class="tabel-bungkus"><table class="data">
        <thead><tr><th>Nomor</th><th>Tanggal surat</th><th>Penerima</th><th>Kegiatan</th><th>Pelaksanaan</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td class="nomor"><a href="<?= site_url('surat/detail/' . $r['id']) ?>"><?= e($r['nomor_surat']) ?></a></td>
                <td><?= e(tgl_indo($r['tanggal_surat'])) ?></td><td><?= e($r['penerima']) ?></td>
                <td class="potong"><?= e($r['untuk']) ?></td><td><?= e($r['rentang']) ?></td><td><?= label_status($r['status']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div><?php endif; ?>
</section>
