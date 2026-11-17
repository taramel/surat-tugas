<section class="panel lebar-form hasil-nomor">
    <p class="muted">Nomor surat tugas</p>
    <div class="nomor-hasil" id="nomor-teks"><?= e($b['nomor_surat']) ?></div>
    <div class="aksi">
        <?php if ($b['status'] === 'terpakai' && $b['template_nama']): ?>
        <a class="btn btn-primary" href="<?= site_url('ambil/unduh/' . $b['id']) ?>">Unduh file Word</a>
        <?php endif; ?>
        <button type="button" class="btn" id="salin-nomor">Salin nomor</button>
        <a class="btn" href="<?= site_url('ambil') ?>">Ambil nomor lagi</a>
    </div>
    <?php if ($b['status'] === 'dibatalkan'): ?><div class="flash flash-bad" style="margin-top:1rem">Nomor ini dibatalkan. Alasan: <?= e($b['alasan_batal']) ?></div><?php endif; ?>
    <dl class="kv" style="margin-top:1.25rem">
        <dt>Tanggal surat</dt><dd><?= e(tgl_indo($b['tanggal_surat'])) ?></dd>
        <dt>Jenis surat</dt><dd><?= e($b['template_nama']) ?></dd>
        <dt>Uraian</dt><dd><?= e($b['uraian']) ?></dd>
        <dt>Diambil oleh</dt><dd><?= e($b['pembuat']) ?></dd>
    </dl>
    <p class="muted">File Word sudah berisi nomor, tanggal, penandatangan, dan uraian kegiatan. Lengkapi bagian yang disorot kuning
        (pengirim surat rujukan, tanggal pelaksanaan, nama pegawai, dan seterusnya) langsung di Word.
        Salah ambil nomor atau surat batal? Buka menu <a href="<?= site_url('register') ?>">Register</a> lalu pilih Batalkan.</p>
</section>
<?php $this->load->view('layout/arsip'); ?>
