<form method="get" class="saring" action="<?= site_url('pegawai') ?>">
    <label class="field"><span>Cari</span>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nama, NIP, jabatan, atau unit kerja"></label>
    <label class="field"><span>Status</span>
        <select name="status"><option value="aktif">Aktif</option>
            <option value="nonaktif"<?= $status ? '' : ' selected' ?>>Nonaktif</option></select></label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button>
        <a class="btn" href="<?= site_url('pegawai/impor') ?>"><?= ikon('unggah') ?>Impor CSV</a>
        <a class="btn btn-primary" href="<?= site_url('pegawai/tambah') ?>"><?= ikon('tambah') ?>Tambah pegawai</a></div>
</form>
<?php if (!$rows): ?>
    <p class="kosong"><?= ikon('kosong') ?>Tidak ada pegawai<?= $q !== '' ? ' yang cocok dengan pencarian' : '' ?>. <a href="<?= site_url('pegawai/impor') ?>">Impor dari CSV</a> atau tambahkan satu per satu.</p>
<?php else: ?>
<div class="tabel-bungkus"><table class="data">
    <thead><tr><th>Nama</th><th>NIP</th><th>Pangkat/Gol.</th><th>Jabatan</th><th>Unit kerja</th><th class="angka-kanan">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
        <tr><td><?= e($p['nama']) ?></td><td class="nomor"><?= e($p['nip']) ?></td><td><?= e($p['pangkat_golongan']) ?></td>
            <td><?= e($p['jabatan']) ?></td><td><?= e($p['unit_kerja']) ?></td>
            <td class="aksi-baris">
                <a class="btn btn-ikon" href="<?= site_url('pegawai/ubah/' . $p['id']) ?>" title="Ubah data pegawai" aria-label="Ubah <?= e($p['nama']) ?>"><?= ikon('ubah') ?></a>
                <form method="post" action="<?= site_url('pegawai/status/' . $p['id']) ?>" data-konfirmasi="<?= $p['aktif'] ? 'Nonaktifkan pegawai ini? Surat lama tidak berubah.' : 'Aktifkan kembali pegawai ini?' ?>">
                    <?= csrf_field() ?><button class="btn btn-ikon<?= $p['aktif'] ? ' bahaya' : '' ?>" type="submit" title="<?= $p['aktif'] ? 'Nonaktifkan' : 'Aktifkan kembali' ?>" aria-label="<?= $p['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?> <?= e($p['nama']) ?>"><?= ikon($p['aktif'] ? 'daya' : 'pulihkan') ?></button></form></td></tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php $this->load->view('layout/halaman', ['hal' => $hal, 'per' => $per, 'total' => $total,
    'param' => ['q' => $q, 'status' => $status ? '' : 'nonaktif'], 'url' => 'pegawai']); ?>
<?php endif; ?>
