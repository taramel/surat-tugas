<?php $bulanNama = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; $nama = $bulanNama[$bulan - 1]; ?>
<form class="saring" method="get" action="<?= site_url('cuti/kalender') ?>">
    <label class="field"><span>Bulan</span>
        <select name="bulan"><?php foreach ($bulanNama as $i => $n): ?><option value="<?= $i + 1 ?>"<?= $bulan === $i + 1 ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></label>
    <label class="field"><span>Tahun</span><input type="number" name="tahun" value="<?= (int) $tahun ?>" min="2000" max="2100"></label>
    <label class="centang"><input type="checkbox" name="semua" value="1"<?= $semua ? ' checked' : '' ?>>Tampilkan semua pegawai</label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button></div>
</form>

<ul class="legenda">
    <?php foreach ($jenis as $j): ?><li><span class="kode kode-<?= e($j['kategori']) ?>"><?= e($j['kode']) ?></span><?= e($j['nama']) ?></li><?php endforeach; ?>
</ul>

<?php if (!$pegawai): ?>
    <p class="kosong"><?= ikon('kosong') ?>Belum ada catatan cuti pada <?= e($nama) ?> <?= (int) $tahun ?>.</p>
<?php else: ?>
<div class="tabel-bungkus kal-bungkus">
    <table class="kal">
        <thead><tr><th class="nama"><?= e($nama) ?> <?= (int) $tahun ?></th>
        <?php for ($d = 1; $d <= $hari_bulan; $d++): $akhirPekan = (int) date('N', strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d))) >= 6; ?>
            <th class="<?= $akhirPekan ? 'akhir' : '' ?>"><?= $d ?></th>
        <?php endfor; ?></tr></thead>
        <tbody>
        <?php foreach ($pegawai as $p): ?>
            <tr><td class="nama"><a href="<?= site_url('pegawai/riwayat/' . $p['id']) ?>"><?= e($p['nama']) ?></a></td>
            <?php for ($d = 1; $d <= $hari_bulan; $d++):
                $akhirPekan = (int) date('N', strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d))) >= 6;
                $sel = isset($peta[$p['id']][$d]) ? $peta[$p['id']][$d] : null; ?>
                <td class="<?= $akhirPekan ? 'akhir' : '' ?>"><?= $sel ? '<span class="kode kode-' . e($sel['kategori']) . '">' . e($sel['kode']) . '</span>' : '' ?></td>
            <?php endfor; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
