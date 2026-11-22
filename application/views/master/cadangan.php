<section class="panel sempit">
    <h2>Cadangan database</h2>
    <p>Unduh salinan seluruh data (surat, pegawai, register nomor, pengguna, dan log) sebagai berkas SQL.
       Simpan berkas ini di tempat yang aman dan di luar komputer server, dan lakukan secara berkala.</p>
    <p class="muted">Cadangan memuat kata sandi dalam bentuk hash dan data pegawai, jadi perlakukan sebagai berkas rahasia.
       Untuk memulihkan, impor berkas ini ke MySQL lewat phpMyAdmin atau perintah <code>mysql</code>.</p>
    <form method="post" action="<?= site_url('master/cadangan_unduh') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-primary" type="submit">Unduh cadangan sekarang</button>
    </form>
</section>
