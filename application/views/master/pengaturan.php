<form method="post" action="<?= site_url('master/pengaturan_simpan') ?>" class="form">
<?= csrf_field() ?>
<section class="panel sempit">
    <h2>Format Penomoran Surat</h2>
    <label class="field"><span>Kode satuan kerja (BPS Kalbar)</span>
        <input type="text" name="kode_satker" value="<?= e(isset($set['kode_satker']) ? $set['kode_satker'] : '61513') ?>" required></label>
    <label class="field"><span>Kode klasifikasi</span>
        <input type="text" name="kode_klasifikasi" value="<?= e(isset($set['kode_klasifikasi']) ? $set['kode_klasifikasi'] : '650') ?>" required></label>
    <label class="field"><span>Format nomor</span>
        <input type="text" name="format_nomor" value="<?= e(isset($set['format_nomor']) ? $set['format_nomor'] : 'B-{urut}/{satker}/KP.{klas}/{tahun}') ?>" required>
        <small>Variabel yang dapat digunakan: <code>{urut}</code>, <code>{satker}</code>, <code>{klas}</code>, <code>{tahun}</code>. Perubahan hanya berlaku untuk nomor yang baru diterbitkan.</small></label>
    <label class="field"><span>Tempat penerbitan default</span>
        <input type="text" name="tempat_default" value="<?= e(isset($set['tempat_default']) ? $set['tempat_default'] : 'Pontianak') ?>" required>
        <small>Dicetak pada tanda tangan surat tugas, misalnya Pontianak.</small></label>
</section>

<section class="panel sempit">
    <h2>Format Dokumen Surat & Cetak PDF</h2>
    <label class="field"><span>Kop Surat Baris 1</span>
        <input type="text" name="kop_baris1" value="<?= e(isset($set['kop_baris1']) ? $set['kop_baris1'] : 'BADAN PUSAT STATISTIK') ?>" required></label>
    <label class="field"><span>Kop Surat Baris 2</span>
        <input type="text" name="kop_baris2" value="<?= e(isset($set['kop_baris2']) ? $set['kop_baris2'] : 'PROVINSI KALIMANTAN BARAT') ?>" required></label>
    <label class="field"><span>Awalan Penugasan ("Untuk")</span>
        <input type="text" name="awalan_untuk" value="<?= e(isset($set['awalan_untuk']) ? $set['awalan_untuk'] : 'Mengikuti Kegiatan') ?>">
        <small>Contoh: "Mengikuti Kegiatan". Awalan ini akan disematkan di awal kalimat penugasan.</small></label>
    <label class="field"><span>Kalimat Penugasan Tengah</span>
        <input type="text" name="kalimat_memberi_tugas" value="<?= e(isset($set['kalimat_memberi_tugas']) ? $set['kalimat_memberi_tugas'] : 'Memberi Tugas:') ?>" required></label>
    <label class="field"><span>Menimbang b (Standar)</span>
        <textarea name="menimbang_b" rows="2"><?= e(isset($set['menimbang_b']) ? $set['menimbang_b'] : 'bahwa untuk pelaksanaannya perlu dikeluarkan Surat Tugas Kepala BPS Provinsi Kalimantan Barat;') ?></textarea></label>
    <div class="aksi"><button class="btn btn-primary" type="submit">Simpan semua pengaturan</button></div>
</section>
</form>

<p class="muted">Template dokumen Word (.docx) dapat diunduh dan dikelola pada menu <a href="<?= site_url('template/kelola') ?>">Kelola template</a>. Versi dasar hukum (bagian "Mengingat") diatur pada menu <a href="<?= site_url('master/dasar_hukum') ?>">Dasar hukum</a>.</p>
