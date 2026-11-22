<?php if ($errors): ?>
<div class="flash dengan-ikon flash-bad" role="alert"><?= ikon('peringatan') ?><div class="flash-isi"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>
<form method="post" action="<?= site_url('bangkom/simpan') ?>" class="panel lebar-form form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
    <div class="grid-form">
        <label class="field lebar"><span>Pegawai</span>
            <select name="pegawai_id" required><option value="">- Pilih pegawai -</option>
                <?php foreach ($pegawai as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $b['pegawai_id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['nama']) ?><?= $p['unit_kerja'] ? ' (' . e($p['unit_kerja']) . ')' : '' ?></option><?php endforeach; ?>
            </select></label>
        <label class="field lebar"><span>Nama kegiatan pengembangan kompetensi</span><input type="text" name="kegiatan" maxlength="400" value="<?= e($b['kegiatan']) ?>" required></label>
        <label class="field"><span>Tanggal mulai</span><input type="date" name="mulai" value="<?= e($b['mulai']) ?>" required></label>
        <label class="field"><span>Sampai tanggal <small>(kosongkan bila satu hari)</small></span><input type="date" name="selesai" value="<?= e($b['selesai'] === $b['mulai'] ? '' : $b['selesai']) ?>"></label>
        <label class="field"><span>Jam pelajaran (JP)</span><input type="text" name="jp" inputmode="numeric" value="<?= e($b['jp']) ?>" required></label>
        <label class="field"><span>Penyelenggara</span>
            <select name="penyelenggara"><option value="">-</option>
                <?php foreach (['BPS', 'Non BPS'] as $o): ?><option value="<?= $o ?>"<?= $b['penyelenggara'] === $o ? ' selected' : '' ?>><?= $o ?></option><?php endforeach; ?>
            </select></label>
        <label class="field"><span>Nama penyelenggara <small>(bila Non BPS)</small></span><input type="text" name="nama_penyelenggara" maxlength="150" value="<?= e($b['nama_penyelenggara']) ?>"></label>
        <label class="field"><span>Bentuk <small>(Klasikal, Non Klasikal, ...)</small></span><input type="text" name="bentuk" maxlength="60" value="<?= e($b['bentuk']) ?>"></label>
        <label class="field"><span>Kategori</span><input type="text" name="kategori" maxlength="100" value="<?= e($b['kategori']) ?>" placeholder="Contoh: Seminar/Konferensi/Sarasehan/Workshop/Lokakarya"></label>
        <label class="field"><span>Jenis</span><input type="text" name="jenis" maxlength="80" value="<?= e($b['jenis']) ?>" placeholder="Contoh: Manajemen SDM"></label>
        <label class="field lebar"><span>Tautan sertifikat <small>(opsional)</small></span><input type="text" name="tautan_sertifikat" maxlength="500" value="<?= e($b['tautan_sertifikat']) ?>" placeholder="https://drive.google.com/..."></label>
    </div>
    <div class="aksi aksi-simpan">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="<?= site_url('bangkom') ?>">Batal</a>
    </div>
</form>
