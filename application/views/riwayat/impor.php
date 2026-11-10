<section class="panel sempit">
    <h2>Impor riwayat surat dari sheet lama</h2>
    <p>Fitur ini memasukkan surat-surat yang sudah pernah dibuat lewat mail merge ke dalam aplikasi, sehingga
       dapat dicari, direkap, dan nomornya tercatat. Berkas dibaca dari sheet <strong>Data Utama</strong> pada "Data mail".</p>
    <ol class="langkah">
        <li>Buka "Data mail", lalu pilih File, Download, Microsoft Excel (.xlsx).</li>
        <li>Unggah berkasnya di bawah ini. Anda akan melihat pratinjau lebih dahulu, dan belum ada data yang berubah.</li>
        <li>Bila pratinjau sudah sesuai, konfirmasi untuk memasukkan datanya.</li>
    </ol>
    <form method="post" action="<?= site_url('riwayat/pratinjau') ?>" enctype="multipart/form-data" class="form">
        <?= csrf_field() ?>
        <label class="field"><span>Berkas Excel atau CSV</span><input type="file" name="berkas" accept=".xlsx,.csv,text/csv" required></label>
        <div class="aksi"><button class="btn btn-primary" type="submit">Lihat pratinjau</button>
            <a class="btn" href="<?= site_url('nomor') ?>">Batal</a></div>
    </form>
    <p class="muted">Sebaiknya impor register nomor lebih dahulu (menu Register nomor, Impor register lama), agar nomor kosong dan nomor lama sudah tercatat.
       Nomor yang sudah ada di aplikasi tidak ditimpa.</p>
</section>
