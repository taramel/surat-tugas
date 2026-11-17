<?php
$v = function ($k, $d = '') use ($s) { return isset($s[$k]) && $s[$k] !== null ? $s[$k] : $d; };
$aksi = $mode === 'tambah' ? site_url('surat/simpan') : site_url('surat/perbarui/' . $s['id']);
$peranJson = json_encode(array_map(function ($p) { return ['id' => $p['id'], 'nama' => $p['nama']]; }, $peran), JSON_UNESCAPED_UNICODE);
?>
<?php if ($errors): ?>
<div class="flash flash-bad" role="alert"><strong>Surat belum disimpan.</strong>
    <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= $aksi ?>" class="form-surat" id="form-surat" data-peran="<?= e($peranJson) ?>">
<?= csrf_field() ?>

<section class="panel">
    <h2>Dasar penugasan</h2>
    <p class="muted">Isi bila penugasan berasal dari surat atau permohonan. Boleh dikosongkan.</p>
    <div class="grid-form">
        <fieldset class="field pilihan"><legend>Asal permintaan</legend>
            <label><input type="radio" name="asal" value="eksternal"<?= $v('asal', 'eksternal') === 'eksternal' ? ' checked' : '' ?>> Surat dari instansi lain</label>
            <label><input type="radio" name="asal" value="internal"<?= $v('asal') === 'internal' ? ' checked' : '' ?>> Permohonan internal</label>
        </fieldset>
        <label class="field"><span>Pengirim</span>
            <input type="text" name="rujukan_pengirim" value="<?= e($v('rujukan_pengirim')) ?>" maxlength="200" placeholder="Kepala Pusdiklat BPS"></label>
        <label class="field"><span>Nomor surat rujukan</span>
            <input type="text" name="rujukan_nomor" value="<?= e($v('rujukan_nomor')) ?>" maxlength="120"></label>
        <label class="field lebar"><span>Hal</span>
            <input type="text" name="rujukan_hal" value="<?= e($v('rujukan_hal')) ?>" maxlength="300" placeholder="Pemanggilan Peserta Pelatihan ..."></label>
        <label class="field lebar"><span>Kalimat Menimbang a (opsional)</span>
            <textarea name="menimbang_a" rows="2" placeholder="bahwa dalam rangka ..., maka dipandang perlu menyesuaikan dengan jadwal kegiatan tersebut;"><?= e($v('menimbang_a')) ?></textarea>
            <small>Kosongkan untuk memakai kalimat standar "bahwa merujuk surat dari ... nomor ... hal ...". Isi bila penugasan tidak berasal dari surat, misalnya tugas belajar.</small></label>
    </div>
</section>

