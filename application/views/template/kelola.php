<section class="panel">
    <h2>Tambah versi template</h2>
    <p class="muted">Unggah berkas Word (.docx). Template dengan nama yang sama membentuk satu deret versi: saat nomor diambil, dipakai versi dengan tanggal mulai
       terbaru yang tidak melewati tanggal surat. Gunakan ini saat dasar hukum atau format surat berganti, tanpa mengubah surat lama.</p>
    <form method="post" action="<?= site_url('template/simpan') ?>" enctype="multipart/form-data" class="form">
        <?= csrf_field() ?>
        <div class="grid-form">
            <label class="field"><span>Nama template</span>
                <input type="text" name="nama" list="nama-ada" required maxlength="150" placeholder="Surat Tugas - satu penerima">
                <datalist id="nama-ada"><?php foreach ($nama_ada as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist>
                <small>Pilih nama yang sudah ada untuk menambah versi baru, atau ketik nama baru.</small></label>
            <label class="field"><span>Berlaku mulai</span><input type="date" name="berlaku_mulai" required></label>
            <label class="field lebar"><span>Keterangan (opsional)</span><input type="text" name="catatan" maxlength="300" placeholder="Dasar hukum Peraturan BPS Nomor ... Tahun ..."></label>
            <label class="field lebar"><span>Berkas Word (.docx)</span><input type="file" name="berkas" accept=".docx" required></label>
        </div>
        <div class="aksi"><button class="btn btn-primary" type="submit">Simpan template</button></div>
    </form>
    <h3>Penanda yang diisi otomatis</h3>
    <p class="muted">Tulis penanda berikut di dalam berkas Word. Aplikasi menggantinya saat nomor diambil:</p>
    <div class="tabel-bungkus"><table class="data kecil"><tbody>
        <tr><td class="nomor">{{NOMOR}}</td><td>Nomor surat lengkap, misalnya B-738/61513/KP.650/2026</td></tr>
        <tr><td class="nomor">{{TANGGAL}}</td><td>Tanggal surat, misalnya 5 Oktober 2026</td></tr>
        <tr><td class="nomor">{{TEMPAT}}</td><td>Tempat penerbitan, misalnya Pontianak</td></tr>
        <tr><td class="nomor">{{URAIAN}}</td><td>Uraian kegiatan yang diketik pada saat mengambil nomor</td></tr>
        <tr><td class="nomor">{{TTD_NAMA}}</td><td>Nama penandatangan yang berlaku pada tanggal surat</td></tr>
        <tr><td class="nomor">{{TTD_JABATAN}}</td><td>Jabatan penandatangan</td></tr>
        <tr><td class="nomor">{{TTD_NIP}}</td><td>NIP penandatangan (bila diisi)</td></tr>
        <tr><td class="nomor">{{TAHUN}}, {{PEMBUAT}}</td><td>Tahun nomor dan nama pengguna yang mengambil nomor</td></tr>
    </tbody></table></div>
</section>

<section class="panel">
    <h2>Daftar versi template</h2>
    <?php if (!$semua): ?><p class="kosong">Belum ada template.</p><?php else: ?>
    <div class="tabel-bungkus"><table class="data">
        <thead><tr><th>Nama</th><th>Mulai berlaku</th><th>Keterangan</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($semua as $t): ?>
            <tr><td><?= e($t['nama']) ?></td><td><?= e(tgl_indo($t['berlaku_mulai'])) ?></td><td class="potong"><?= e($t['catatan']) ?></td>
                <td><?= $t['aktif'] ? '<span class="badge badge-ok">Aktif</span>' : '<span class="badge badge-mute">Nonaktif</span>' ?></td>
                <td class="aksi-baris">
                    <a class="btn btn-ikon" href="<?= site_url('template/mentah/' . $t['id']) ?>" title="Unduh berkas asli" aria-label="Unduh berkas asli"><?= ikon('unduh') ?></a>
                    <form method="post" action="<?= site_url('template/status/' . $t['id']) ?>" data-konfirmasi="<?= $t['aktif'] ? 'Nonaktifkan versi ini?' : 'Aktifkan kembali versi ini?' ?>">
                        <?= csrf_field() ?><button class="btn btn-ikon<?= $t['aktif'] ? ' bahaya' : '' ?>" type="submit" title="<?= $t['aktif'] ? 'Nonaktifkan' : 'Aktifkan kembali' ?>" aria-label="<?= $t['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?> versi ini"><?= ikon($t['aktif'] ? 'daya' : 'pulihkan') ?></button></form></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
