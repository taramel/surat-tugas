<?php if (!empty($f['flag'])): ?>
<div class="flash flash-warn">Menampilkan surat bertanggal setelah pelaksanaan pertama. <a href="<?= site_url('surat') ?>">Hapus penyaringan</a></div>
<?php endif; ?>
<form method="get" class="saring" action="<?= site_url('surat') ?>">
    <?php if (!empty($f['flag'])): ?><input type="hidden" name="flag" value="<?= e($f['flag']) ?>"><?php endif; ?>
    <label class="field"><span>Cari</span>
        <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Nomor, nama pegawai, atau kegiatan"></label>
    <label class="field"><span>Tahun</span>
        <select name="tahun"><option value="">Semua</option>
        <?php foreach ($tahun_opsi as $t): ?><option value="<?= $t ?>"<?= (string) $f['tahun'] === (string) $t ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select></label>
    <label class="field"><span>Bulan</span>
        <select name="bulan"><option value="">Semua</option>
        <?php for ($b = 1; $b <= 12; $b++): ?><option value="<?= $b ?>"<?= (string) $f['bulan'] === (string) $b ? ' selected' : '' ?>><?= bulan_indo($b) ?></option><?php endfor; ?>
        </select></label>
    <label class="field"><span>Status</span>
        <select name="status"><option value="">Semua</option>
            <option value="aktif"<?= $f['status'] === 'aktif' ? ' selected' : '' ?>>Aktif</option>
            <option value="dibatalkan"<?= $f['status'] === 'dibatalkan' ? ' selected' : '' ?>>Dibatalkan</option></select></label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button>
        <a class="btn" href="<?= site_url('ambil') ?>" title="Ambil nomor dan file Word saja, tanpa mengisi data pegawai"><?= ikon('nomor') ?>Ambil nomor saja</a>
        <a class="btn btn-primary" href="<?= site_url('surat/tambah') ?>"><?= ikon('tambah') ?>Buat surat tugas</a></div>
</form>

<?php if (!$rows): ?>
    <p class="kosong"><?= ikon('kosong') ?>Tidak ada surat yang cocok dengan penyaringan ini.</p>
<?php else: ?>
<div class="tabel-bungkus"><table class="data">
    <thead><tr><th>Nomor</th><th>Tanggal surat</th><th>Penerima</th><th>Kegiatan</th><th>Pelaksanaan</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td class="nomor"><a href="<?= site_url('surat/detail/' . $r['id']) ?>"><?= e($r['nomor_surat']) ?></a></td>
            <td><?= e(tgl_indo($r['tanggal_surat'])) ?></td>
            <td><?= e($r['penerima']) ?></td>
            <td class="potong"><?= e($r['untuk']) ?></td>
            <td><?= e($r['rentang']) ?></td>
            <td><?= label_status($r['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php $this->load->view('layout/halaman', ['hal' => $hal, 'per' => $per, 'total' => $total, 'param' => $f, 'url' => 'surat']); ?>
<?php endif; ?>
