<section class="panel sempit">
<?php if ($hasil): ?>
    <h2>Impor selesai</h2>
    <p><?= (int) $hasil['baru'] ?> pegawai baru, <?= (int) $hasil['ubah'] ?> diperbarui, <?= (int) $hasil['lewat'] ?> baris dilewati karena NIP atau nama kosong<?= (int) $hasil['luar'] ? ', ' . (int) $hasil['luar'] . ' baris dilewati karena bukan wilayah provinsi' : '' ?>.</p>
    <?php if ($hasil['sinkron']): ?>
    <p><?= (int) $hasil['hapus'] ?> pegawai yang tidak ada di berkas dihapus<?= (int) $hasil['nonaktif'] ? ', ' . (int) $hasil['nonaktif'] . ' dinonaktifkan karena sudah tercatat pada surat tugas (riwayat surat tetap utuh)' : '' ?>.</p>
    <?php endif; ?>
    <div class="aksi"><a class="btn btn-primary" href="<?= site_url('pegawai') ?>">Lihat data pegawai</a></div>
<?php else: ?>
    <p>Berkas CSV harus memiliki baris judul: <code>nip, nama, pangkat_golongan, jabatan, unit_kerja</code>.
       Judul kolom dari sheet daftar pegawai juga dikenali (<code>Gol.Akhir</code>, <code>Tim Kerja</code>, <code>Wilayah</code>); bila ada kolom <code>Wilayah</code>, hanya baris berwilayah provinsi yang dimasukkan.
       Pegawai dengan NIP yang sudah ada akan diperbarui. <a href="<?= site_url('pegawai/contoh') ?>">Unduh contoh berkas</a>.</p>
    <form method="post" action="<?= site_url('pegawai/proses_impor') ?>" enctype="multipart/form-data" class="form">
        <?= csrf_field() ?>
        <label class="field"><span>Berkas CSV</span><input type="file" name="berkas" accept=".csv,text/csv" required></label>
        <label class="centang"><input type="checkbox" name="sinkron" value="1">
            Hapus pegawai yang tidak ada di berkas ini (yang sudah tercatat pada surat tugas hanya dinonaktifkan)</label>
        <div class="aksi"><button class="btn btn-primary" type="submit">Impor</button><a class="btn" href="<?= site_url('pegawai') ?>">Batal</a></div>
    </form>
<?php endif; ?>
</section>
