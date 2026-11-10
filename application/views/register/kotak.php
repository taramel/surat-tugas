<?php
$berikut = $maks + 1;
$contoh = format_nomor($format, $berikut, $satker, $klas, $tahun);
?>
<form method="get" class="saring" action="<?= site_url('register/kotak') ?>">
    <label class="field"><span>Tahun</span>
        <select name="tahun" onchange="this.form.submit()">
        <?php foreach ($tahun_opsi as $t): ?><option value="<?= $t ?>"<?= $t === $tahun ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select></label>
    <div class="aksi"><a class="btn btn-primary" href="<?= site_url('ambil') ?>">Ambil nomor berikutnya</a>
        <a class="btn" href="<?= site_url('register?tahun=' . $tahun) ?>">Lihat daftar</a></div>
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
    <p class="kosong">Belum ada nomor pada tahun <?= $tahun ?>. <a href="<?= site_url('ambil') ?>">Ambil nomor pertama</a>.</p>
<?php else: ?>
<div class="grid-nomor" role="list">
<?php for ($i = 1; $i <= $maks; $i++):
    $r = isset($register[$i]) ? $register[$i] : null;
    $st = $r ? $r['status'] : 'kosong';
    $uraian = $r ? (string) $r['uraian'] : '';
    if ($st === 'terpakai' || $st === 'arsip') { $kelas = $uraian !== '' ? 'sel-pakai' : 'sel-perlu'; $ket = $st === 'arsip' ? 'Arsip' : 'Terpakai'; }
    elseif ($st === 'dibatalkan') { $kelas = 'sel-batal'; $ket = 'Dibatalkan'; }
    else { $kelas = 'sel-bebas'; $ket = 'Kosong, dapat dipakai'; }
    $judulSel = $i . ' - ' . $ket . ($uraian !== '' ? ': ' . mb_strimwidth($uraian, 0, 90, '...') : '');
    if ($r && $r['nomor_surat']) { $href = site_url('register?tahun=' . $tahun . '&status=' . ($st === 'tersedia' ? '' : $st) . '&q=' . rawurlencode($r['nomor_surat'])); }
    elseif ($kelas === 'sel-bebas') { $href = site_url('ambil?nomor=' . $i); }
    else { $href = null; }
    if ($kelas === 'sel-bebas') { $href = site_url('ambil?nomor=' . $i); }
?>
    <?php if ($href): ?><a role="listitem" class="sel <?= $kelas ?>" href="<?= e($href) ?>" title="<?= e($judulSel) ?>"><?= $i ?></a>
    <?php else: ?><span role="listitem" class="sel <?= $kelas ?>" title="<?= e($judulSel) ?>"><?= $i ?></span><?php endif; ?>
<?php endfor; ?>
    <a role="listitem" class="sel sel-berikut" href="<?= site_url('ambil') ?>" title="Nomor berikutnya"><?= $berikut ?></a>
</div>
<?php endif; ?>