<section class="panel">
    <h2>Penugasan</h2>
    <label class="field"><span>Untuk (isi penugasan)</span>
        <textarea name="untuk" rows="3" required><?= e($v('untuk')) ?></textarea></label>

    <h3>Tanggal pelaksanaan</h3>
    <p class="muted">Isi <strong>tanggal mulai</strong> saja untuk kegiatan satu hari. Isi <strong>sampai tanggal</strong> untuk kegiatan beberapa hari berturut-turut.
        Untuk tanggal yang tidak berurutan, misalnya 4, 7, dan 9, pakai kotak &ldquo;Tanggal terpisah&rdquo; di bawah.</p>
    <div id="baris-tanggal">
    <?php foreach ($tanggal as $t): ?>
        <div class="baris-tanggal">
            <label class="field"><span>Tanggal mulai</span><input type="date" name="mulai[]" value="<?= e($t['mulai']) ?>"></label>
            <label class="field"><span>Sampai tanggal <small>(kosongkan bila satu hari)</small></span><input type="date" name="selesai[]" value="<?= e($t['selesai'] === $t['mulai'] ? '' : $t['selesai']) ?>"></label>
            <button type="button" class="btn btn-ghost hapus-tanggal" title="Hapus baris tanggal ini"><?= ikon('hapus') ?>Hapus</button>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="aksi">
        <button type="button" class="btn" id="tambah-tanggal"><?= ikon('tambah') ?>Tambah baris tanggal</button>
    </div>
    <div class="tanggal-terpisah">
        <strong>Tanggal terpisah</strong>
        <div class="baris-tanggal">
            <label class="field"><span>Bulan</span><input type="month" id="tp-bulan"></label>
            <label class="field"><span>Tanggal <small>(pisahkan dengan koma)</small></span><input type="text" id="tp-hari" inputmode="numeric" placeholder="Contoh: 4, 7, 9" autocomplete="off"></label>
            <button type="button" class="btn" id="tp-tambah"><?= ikon('tambah') ?>Tambahkan</button>
        </div>
        <small id="tp-pesan" class="muted" role="status"></small>
    </div>
    <p class="pratinjau-tanggal" id="pratinjau-tanggal" aria-live="polite" hidden><span class="muted">Tertulis di surat:</span> <strong id="pratinjau-isi"></strong></p>

    <h3>Pegawai yang ditugaskan</h3>
    <div class="cari-bungkus">
        <label class="field"><span>Cari pegawai (nama, NIP, atau jabatan)</span>
            <input type="search" id="cari-pegawai" autocomplete="off" placeholder="Ketik minimal 2 huruf"></label>
        <ul class="hasil-cari" id="hasil-cari" hidden></ul>
    </div>
    <ul class="daftar-penerima" id="daftar-penerima">
    <?php foreach ($penerima as $p): ?>
        <li class="baris-penerima" data-id="<?= (int) $p['pegawai_id'] ?>">
            <input type="hidden" name="pegawai_id[]" value="<?= (int) $p['pegawai_id'] ?>">
            <div class="info"><strong><?= e($p['nama']) ?></strong>
                <span><?= e(implode(' | ', array_filter([$p['nip'], $p['pangkat_golongan'], $p['jabatan']]))) ?></span></div>
            <select name="peran_id[]" aria-label="Peran <?= e($p['nama']) ?>">
                <option value="">Tanpa peran</option>
                <?php foreach ($peran as $r): ?><option value="<?= $r['id'] ?>"<?= (int) $p['peran_id'] === (int) $r['id'] ? ' selected' : '' ?>><?= e($r['nama']) ?></option><?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-ghost hapus">Hapus</button>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php if ($mode === 'tambah'): ?>
    <label class="centang" id="blok-massal" hidden><input type="checkbox" name="massal" value="1" id="massal"<?= !empty($s['massal']) ? ' checked' : '' ?>>
        Terbitkan satu surat untuk setiap pegawai (nomor berurutan, tanpa lampiran)</label>
    <?php endif; ?>
    <p class="kosong" id="penerima-kosong"<?= $penerima ? ' hidden' : '' ?>>Belum ada pegawai. Satu pegawai menghasilkan surat tanpa tabel; dua atau lebih menghasilkan surat dengan tabel.</p>
</section>

<section class="panel">
    <h2>Pengesahan</h2>
    <div class="grid-form">
        <label class="field"><span>Tanggal surat</span>
            <input type="date" id="tanggal-surat" name="tanggal_surat" value="<?= e($v('tanggal_surat', date('Y-m-d'))) ?>" required></label>
        <label class="field"><span>Tempat</span>
            <input type="text" name="tempat" value="<?= e($v('tempat', 'Pontianak')) ?>" maxlength="80"></label>
        <label class="field"><span>Penandatangan</span>
            <select name="penandatangan_id" required>
            <?php foreach ($ttd as $t): ?>
                <?php $hariIni = date('Y-m-d'); $berlaku = $t['aktif_dari'] <= $hariIni && (!$t['aktif_sampai'] || $t['aktif_sampai'] >= $hariIni); ?>
                <option value="<?= $t['id'] ?>"<?= (int) $v('penandatangan_id') === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['nama']) ?> - <?= e($t['jabatan']) ?><?= $berlaku ? '' : ' (tidak berlaku hari ini: ' . e(tgl_indo($t['aktif_dari'])) . ' s.d. ' . ($t['aktif_sampai'] ? e(tgl_indo($t['aktif_sampai'])) : 'sekarang') . ')' ?></option>
            <?php endforeach; ?>
            </select></label>
        <?php if ($mode === 'tambah'): ?>
        <label class="field"><span>Nomor urut</span>
            <input type="number" name="nomor_pilih" id="nomor-pilih" min="1" list="nomor-bebas" value="<?= e($v('nomor_pilih')) ?>" placeholder="Otomatis">
            <small>Kosongkan untuk memakai nomor berikutnya. Isi hanya untuk memakai nomor kosong yang tertinggal.</small>
            <datalist id="nomor-bebas"><?php foreach ($bebas as $n): ?><option value="<?= $n ?>"><?php endforeach; ?></datalist></label>
        <?php endif; ?>
        <label class="field lebar"><span>Catatan internal</span>
            <input type="text" name="catatan" value="<?= e($v('catatan')) ?>" maxlength="300" placeholder="Tidak dicetak pada surat"></label>
    </div>
</section>

<div class="aksi aksi-simpan">
    <button class="btn btn-primary" type="submit"><?= $mode === 'tambah' ? 'Terbitkan surat' : 'Simpan perubahan' ?></button>
    <a class="btn" href="<?= $mode === 'tambah' ? site_url('surat') : site_url('surat/detail/' . $s['id']) ?>">Batal</a>
</div>
</form>
