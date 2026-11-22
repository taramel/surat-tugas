<?php $bulanNama = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; ?>
<div class="aksi-atas">
    <a class="btn btn-primary" href="<?= site_url('cuti/tambah') ?>"><?= ikon('tambah') ?>Catat cuti</a>
    <a class="btn" href="<?= site_url('cuti/kalender?tahun=' . $f['tahun'] . ($f['bulan'] ? '&bulan=' . $f['bulan'] : '')) ?>"><?= ikon('kalender') ?>Kalender</a>
    <a class="btn" href="<?= site_url('cuti/saldo?tahun=' . $f['tahun']) ?>"><?= ikon('laporan') ?>Saldo cuti</a>
</div>

<form class="saring" method="get" action="<?= site_url('cuti') ?>">
    <label class="field"><span>Cari pegawai</span><input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Nama pegawai"></label>
    <label class="field"><span>Tahun</span><input type="number" name="tahun" value="<?= (int) $f['tahun'] ?>" min="2000" max="2100"></label>
    <label class="field"><span>Bulan</span>
        <select name="bulan"><option value="0">Semua bulan</option>
            <?php foreach ($bulanNama as $i => $n): ?><option value="<?= $i + 1 ?>"<?= (int) $f['bulan'] === $i + 1 ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
        </select></label>
    <label class="field"><span>Jenis</span>
        <select name="jenis"><option value="0">Semua jenis</option>
            <?php foreach ($jenis as $j): ?><option value="<?= (int) $j['id'] ?>"<?= (int) $f['jenis'] === (int) $j['id'] ? ' selected' : '' ?>><?= e($j['kode']) ?> - <?= e($j['nama']) ?></option><?php endforeach; ?>
        </select></label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button></div>
</form>

<?php if (!$rows): ?>
    <p class="kosong"><?= ikon('kosong') ?>Belum ada catatan cuti untuk penyaring ini.</p>
<?php else: ?>
<div class="tabel-bungkus">
    <table class="data">
        <thead><tr><th>Pegawai</th><th>Jenis</th><th>Tanggal</th><th class="angka-kanan">Hari</th><th>Keterangan</th><th class="angka-kanan">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= $r['pegawai_id'] ? '<a href="' . site_url('pegawai/riwayat/' . $r['pegawai_id']) . '">' . e($r['nama_pegawai']) . '</a>' : e($r['nama_pegawai']) ?></td>
                <td><span class="kode kode-<?= e($r['kategori']) ?>" title="<?= e($r['jenis_nama']) ?>"><?= e($r['kode']) ?></span> <span class="muted"><?= e($r['jenis_nama']) ?></span></td>
                <td><?= e(rentang_tanggal([['mulai' => $r['mulai'], 'selesai' => $r['selesai']]])) ?></td>
                <td class="angka-kanan"><?= e(angka_hari($r['jumlah_hari'])) ?></td>
                <td class="potong" title="<?= e($r['keterangan']) ?>">
                    <?= $r['keterangan'] ? e($r['keterangan']) : '<span class="muted">-</span>' ?>
                    <?php if ($r['tautan_dokumen']): ?> <a href="<?= e($r['tautan_dokumen']) ?>" target="_blank" rel="noopener noreferrer" title="Buka dokumen"><?= ikon('tautan') ?></a><?php endif; ?>
                </td>
                <td class="aksi-baris">
                    <a class="btn btn-ikon" href="<?= site_url('cuti/ubah/' . $r['id']) ?>" title="Ubah" aria-label="Ubah catatan <?= e($r['nama_pegawai']) ?>"><?= ikon('ubah') ?></a>
                    <form method="post" action="<?= site_url('cuti/hapus/' . $r['id']) ?>" data-konfirmasi="Hapus catatan <?= e($r['jenis_nama']) ?> untuk <?= e($r['nama_pegawai']) ?>?">
                        <?= csrf_field() ?>
                        <button class="btn btn-ikon bahaya" type="submit" title="Hapus" aria-label="Hapus catatan <?= e($r['nama_pegawai']) ?>"><?= ikon('hapus') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $this->load->view('layout/halaman', ['total' => $total, 'per' => $per, 'hal' => $hal, 'url' => 'cuti', 'param' => $f]); ?>
<?php endif; ?>
