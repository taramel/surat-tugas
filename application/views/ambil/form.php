<?php if ($errors): ?>
<div class="flash flash-bad" role="alert"><strong>Nomor belum diambil.</strong>
    <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if (!$templat): ?>
<div class="flash flash-warn">Belum ada template Word yang aktif. Minta admin menambahkannya di menu Kelola template.</div>
<?php endif; ?>

<section class="panel lebar-form">
    <p class="muted"><a href="<?= site_url('surat') ?>">&larr; Kembali ke Surat tugas</a></p>
    <h2>Ambil nomor surat tugas</h2>
    <p class="muted">Isi data singkat di bawah ini. Nomor terbit saat tombol ditekan, lalu unduh file Word-nya dan lengkapi isi surat langsung di Word. Untuk surat yang langsung memuat data pegawai dan PDF resmi, gunakan <a href="<?= site_url('surat/tambah') ?>">Buat surat tugas</a>.</p>
    <form method="post" action="<?= site_url('ambil/proses') ?>" class="form">
        <?= csrf_field() ?>
        <div class="grid-form">
            <label class="field"><span>Jenis surat</span>
                <select name="template_nama" required>
                <?php foreach ($templat as $n): ?><option value="<?= e($n) ?>"<?= $v['template_nama'] === $n ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
                </select></label>
            <label class="field"><span>Tanggal surat</span>
                <input type="date" name="tanggal_surat" value="<?= e($v['tanggal_surat']) ?>" required>
                <small>Tahun pada tanggal ini menentukan tahun nomor.</small></label>
            <label class="field lebar"><span>Uraian kegiatan</span>
                <textarea name="uraian" rows="2" required maxlength="1000" placeholder="Pelatihan Fungsional Penguatan Pranata Komputer"><?= e($v['uraian']) ?></textarea>
                <small>Cukup nama kegiatannya. Kata "Mengikuti Kegiatan" sudah ada pada template.</small></label>
        </div>
        <details class="lipat">
            <summary>Pilihan lanjutan: memakai nomor kosong tertentu</summary>
            <label class="field"><span>Nomor urut</span>
                <input type="number" name="nomor_pilih" min="1" list="nomor-bebas" value="<?= e($v['nomor_pilih']) ?>" placeholder="Otomatis">
                <small>Kosongkan untuk memakai nomor berikutnya (B-<?= (int) $terakhir + 1 ?>). Isi hanya untuk memakai nomor kosong yang tertinggal.</small>
                <datalist id="nomor-bebas"><?php foreach ($bebas as $n): ?><option value="<?= $n ?>"><?php endforeach; ?></datalist></label>
        </details>
        <div class="aksi aksi-simpan"><button class="btn btn-primary" type="submit">Ambil nomor</button></div>
    </form>
    <p class="muted">Nomor terakhir tahun <?= (int) $tahun ?>: B-<?= (int) $terakhir ?>. Ada <?= count($bebas) ?> nomor kosong yang masih dapat dipakai.
        <a href="<?= site_url('register/kotak') ?>">Lihat peta nomor</a></p>
</section>

<?php if ($saya): ?>
<section class="panel">
    <h2>Nomor yang baru saya ambil</h2>
    <div class="tabel-bungkus"><table class="data">
        <thead><tr><th>Nomor</th><th>Tanggal</th><th>Uraian</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($saya as $r): ?>
            <tr><td class="nomor"><a href="<?= site_url('ambil/hasil/' . $r['id']) ?>"><?= e($r['nomor_surat']) ?></a></td>
                <td><?= e(tgl_indo($r['tanggal_surat'])) ?></td><td class="potong"><?= e($r['uraian']) ?></td>
                <td><?= label_status($r['status']) ?></td>
                <td class="aksi-baris"><?php if ($r['status'] === 'terpakai'): ?><a class="btn btn-ikon" href="<?= site_url('ambil/unduh/' . $r['id']) ?>" title="Unduh Word" aria-label="Unduh Word"><?= ikon('unduh') ?></a><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php endif; ?>
