<section class="panel">
    <h2><?= $edit ? 'Ubah versi dasar hukum' : 'Tambah versi dasar hukum' ?></h2>
    <p class="muted">Bagian "Mengingat" pada surat mengikuti tanggal surat: dipakai versi dengan tanggal mulai terbaru yang tidak melewati tanggal surat.
       Saat peraturan berganti, tambahkan versi baru. Surat lama tetap mencetak dasar hukum yang berlaku pada masanya.</p>
    <form method="post" action="<?= site_url('master/dasar_hukum_simpan') ?>" class="form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
        <div class="grid-form">
            <label class="field"><span>Berlaku mulai</span><input type="date" name="berlaku_mulai" value="<?= $edit ? e($edit['berlaku_mulai']) : '' ?>" required></label>
            <label class="field"><span>Keterangan</span><input type="text" name="keterangan" value="<?= $edit ? e($edit['keterangan']) : '' ?>" maxlength="150" placeholder="Peraturan BPS Nomor ... Tahun ..."></label>
            <label class="field lebar"><span>Mengingat (satu dasar hukum per baris)</span>
                <textarea name="mengingat" rows="10" required><?= $edit ? e($edit['mengingat']) : '' ?></textarea></label>
        </div>
        <div class="aksi"><button class="btn btn-primary" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="btn" href="<?= site_url('master/dasar_hukum') ?>">Batal</a><?php endif; ?></div>
    </form>
</section>
<?php foreach ($rows as $r): ?>
<section class="panel">
    <div class="kepala-detail" style="margin-bottom:.5rem">
        <div><strong>Berlaku mulai <?= e(tgl_indo($r['berlaku_mulai'])) ?></strong>
            <div class="muted"><?= e($r['keterangan']) ?></div></div>
        <a class="btn btn-ikon" href="<?= site_url('master/dasar_hukum?ubah=' . $r['id']) ?>" title="Ubah" aria-label="Ubah"><?= ikon('ubah') ?></a>
    </div>
    <ol class="catatan"><?php foreach (preg_split('/\R/', $r['mengingat']) as $b): if (trim($b) !== ''): ?><li><?= e($b) ?></li><?php endif; endforeach; ?></ol>
</section>
<?php endforeach; ?>
