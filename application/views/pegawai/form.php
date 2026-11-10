<section class="panel sempit">
<?php if ($errors): ?><div class="flash flash-bad" role="alert"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= site_url('pegawai/simpan') ?>" class="form">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
    <label class="field"><span>Nama lengkap dengan gelar</span><input type="text" name="nama" value="<?= e($p['nama']) ?>" required maxlength="150"></label>
    <label class="field"><span>NIP</span><input type="text" name="nip" value="<?= e($p['nip']) ?>" inputmode="numeric" maxlength="30">
        <small>Kosongkan bila tidak ada NIP.</small></label>
    <label class="field"><span>Pangkat/Golongan</span><input type="text" name="pangkat_golongan" value="<?= e($p['pangkat_golongan']) ?>" maxlength="80" placeholder="Penata Muda - III/a"></label>
    <label class="field"><span>Jabatan</span><input type="text" name="jabatan" value="<?= e($p['jabatan']) ?>" required maxlength="200" placeholder="Statistisi Ahli Pertama"></label>
    <label class="field"><span>Unit kerja</span><input type="text" name="unit_kerja" value="<?= e($p['unit_kerja']) ?>" maxlength="150" placeholder="BPS Provinsi Kalimantan Barat"></label>
    <div class="aksi"><button class="btn btn-primary" type="submit">Simpan pegawai</button><a class="btn" href="<?= site_url('pegawai') ?>">Batal</a></div>
</form>
</section>
