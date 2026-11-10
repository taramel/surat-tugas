<?php
$jumlahHal = max(1, (int) ceil($total / $per));
if ($jumlahHal > 1):
    $q = array_filter($param, function ($v) { return $v !== '' && $v !== null; });
    $link = function ($h) use ($q, $url) { return site_url($url) . '?' . http_build_query($q + ['hal' => $h]); };
    $awal = ($hal - 1) * $per + 1;
    $akhir = min((int) $total, $hal * $per);
    // nomor halaman yang ditampilkan: pertama, terakhir, dan sekitar halaman aktif
    $tampil = array_unique(array_filter([1, $hal - 1, $hal, $hal + 1, $jumlahHal], function ($h) use ($jumlahHal) { return $h >= 1 && $h <= $jumlahHal; }));
    sort($tampil);
?>
<nav class="halaman" aria-label="Halaman">
    <span class="halaman-info">Menampilkan <?= $awal ?>&ndash;<?= $akhir ?> dari <?= (int) $total ?> data</span>
    <div class="halaman-daftar">
        <?php if ($hal > 1): ?><a class="btn btn-kecil" href="<?= e($link($hal - 1)) ?>" rel="prev" aria-label="Halaman sebelumnya" title="Sebelumnya"><?= ikon('panah-kiri') ?></a><?php else: ?><span class="btn btn-kecil" aria-disabled="true"><?= ikon('panah-kiri') ?></span><?php endif; ?>
        <?php $prev = 0; foreach ($tampil as $h): ?>
            <?php if ($prev && $h - $prev > 1): ?><span class="titik">&hellip;</span><?php endif; ?>
            <a class="btn btn-kecil" href="<?= e($link($h)) ?>"<?= $h === (int) $hal ? ' aria-current="page"' : '' ?>><?= $h ?></a>
        <?php $prev = $h; endforeach; ?>
        <?php if ($hal < $jumlahHal): ?><a class="btn btn-kecil" href="<?= e($link($hal + 1)) ?>" rel="next" aria-label="Halaman berikutnya" title="Berikutnya"><?= ikon('panah-kanan') ?></a><?php else: ?><span class="btn btn-kecil" aria-disabled="true"><?= ikon('panah-kanan') ?></span><?php endif; ?>
    </div>
</nav>
<?php endif; ?>
