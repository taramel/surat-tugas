<?php if ($errors): ?>
<div class="flash dengan-ikon flash-bad" role="alert"><?= ikon('peringatan') ?><div class="flash-isi"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>
<form method="post" action="<?= site_url('cuti/simpan') ?>" class="panel lebar-form form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <div class="grid-form">
        <label class="field lebar"><span>Pegawai</span>
            <select name="pegawai_id" required>
                <option value="">- Pilih pegawai -</option>
                <?php foreach ($pegawai as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $c['pegawai_id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['nama']) ?><?= $p['unit_kerja'] ? ' (' . e($p['unit_kerja']) . ')' : '' ?></option><?php endforeach; ?>
            </select></label>
        <label class="field lebar"><span>Jenis</span>
            <select name="jenis_id" required>
                <option value="">- Pilih jenis -</option>
                <?php foreach ($jenis as $j): ?><option value="<?= (int) $j['id'] ?>"<?= (int) $c['jenis_id'] === (int) $j['id'] ? ' selected' : '' ?>><?= e($j['kode']) ?> - <?= e($j['nama']) ?></option><?php endforeach; ?>
            </select></label>
        <label class="field"><span>Tanggal mulai</span><input type="date" name="mulai" value="<?= e($c['mulai']) ?>" required></label>
        <label class="field"><span>Sampai tanggal <small>(kosongkan bila satu hari)</small></span><input type="date" name="selesai" value="<?= e($c['selesai'] === $c['mulai'] ? '' : $c['selesai']) ?>"></label>
        <label class="field"><span>Jumlah hari <small>(kosongkan untuk dihitung otomatis)</small></span><input type="text" name="jumlah_hari" inputmode="decimal" value="<?= e($c['jumlah_hari'] !== '' ? angka_hari($c['jumlah_hari']) : '') ?>" placeholder="Hari kerja Senin-Jumat"></label>
        <label class="field"><span>Tautan dokumen <small>(opsional)</small></span><input type="text" name="tautan_dokumen" value="<?= e($c['tautan_dokumen']) ?>" placeholder="https://drive.google.com/..."></label>
        <label class="field lebar"><span>Keterangan <small>(opsional)</small></span><input type="text" name="keterangan" maxlength="300" value="<?= e($c['keterangan']) ?>"></label>
    </div>
    <p class="muted">Jumlah hari dihitung dari hari kerja Senin sampai Jumat. Libur nasional tidak dikurangi otomatis; isi jumlah hari bila perlu disesuaikan.</p>
    <div class="aksi aksi-simpan">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="<?= site_url('cuti') ?>">Batal</a>
    </div>
</form>
