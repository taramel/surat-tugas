<form method="get" class="saring" action="<?= site_url('register') ?>">
    <label class="field"><span>Cari</span>
        <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Nomor, uraian, atau pembuat"></label>
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
            <option value="terpakai"<?= $f['status'] === 'terpakai' ? ' selected' : '' ?>>Terpakai</option>
            <option value="arsip"<?= $f['status'] === 'arsip' ? ' selected' : '' ?>>Arsip (dari sheet lama)</option>
            <option value="dibatalkan"<?= $f['status'] === 'dibatalkan' ? ' selected' : '' ?>>Dibatalkan</option>
            <option value="tersedia"<?= $f['status'] === 'tersedia' ? ' selected' : '' ?>>Kosong</option></select></label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button>
        <a class="btn" href="<?= site_url('register/kotak?tahun=' . $tahun_stat) ?>"><?= ikon('dashboard') ?>Peta nomor</a>
        <a class="btn" href="<?= site_url('register/ekspor?' . e(http_build_query($f))) ?>"><?= ikon('unduh') ?>Unduh CSV</a>
        <a class="btn btn-primary" href="<?= site_url('ambil') ?>"><?= ikon('tambah') ?>Ambil nomor</a></div>
</form>

<p class="muted">Tahun <?= (int) $tahun_stat ?>: nomor terakhir B-<?= (int) $terakhir ?>, <?= (int) $kosong ?> nomor kosong yang masih dapat dipakai.</p>

<?php if (!$rows): ?>
    <p class="kosong"><?= ikon('kosong') ?>Tidak ada nomor yang cocok dengan penyaringan ini.</p>
<?php else: ?>
<div class="tabel-bungkus"><table class="data">
    <thead><tr><th>Nomor</th><th>Tanggal</th><th>Uraian</th><th>Pembuat</th><th>Status</th><th class="angka-kanan">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr id="n<?= (int) $r['id'] ?>">
            <td class="nomor"><?= e($r['nomor_surat']) ?></td>
            <td><?= e(tgl_indo($r['tanggal_surat'])) ?></td>
            <td class="potong" title="<?= e($r['uraian']) ?>"><?= e($r['uraian']) ?></td>
            <td><?= e($r['pembuat']) ?></td>
            <td><?= label_status($r['status']) ?><?php if ($r['status'] === 'dibatalkan' && $r['alasan_batal']): ?><div class="muted"><?= e($r['alasan_batal']) ?></div><?php endif; ?></td>
            <td class="aksi-baris">
                <?php if ($r['status'] === 'terpakai' && $r['template_nama']): ?><a class="btn btn-ikon" href="<?= site_url('ambil/unduh/' . $r['id']) ?>" title="Unduh Word" aria-label="Unduh Word <?= e($r['nomor_surat']) ?>"><?= ikon('unduh') ?></a><?php endif; ?>
                <?php if ($r['status'] !== 'dibatalkan'): ?>
                    <a class="btn btn-ikon" href="<?= site_url('register/ubah/' . $r['id']) ?>" title="Ubah" aria-label="Ubah <?= e($r['nomor_surat']) ?>"><?= ikon('ubah') ?></a>
                    <details class="batal-inline"><summary class="btn btn-ikon bahaya" title="Batalkan nomor" aria-label="Batalkan <?= e($r['nomor_surat']) ?>"><?= ikon('batal') ?></summary>
                        <form method="post" action="<?= site_url('register/batal/' . $r['id']) ?>" data-konfirmasi="Batalkan nomor ini? Nomornya tidak dapat dipakai lagi.">
                            <?= csrf_field() ?><input type="hidden" name="kembali" value="register">
                            <input type="text" name="alasan" placeholder="Alasan pembatalan" required maxlength="300">
                            <button class="btn btn-danger" type="submit">Batalkan</button></form></details>
                <?php elseif ($user['role'] === 'admin'): ?>
                    <form method="post" action="<?= site_url('register/pulihkan/' . $r['id']) ?>" data-konfirmasi="Pulihkan nomor ini?">
                        <?= csrf_field() ?><button class="btn btn-ikon" type="submit" title="Pulihkan nomor" aria-label="Pulihkan <?= e($r['nomor_surat']) ?>"><?= ikon('pulihkan') ?></button></form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php $this->load->view('layout/halaman', ['hal' => $hal, 'per' => $per, 'total' => $total, 'param' => $f, 'url' => 'register']); ?>
<?php endif; ?>
