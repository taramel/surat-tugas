<section class="panel sempit">
    <h2><?= $edit ? 'Ubah peran' : 'Tambah peran' ?></h2>
    <form method="post" action="<?= site_url('master/peran_simpan') ?>" class="form baris-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
        <label class="field"><span>Nama peran</span><input type="text" name="nama" value="<?= $edit ? e($edit['nama']) : '' ?>" required maxlength="80"></label>
        <div class="aksi"><button class="btn btn-primary" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="btn" href="<?= site_url('master/peran') ?>">Batal</a><?php endif; ?></div>
    </form>
</section>
<div class="tabel-bungkus sempit"><table class="data">
    <thead><tr><th>Peran</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['nama']) ?></td><td class="aksi-baris">
            <a class="btn btn-ikon" href="<?= site_url('master/peran?ubah=' . $r['id']) ?>" title="Ubah" aria-label="Ubah"><?= ikon('ubah') ?></a>
            <form method="post" action="<?= site_url('master/peran_hapus/' . $r['id']) ?>" data-konfirmasi="Hapus peran ini?">
                <?= csrf_field() ?><button class="btn btn-ikon bahaya" type="submit" title="Hapus" aria-label="Hapus"><?= ikon('hapus') ?></button></form></td></tr>
    <?php endforeach; ?></tbody>
</table></div>
