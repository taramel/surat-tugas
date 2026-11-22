<div class="aksi-atas">
    <a class="btn" href="<?= site_url('pegawai') ?>"><?= ikon('panah-kiri') ?>Kembali ke data pegawai</a>
    <a class="btn" href="<?= site_url('pegawai/ubah/' . $p['id']) ?>"><?= ikon('ubah') ?>Ubah data</a>
</div>

<div class="kepala-detail">
    <div>
        <div class="nomor-besar"><?= e($p['nama']) ?></div>
        <div class="muted"><?= $p['nip'] ? 'NIP ' . e($p['nip']) : 'NIP belum diisi' ?><?= $p['pangkat_golongan'] ? ' &middot; ' . e($p['pangkat_golongan']) : '' ?></div>
        <div class="muted"><?= e($p['jabatan']) ?><?= $p['unit_kerja'] ? ' &middot; ' . e($p['unit_kerja']) : '' ?></div>
    </div>
    <div><?= $p['aktif'] ? '<span class="badge badge-ok">Aktif</span>' : '<span class="badge badge-mute">Nonaktif</span>' ?></div>
</div>

<section class="ringkas ringkas-kecil" aria-label="Ringkasan penugasan">
    <div><span class="ikon-kartu"><?= ikon('surat') ?></span><span class="angka"><?= (int) $jumlah_aktif ?></span><span class="label">Penugasan aktif<?= $tahun ? ' tahun ' . (int) $tahun : '' ?></span></div>
    <div><span class="ikon-kartu"><?= ikon('riwayat') ?></span><span class="angka"><?= (int) $hari_bertugas ?></span><span class="label">Hari bertugas</span></div>
    <div><span class="ikon-kartu <?= $bentrok ? 'waspada' : 'ok' ?>"><?= ikon($bentrok ? 'peringatan' : 'sukses') ?></span><span class="angka"><?= count($bentrok) ?></span><span class="label">Tanggal bertabrakan</span></div>
</section>

<?php if ($bentrok): ?>
<div class="flash dengan-ikon flash-warn" role="alert">
    <?= ikon('peringatan') ?>
    <div class="flash-isi">Pegawai ini memiliki penugasan dengan tanggal yang bertabrakan:
        <ul>
        <?php foreach ($bentrok as $b): ?>
            <li><a href="<?= site_url('surat/detail/' . $b[0]['id']) ?>"><?= e($b[0]['nomor_surat']) ?></a> (<?= e($b[0]['rentang']) ?>)
                dan <a href="<?= site_url('surat/detail/' . $b[1]['id']) ?>"><?= e($b[1]['nomor_surat']) ?></a> (<?= e($b[1]['rentang']) ?>)</li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<form class="saring" method="get" action="<?= site_url('pegawai/riwayat/' . $p['id']) ?>">
    <label class="field"><span>Tahun</span>
        <select name="tahun">
            <option value="0">Semua tahun</option>
            <?php foreach ($daftar_tahun as $t): ?><option value="<?= (int) $t ?>"<?= (int) $t === (int) $tahun ? ' selected' : '' ?>><?= (int) $t ?></option><?php endforeach; ?>
        </select></label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button></div>
</form>

<?php if (!$rows): ?>
    <p class="kosong"><?= ikon('kosong') ?>Belum ada penugasan yang tercatat untuk pegawai ini.</p>
<?php else: ?>
<div class="tabel-bungkus">
    <table class="data">
        <thead><tr><th>Nomor surat</th><th>Kegiatan</th><th>Pelaksanaan</th><th>Peran</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="nomor"><a href="<?= site_url('surat/detail/' . $r['id']) ?>"><?= e($r['nomor_surat']) ?></a><br><span class="muted"><?= e(tgl_indo($r['tanggal_surat'])) ?></span></td>
                <td class="potong" title="<?= e($r['untuk']) ?>"><?= e($r['untuk']) ?></td>
                <td><?= e($r['rentang']) ?></td>
                <td><?= $r['peran'] ? e($r['peran']) : '<span class="muted">-</span>' ?></td>
                <td><?= label_status($r['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
