<section class="panel sempit">
    <h2><?= $edit ? 'Ubah pengguna' : 'Tambah pengguna' ?></h2>
    <form method="post" action="<?= site_url('master/pengguna_simpan') ?>" class="form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
        <label class="field"><span>Username</span><input type="text" name="username" value="<?= $edit ? e($edit['username']) : '' ?>" required maxlength="50" autocomplete="off"></label>
        <label class="field"><span>Nama</span><input type="text" name="nama" value="<?= $edit ? e($edit['nama']) : '' ?>" required maxlength="150"></label>
        <label class="field"><span>Peran akun</span><select name="role">
            <option value="operator"<?= $edit && $edit['role'] === 'operator' ? ' selected' : '' ?>>Operator: membuat dan mencetak surat</option>
            <option value="admin"<?= $edit && $edit['role'] === 'admin' ? ' selected' : '' ?>>Admin: semua fitur dan pengelolaan</option></select></label>
        <label class="field"><span><?= $edit ? 'Kata sandi baru' : 'Kata sandi awal' ?></span>
            <input type="password" name="password" minlength="8" autocomplete="new-password" <?= $edit ? '' : 'required' ?>>
            <?php if ($edit): ?><small>Kosongkan bila tidak diganti.</small><?php endif; ?></label>
        <label class="centang"><input type="checkbox" name="aktif" value="1"<?= !$edit || $edit['aktif'] ? ' checked' : '' ?>> Akun aktif</label>
        <div class="aksi"><button class="btn btn-primary" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="btn" href="<?= site_url('master/pengguna') ?>">Batal</a><?php endif; ?></div>
    </form>
</section>
<div class="tabel-bungkus"><table class="data">
    <thead><tr><th>Username</th><th>Nama</th><th>Akun</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
        <tr><td class="nomor"><?= e($r['username']) ?></td><td><?= e($r['nama']) ?></td><td><?= $r['role'] === 'admin' ? 'Admin' : 'Operator' ?></td>
            <td><?= $r['aktif'] ? '<span class="badge badge-ok">Aktif</span>' : '<span class="badge badge-mute">Nonaktif</span>' ?></td>
            <td class="aksi-baris"><a class="btn btn-ikon" href="<?= site_url('master/pengguna?ubah=' . $r['id']) ?>" title="Ubah" aria-label="Ubah"><?= ikon('ubah') ?></a></td></tr>
    <?php endforeach; ?></tbody>
</table></div>
