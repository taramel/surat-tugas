<section class="panel sempit">
<?php if ($galat): ?>
    <div class="flash flash-bad" role="alert"><strong>Pembaruan gagal.</strong> <?= e($galat) ?><br>
        Perubahan sebelumnya yang sudah berhasil tidak dibatalkan; Anda dapat menekan tombol pembaruan lagi setelah masalahnya diperbaiki.</div>
<?php endif; ?>
<?php if ($hasil !== null): ?>
    <h2>Database sudah diperbarui</h2>
    <?php if ($hasil): ?>
        <p>Perubahan yang dilakukan:</p>
        <ul class="catatan"><?php foreach ($hasil as $h): ?><li><?= e($h) ?></li><?php endforeach; ?></ul>
    <?php else: ?>
        <p>Tidak ada perubahan yang diperlukan. Database sudah versi terbaru.</p>
    <?php endif; ?>
    <div class="aksi"><a class="btn btn-primary" href="<?= site_url('/') ?>">Lanjut ke aplikasi</a></div>
<?php elseif (!$rencana): ?>
    <h2>Database sudah versi terbaru</h2>
    <p>Tidak ada pembaruan yang diperlukan.</p>
    <div class="aksi"><a class="btn btn-primary" href="<?= site_url('/') ?>">Ke Ringkasan</a></div>
<?php else: ?>
    <h2>Database perlu diperbarui</h2>
    <p>Aplikasi ini lebih baru daripada database yang terpasang (versi <?= (int) $versi ?>, dibutuhkan versi <?= (int) $versi_baru ?>).
       Sebelum diperbarui, sebagian halaman dapat tidak terbuka dan pengambilan nomor dapat gagal.</p>
    <p>Perubahan yang akan dilakukan (data yang sudah ada tidak dihapus):</p>
    <ul class="catatan"><?php foreach ($rencana as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
    <p class="muted">Bila sudah ada data penting, ekspor database lebih dahulu lewat phpMyAdmin (tab Export). Perubahan di atas hanya menambah, tidak menghapus data.</p>
    <form method="post" action="<?= site_url('pembaruan/terapkan') ?>" class="aksi">
        <?= csrf_field() ?>
        <button class="btn btn-primary" type="submit">Perbarui database sekarang</button>
    </form>
<?php endif; ?>
</section>
