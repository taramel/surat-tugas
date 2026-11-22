<section class="panel">
    <h2><?= count($rows) ?> surat tugas diterbitkan</h2>
    <div class="tabel-bungkus"><table class="data">
        <thead><tr><th>Nomor</th><th>Penerima</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td class="nomor"><a href="<?= site_url('surat/detail/' . $r['id']) ?>"><?= e($r['nomor_surat']) ?></a></td>
                <td><?= e($r['penerima']) ?></td>
                <td class="aksi-baris"><a class="btn btn-ikon" href="<?= site_url('surat/word/' . $r['id']) ?>" title="Unduh Word (dapat diedit)" aria-label="Unduh Word <?= e($r['nomor_surat']) ?>"><?= ikon('unduh') ?></a><a class="btn btn-ikon" href="<?= site_url('surat/pdf/' . $r['id']) ?>" target="_blank" rel="noopener" title="Buka PDF" aria-label="Buka PDF"><?= ikon('cetak') ?></a></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <div class="aksi" style="margin-top:1rem">
        <a class="btn btn-primary" href="<?= site_url('surat/word_banyak?ids=' . $ids) ?>"><?= ikon('unduh') ?>Unduh semua Word (ZIP)</a>
        <a class="btn" href="<?= site_url('surat/pdf_banyak?ids=' . $ids) ?>" target="_blank" rel="noopener"><?= ikon('cetak') ?>Buka semua dalam satu PDF</a>
        <a class="btn" href="<?= site_url('surat/pdf_banyak?ids=' . $ids . '&unduh=1') ?>">Unduh satu PDF</a>
        <a class="btn" href="<?= site_url('surat') ?>">Ke daftar surat</a>
    </div>
</section>
