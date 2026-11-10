<section class="panel sempit">
    <h2>Impor selesai</h2>
    <ul class="ringkas ringkas-kecil">
        <li><span class="angka"><?= (int) $r['surat'] ?></span><span class="label">Surat diimpor</span></li>
        <li><span class="angka"><?= (int) $r['pegawai_baru'] ?></span><span class="label">Pegawai baru</span></li>
    </ul>
    <p class="muted"><?= (int) $r['peran_baru'] ?> peran baru dan <?= (int) $r['ttd_baru'] ?> penandatangan baru dibuat.</p>
    <div class="aksi"><a class="btn btn-primary" href="<?= site_url('surat') ?>">Lihat daftar surat</a>
        <a class="btn" href="<?= site_url('nomor') ?>">Lihat register nomor</a></div>
</section>
