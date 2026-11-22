<?php $u = $ubah ?: ['id' => 0, 'kode' => '', 'nama' => '', 'kategori' => 'cuti', 'potong_saldo' => 0, 'setengah_hari' => 0, 'aktif' => 1, 'catatan' => '']; ?>
<div class="tabel-bungkus">
    <table class="data">
        <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Potong saldo</th><th>Status</th><th>Catatan</th><th class="angka-kanan">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $j): ?>
            <tr>
                <td><span class="kode kode-<?= e($j['kategori']) ?>"><?= e($j['kode']) ?></span></td>
                <td><?= e($j['nama']) ?><?= $j['setengah_hari'] ? ' <span class="muted">(0,5 hari)</span>' : '' ?></td>
                <td><?= e(ucfirst($j['kategori'])) ?></td>
                <td><?= $j['potong_saldo'] ? 'Ya' : '-' ?></td>
                <td><?= $j['aktif'] ? '<span class="badge badge-ok">Aktif</span>' : '<span class="badge badge-mute">Nonaktif</span>' ?></td>
                <td class="muted"><?= e($j['catatan']) ?></td>
                <td class="aksi-baris"><a class="btn btn-ikon" href="<?= site_url('cuti/jenis?ubah=' . $j['id']) ?>" title="Ubah" aria-label="Ubah <?= e($j['kode']) ?>"><?= ikon('ubah') ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<form method="post" action="<?= site_url('cuti/jenis_simpan') ?>" class="panel lebar-form form">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
    <h2><?= $u['id'] ? 'Ubah jenis ' . e($u['kode']) : 'Tambah jenis baru' ?></h2>
    <div class="grid-form">
        <label class="field"><span>Kode</span><input type="text" name="kode" maxlength="10" value="<?= e($u['kode']) ?>" required></label>
        <label class="field"><span>Nama</span><input type="text" name="nama" maxlength="100" value="<?= e($u['nama']) ?>" required></label>
        <label class="field"><span>Kategori</span>
            <select name="kategori"><?php foreach (['cuti' => 'Cuti', 'penugasan' => 'Penugasan', 'lainnya' => 'Lainnya'] as $k => $n): ?><option value="<?= $k ?>"<?= $u['kategori'] === $k ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Catatan</span><input type="text" name="catatan" maxlength="200" value="<?= e($u['catatan']) ?>"></label>
    </div>
    <label class="centang"><input type="checkbox" name="potong_saldo" value="1"<?= $u['potong_saldo'] ? ' checked' : '' ?>>Memotong saldo cuti tahunan</label>
    <label class="centang"><input type="checkbox" name="setengah_hari" value="1"<?= $u['setengah_hari'] ? ' checked' : '' ?>>Dihitung setengah hari (0,5) per tanggal</label>
    <label class="centang"><input type="checkbox" name="aktif" value="1"<?= $u['aktif'] ? ' checked' : '' ?>>Aktif (muncul pada pilihan)</label>
    <div class="aksi aksi-simpan">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <?php if ($u['id']): ?><a class="btn" href="<?= site_url('cuti/jenis') ?>">Batal</a><?php endif; ?>
    </div>
</form>
