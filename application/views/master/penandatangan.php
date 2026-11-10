<section class="panel sempit">
    <h2><?= $edit ? 'Ubah penandatangan' : 'Tambah penandatangan' ?></h2>
    <p class="muted">Isi tanggal akhir berlaku saat pejabat berganti. Surat baru hanya menawarkan penandatangan yang berlaku pada tanggal surat.</p>
    <form method="post" action="<?= site_url('master/penandatangan_simpan') ?>" class="form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
        <label class="field"><span>Nama dan gelar</span><input type="text" name="nama" value="<?= $edit ? e($edit['nama']) : '' ?>" required maxlength="150"></label>
        <label class="field"><span>NIP</span><input type="text" name="nip" value="<?= $edit ? e($edit['nip']) : '' ?>" maxlength="30"></label>
        <label class="field"><span>Jabatan pada surat</span><input type="text" name="jabatan" value="<?= $edit ? e($edit['jabatan']) : '' ?>" required maxlength="200" placeholder="Kepala Badan Pusat Statistik Provinsi Kalimantan Barat"></label>
        <label class="field"><span>Berlaku mulai</span><input type="date" name="aktif_dari" value="<?= $edit ? e($edit['aktif_dari']) : '' ?>" required></label>
        <label class="field"><span>Berlaku sampai</span><input type="date" name="aktif_sampai" value="<?= $edit ? e($edit['aktif_sampai']) : '' ?>"></label>
        <div class="aksi"><button class="btn btn-primary" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="btn" href="<?= site_url('master/penandatangan') ?>">Batal</a><?php endif; ?></div>
    </form>
</section>
<div class="tabel-bungkus"><table class="data">
    <thead><tr><th>Nama</th><th>Jabatan</th><th>Berlaku</th><th class="angka-kanan">Aksi</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['nama']) ?></td><td><?= e($r['jabatan']) ?></td>
            <td><?= e(tgl_indo($r['aktif_dari'])) ?> sampai <?= $r['aktif_sampai'] ? e(tgl_indo($r['aktif_sampai'])) : 'sekarang' ?></td>
            <td class="aksi-baris"><a class="btn btn-ikon" href="<?= site_url('master/penandatangan?ubah=' . $r['id']) ?>" title="Ubah" aria-label="Ubah <?= e($r['nama']) ?>"><?= ikon('ubah') ?></a></td></tr>
    <?php endforeach; ?></tbody>
</table></div>
