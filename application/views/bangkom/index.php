<div class="aksi-atas">
    <a class="btn btn-primary" href="<?= site_url('bangkom/tambah') ?>"><?= ikon('tambah') ?>Catat Bangkom</a>
    <a class="btn" href="<?= site_url('bangkom/rekap?tahun=' . $f['tahun']) ?>"><?= ikon('laporan') ?>Rekap JP</a>
</div>
<form class="saring" method="get" action="<?= site_url('bangkom') ?>">
    <label class="field"><span>Cari pegawai atau kegiatan</span><input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Nama pegawai atau kegiatan"></label>
    <label class="field"><span>Tahun</span><input type="number" name="tahun" value="<?= (int) $f['tahun'] ?>" min="2000" max="2100"></label>
    <label class="field"><span>Kategori</span>
        <select name="kategori"><option value="">Semua kategori</option>
            <?php foreach ($kategori as $k): ?><option value="<?= e($k) ?>"<?= $f['kategori'] === $k ? ' selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?>
        </select></label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button></div>
</form>
<?php if (!$rows): ?>
    <p class="kosong"><?= ikon('kosong') ?>Belum ada catatan Bangkom untuk penyaring ini.</p>
<?php else: ?>
<div class="tabel-bungkus">
    <table class="data">
        <thead><tr><th>Pegawai</th><th>Kegiatan</th><th>Tanggal</th><th class="angka-kanan">JP</th><th>Penyelenggara</th><th class="angka-kanan">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= $r['pegawai_id'] ? '<a href="' . site_url('pegawai/riwayat/' . $r['pegawai_id']) . '">' . e($r['nama_pegawai']) . '</a>' : e($r['nama_pegawai']) ?></td>
                <td class="potong" title="<?= e($r['kegiatan']) ?>"><?= e($r['kegiatan']) ?><?php if ($r['kategori']): ?><br><span class="muted"><?= e($r['kategori']) ?></span><?php endif; ?></td>
                <td><?= e(rentang_tanggal([['mulai' => $r['mulai'], 'selesai' => $r['selesai']]])) ?></td>
                <td class="angka-kanan"><?= (int) $r['jp'] ?></td>
                <td><?= $r['penyelenggara'] ? '<span class="badge badge-mute">' . e($r['penyelenggara']) . '</span>' : '<span class="muted">-</span>' ?>
                    <?= $r['nama_penyelenggara'] ? '<br><span class="muted">' . e($r['nama_penyelenggara']) . '</span>' : '' ?></td>
                <td class="aksi-baris">
                    <?php if ($r['tautan_sertifikat']): ?><a class="btn btn-ikon" href="<?= e($r['tautan_sertifikat']) ?>" target="_blank" rel="noopener noreferrer" title="Buka sertifikat" aria-label="Buka sertifikat <?= e($r['nama_pegawai']) ?>"><?= ikon('tautan') ?></a><?php endif; ?>
                    <a class="btn btn-ikon" href="<?= site_url('bangkom/ubah/' . $r['id']) ?>" title="Ubah" aria-label="Ubah catatan <?= e($r['nama_pegawai']) ?>"><?= ikon('ubah') ?></a>
                    <form method="post" action="<?= site_url('bangkom/hapus/' . $r['id']) ?>" data-konfirmasi="Hapus catatan &quot;<?= e($r['kegiatan']) ?>&quot; untuk <?= e($r['nama_pegawai']) ?>?">
                        <?= csrf_field() ?>
                        <button class="btn btn-ikon bahaya" type="submit" title="Hapus" aria-label="Hapus catatan <?= e($r['nama_pegawai']) ?>"><?= ikon('hapus') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $this->load->view('layout/halaman', ['total' => $total, 'per' => $per, 'hal' => $hal, 'url' => 'bangkom', 'param' => $f]); ?>
<?php endif; ?>
