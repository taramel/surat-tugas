<?php
$berikut = $maks + 1;
$contoh = format_nomor($format, $berikut, $satker, $klas, $tahun);
$perhatian = [];
foreach ($register as $u => $r) {
    if ($r['status'] === 'dibatalkan' || !empty($r['catatan']) || ($r['status'] === 'arsip' && empty($r['uraian']))) {
        $perhatian[$u] = $r;
    }
}
?>
<form method="get" class="saring" action="<?= site_url('nomor') ?>">
    <label class="field"><span>Tahun</span>
        <select name="tahun" onchange="this.form.submit()">
        <?php foreach ($tahun_opsi as $t): ?><option value="<?= $t ?>"<?= $t === $tahun ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select></label>
    <div class="aksi">
        <a class="btn btn-primary" href="<?= site_url('surat/tambah') ?>">Terbitkan nomor berikutnya</a>
        <?php if ($user['role'] === 'admin'): ?><a class="btn" href="<?= site_url('nomor/impor') ?>">Impor register lama</a><?php endif; ?>
    </div>
</form>

<p>Nomor berikutnya: <strong class="nomor"><?= e($contoh) ?></strong>
   <span class="muted">dari <?= (int) $maks ?> nomor tahun <?= $tahun ?>; <?= count($bebas) ?> nomor kosong yang masih dapat dipakai.</span></p>

<ul class="legenda" aria-label="Keterangan warna">
    <li><span class="sel sel-pakai"></span>Terpakai</li>
    <li><span class="sel sel-bebas"></span>Kosong, dapat dipakai</li>
    <li><span class="sel sel-perlu"></span>Terpakai tanpa uraian</li>
    <li><span class="sel sel-batal"></span>Dibatalkan</li>
</ul>

<?php if (!$maks): ?>
    <p class="kosong">Belum ada nomor pada tahun <?= $tahun ?>.
        <?php if ($user['role'] === 'admin'): ?><a href="<?= site_url('nomor/impor') ?>">Impor register lama</a> atau<?php endif; ?>
        <a href="<?= site_url('surat/tambah') ?>">terbitkan surat pertama</a>.</p>
<?php else: ?>
<div class="grid-nomor" role="list">
<?php for ($i = 1; $i <= $maks; $i++):
    $r = isset($register[$i]) ? $register[$i] : null;
    $st = $r ? $r['status'] : 'kosong';
    $uraian = $r ? (!empty($r['untuk_surat']) ? $r['untuk_surat'] : (!empty($r['uraian']) ? $r['uraian'] : '')) : '';
    if ($st === 'terpakai') { $kelas = 'sel-pakai'; $ket = 'Terpakai'; }
    elseif ($st === 'arsip') { $kelas = $uraian ? 'sel-pakai' : 'sel-perlu'; $ket = $uraian ? 'Arsip' : 'Terpakai tanpa uraian'; }
    elseif ($st === 'dibatalkan') { $kelas = 'sel-batal'; $ket = 'Dibatalkan'; }
    else { $kelas = 'sel-bebas'; $ket = 'Kosong, dapat dipakai'; }
    $judulSel = $i . ' - ' . $ket . ($uraian ? ': ' . mb_strimwidth($uraian, 0, 90, '...') : '');
    if ($st === 'terpakai' && $r['surat_tugas_id']) { $href = site_url('surat/detail/' . $r['surat_tugas_id']); }
    elseif ($kelas === 'sel-bebas') { $href = site_url('surat/tambah?nomor=' . $i); }
    else { $href = null; }
?>
    <?php if ($href): ?><a role="listitem" class="sel <?= $kelas ?>" href="<?= e($href) ?>" title="<?= e($judulSel) ?>"><?= $i ?></a>
    <?php else: ?><span role="listitem" class="sel <?= $kelas ?>" title="<?= e($judulSel) ?>"><?= $i ?></span><?php endif; ?>
<?php endfor; ?>
    <a role="listitem" class="sel sel-berikut" href="<?= site_url('surat/tambah') ?>" title="Nomor berikutnya"><?= $berikut ?></a>
</div>

<?php if ($perhatian): ?>
<section class="panel">
    <h2>Perlu diperiksa</h2>
    <div class="tabel-bungkus"><table class="data">
        <thead><tr><th>Nomor</th><th>Status</th><th>Catatan</th></tr></thead>
        <tbody>
        <?php foreach ($perhatian as $u => $r): ?>
            <tr><td class="nomor"><?= format_nomor($format, $u, $satker, $klas, $tahun) ?></td>
                <td><?= label_status($r['status']) ?></td>
                <td><?= e($r['catatan'] ?: ($r['status'] === 'dibatalkan' ? 'Surat dibatalkan' : '')) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php endif; ?>
<?php endif; ?>
