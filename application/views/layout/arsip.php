<?php if (!empty($arsip_nomor_id)): ?>
<section class="panel" id="arsip">
    <h2><?= ikon('klip') ?> Arsip berkas</h2>
    <p class="muted">Simpan surat yang sudah ditandatangani (PDF, Word, atau foto/pindaian) agar mudah dicari kembali. Maksimal 10 MB per berkas.</p>
    <?php if ($arsip): ?>
    <div class="tabel-bungkus"><table class="data">
        <thead><tr><th>Berkas</th><th>Keterangan</th><th>Diunggah</th><th class="angka-kanan">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($arsip as $a):
            $ext = strtolower(pathinfo($a['berkas'], PATHINFO_EXTENSION));
            $bisaLihat = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true);
            $boleh = $user['role'] === 'admin' || (int) $a['diunggah_oleh'] === (int) $user['id']; ?>
            <tr>
                <td><?= ikon('surat') ?> <?= e($a['nama_asli']) ?> <span class="muted">(<?= e(ukuran_berkas($a['ukuran'])) ?>)</span></td>
                <td><?= $a['keterangan'] ? e($a['keterangan']) : '<span class="muted">-</span>' ?></td>
                <td class="muted"><?= e(substr($a['created_at'], 0, 16)) ?><br><?= e($a['pengunggah']) ?></td>
                <td class="aksi-baris">
                    <?php if ($bisaLihat): ?><a class="btn btn-ikon" href="<?= site_url('arsip/unduh/' . $a['id'] . '?lihat=1') ?>" target="_blank" rel="noopener" title="Buka" aria-label="Buka <?= e($a['nama_asli']) ?>"><?= ikon('lihat') ?></a><?php endif; ?>
                    <a class="btn btn-ikon" href="<?= site_url('arsip/unduh/' . $a['id']) ?>" title="Unduh" aria-label="Unduh <?= e($a['nama_asli']) ?>"><?= ikon('unduh') ?></a>
                    <?php if ($boleh): ?>
                    <form method="post" action="<?= site_url('arsip/hapus/' . $a['id']) ?>" data-konfirmasi="Hapus berkas &quot;<?= e($a['nama_asli']) ?>&quot; dari arsip?">
                        <?= csrf_field() ?><input type="hidden" name="kembali" value="<?= e($kembali) ?>">
                        <button class="btn btn-ikon bahaya" type="submit" title="Hapus" aria-label="Hapus <?= e($a['nama_asli']) ?>"><?= ikon('hapus') ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php else: ?>
    <p class="kosong"><?= ikon('kosong') ?>Belum ada berkas tersimpan untuk surat ini.</p>
    <?php endif; ?>
    <form method="post" action="<?= site_url('arsip/unggah/' . (int) $arsip_nomor_id) ?>" enctype="multipart/form-data" class="form form-arsip">
        <?= csrf_field() ?><input type="hidden" name="kembali" value="<?= e($kembali) ?>">
        <label class="field"><span>Pilih berkas</span><input type="file" name="berkas" accept=".pdf,.docx,.jpg,.jpeg,.png" required></label>
        <label class="field"><span>Keterangan <small>(opsional)</small></span><input type="text" name="keterangan" maxlength="200" placeholder="Contoh: Surat bertanda tangan"></label>
        <div class="aksi"><button class="btn btn-primary" type="submit"><?= ikon('unggah') ?>Simpan ke arsip</button></div>
    </form>
</section>
<?php endif; ?>
