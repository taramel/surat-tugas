<section class="panel sempit">
<?php if ($errors): ?><div class="flash flash-bad" role="alert"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<p class="muted">Nomor surat tidak dapat diubah. Yang dapat dikoreksi hanya uraian dan tanggalnya.</p>
<form method="post" action="<?= site_url('register/simpan/' . $b['id']) ?>" class="form">
    <?= csrf_field() ?>
    <label class="field"><span>Nomor surat</span><input type="text" value="<?= e($b['nomor_surat']) ?>" disabled></label>
    <label class="field"><span>Uraian kegiatan</span><textarea name="uraian" rows="3" required maxlength="1000"><?= e($b['uraian']) ?></textarea></label>
    <label class="field"><span>Tanggal surat</span><input type="date" name="tanggal_surat" value="<?= e($b['tanggal_surat']) ?>"<?= $b['status'] === 'terpakai' ? ' required' : '' ?>>
        <small>Tahun harus <?= (int) $b['tahun'] ?>, sesuai tahun nomor.</small></label>
    <div class="aksi"><button class="btn btn-primary" type="submit">Simpan</button><a class="btn" href="<?= site_url('register') ?>">Batal</a></div>
</form>
</section>
<?php $this->load->view('layout/arsip'); ?>
