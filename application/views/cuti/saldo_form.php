<?php if ($errors): ?>
<div class="flash dengan-ikon flash-bad" role="alert"><?= ikon('peringatan') ?><div class="flash-isi"><?= e($errors[0]) ?></div></div>
<?php endif; ?>
<form method="post" action="<?= site_url('cuti/saldo_simpan') ?>" class="panel sempit form">
    <?= csrf_field() ?>
    <input type="hidden" name="pegawai_id" value="<?= (int) $p['id'] ?>">
    <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
    <h2><?= e($p['nama']) ?> &middot; <?= (int) $tahun ?></h2>
    <label class="field"><span>Jatah cuti tahun <?= (int) $tahun ?></span><input type="text" name="jatah" inputmode="decimal" value="<?= e(angka_hari($s['jatah'])) ?>" required></label>
    <label class="field"><span>Sisa cuti tahun lalu (N-1)</span><input type="text" name="sisa_n1" inputmode="decimal" value="<?= e(angka_hari($s['sisa_n1'])) ?>" required></label>
    <label class="field"><span>Sisa cuti dua tahun lalu (N-2)</span><input type="text" name="sisa_n2" inputmode="decimal" value="<?= e(angka_hari($s['sisa_n2'])) ?>" required></label>
    <p class="muted">Cuti tahunan terpakai tahun ini: <strong><?= e(angka_hari($terpakai)) ?> hari</strong> (dihitung dari catatan cuti).</p>
    <div class="aksi aksi-simpan">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="<?= site_url('cuti/saldo?tahun=' . (int) $tahun) ?>">Batal</a>
    </div>
</form>
