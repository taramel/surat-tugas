<?php $batal = $s['status'] === 'dibatalkan'; ?>
<div class="kepala-detail">
    <div>
        <div class="nomor-besar"><?= e($s['nomor_surat']) ?></div>
        <div><?= label_status($s['status']) ?> <span class="muted">&nbsp;Tanggal surat <?= e(tgl_indo($s['tanggal_surat'])) ?></span></div>
    </div>
    <div class="aksi">
        <?php if (!$s['tidak_lengkap']): ?>
        <a class="btn btn-primary" href="<?= site_url('surat/word/' . $s['id']) ?>" title="Berkas Word dapat disunting sebelum dicetak"><?= ikon('unduh') ?>Unduh Word (dapat diedit)</a>
        <a class="btn" href="<?= site_url('surat/pdf/' . $s['id']) ?>" target="_blank" rel="noopener"><?= ikon('cetak') ?>Buka PDF</a>
        <a class="btn" href="<?= site_url('surat/pdf/' . $s['id'] . '?unduh=1') ?>"><?= ikon('unduh') ?>Unduh PDF</a>
        <?php if (!$batal): ?><a class="btn" href="<?= site_url('surat/ubah/' . $s['id']) ?>"><?= ikon('ubah') ?>Ubah data</a><?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($s['tautan_arsip'])): ?><a class="btn" href="<?= e($s['tautan_arsip']) ?>" target="_blank" rel="noopener noreferrer"><?= ikon('tautan') ?>Dokumen asli (Google Docs)</a><?php endif; ?>
        <a class="btn" href="<?= site_url('surat/salin/' . $s['id']) ?>"><?= ikon('salin') ?>Salin sebagai surat baru</a>
    </div>
</div>

<?php if ($s['tidak_lengkap']): ?>
<div class="flash flash-warn">Surat arsip hasil impor dari sheet lama. Daftar penerima lengkapnya tidak tersedia, sehingga cetak ulang dan perubahan dinonaktifkan.
    <?= !empty($s['tautan_arsip']) ? 'Buka dokumen aslinya lewat tombol di atas.' : '' ?></div>
<?php endif; ?>
<?php if ($batal): ?>
<div class="flash flash-bad">Surat ini dibatalkan. Alasan: <?= e($s['alasan_batal']) ?>. Nomornya tetap tercatat dan tidak dipakai ulang.</div>
<?php endif; ?>

<section class="panel">
    <dl class="kv">
        <dt>Dasar</dt>
        <dd><?php
            $dasar = trim(implode(' ', array_filter([
                $s['rujukan_pengirim'] ? ($s['asal'] === 'internal' ? 'Permohonan ' : 'Surat ') . $s['rujukan_pengirim'] : '',
                $s['rujukan_nomor'] ? 'Nomor ' . $s['rujukan_nomor'] : '',
                $s['rujukan_hal'] ? 'hal ' . $s['rujukan_hal'] : '',
            ])));
            echo $dasar !== '' ? e($dasar) : '<span class="muted">Tidak ada</span>'; ?></dd>
        <dt>Untuk</dt><dd><?= nl2br(e($s['untuk'])) ?></dd>
        <dt>Pelaksanaan</dt><dd><?= e($s['rentang']) ?> <span class="muted">(<?= (int) $s['lama'] ?> hari)</span></dd>
        <dt>Penandatangan</dt><dd><?= e($s['ttd_nama']) ?>, <?= e($s['ttd_jabatan']) ?></dd>
        <dt>Dibuat oleh</dt><dd><?= e($s['pembuat']) ?> <span class="muted"><?= e($s['created_at']) ?></span></dd>
        <?php if ($s['catatan']): ?><dt>Catatan</dt><dd><?= e($s['catatan']) ?></dd><?php endif; ?>
    </dl>
</section>

<section class="panel">
    <h2>Pegawai yang ditugaskan <?php if (!$s['tidak_lengkap']): ?><span class="muted">(<?= count($s['penerima']) ?> orang, format <?= count($s['penerima']) > 1 ? 'dengan tabel' : 'tanpa tabel' ?>)</span><?php endif; ?></h2>
    <div class="tabel-bungkus"><table class="data">
        <thead><tr><th>Nama</th><th>NIP</th><th>Pangkat/Gol.</th><th>Jabatan</th><th>Peran</th></tr></thead>
        <tbody>
        <?php foreach ($s['penerima'] as $p): ?>
            <tr><td><?= e($p['nama']) ?></td><td class="nomor"><?= e($p['nip']) ?></td><td><?= e($p['pangkat_golongan']) ?></td>
                <td><?= e($p['jabatan']) ?></td><td><?= e($p['peran']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>

<section class="panel">
    <h2>Tindakan lain</h2>
    <?php if (!$batal): ?>
    <details class="lipat"><summary>Batalkan surat</summary>
        <form method="post" action="<?= site_url('surat/batal/' . $s['id']) ?>" class="form" data-konfirmasi="Batalkan surat ini? Nomornya tidak dapat dipakai lagi.">
            <?= csrf_field() ?>
            <label class="field"><span>Alasan pembatalan</span><input type="text" name="alasan" required maxlength="300"></label>
            <div class="aksi"><button class="btn btn-danger" type="submit">Batalkan surat</button></div>
        </form>
    </details>
    <?php elseif ($user['role'] === 'admin'): ?>
    <form method="post" action="<?= site_url('surat/pulihkan/' . $s['id']) ?>" data-konfirmasi="Pulihkan surat ini menjadi aktif?">
        <?= csrf_field() ?><button class="btn" type="submit"><?= ikon('pulihkan') ?>Pulihkan surat</button>
    </form>
    <?php else: ?><p class="muted">Hanya admin yang dapat memulihkan surat yang dibatalkan.</p>
    <?php endif; ?>
</section>
<?php $this->load->view('layout/arsip'); ?>
