<?php
$banyak = count($s['penerima']) > 1;
$p = $s['penerima'][0];
$set = array_merge([
    'kop_baris1' => '', 'kop_baris2' => '', 'kalimat_memberi_tugas' => 'Memberi Tugas:',
    'menimbang_a' => '', 'menimbang_b' => '', 'mengingat' => '',
], $set);

$adaRujukan = $s['rujukan_pengirim'] || $s['rujukan_nomor'] || $s['rujukan_hal'];
$menimbang = [];
if (!empty($s['menimbang_a'])) {
    $menimbang[] = $s['menimbang_a'];
} elseif ($adaRujukan && trim($set['menimbang_a']) !== '') {
    $tpl = $set['menimbang_a'];
    foreach (['pengirim' => 'dari', 'nomor' => 'nomor', 'hal' => 'hal'] as $kunci => $kata) {
        $nilai = $kunci === 'nomor' ? $s['rujukan_nomor'] : ($kunci === 'hal' ? $s['rujukan_hal'] : $s['rujukan_pengirim']);
        if (!$nilai) {
            $tpl = preg_replace('/\s*' . $kata . '\s+\{' . $kunci . '\}/u', '', $tpl);
        }
    }
    $menimbang[] = strtr($tpl, [
        '{pengirim}' => $s['rujukan_pengirim'], '{nomor}' => $s['rujukan_nomor'], '{hal}' => $s['rujukan_hal'],
    ]);
}
if (trim($set['menimbang_b']) !== '') {
    $menimbang[] = $set['menimbang_b'];
}
$sumberMengingat = (isset($s['mengingat_teks']) && trim($s['mengingat_teks']) !== '') ? $s['mengingat_teks'] : (isset($set['mengingat']) ? $set['mengingat'] : '');
$mengingat = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $sumberMengingat))));

// Kalimat "Untuk" mengikuti surat asli: satu penerima diakhiri "sebagai {peran}." dan banyak penerima tanpa titik akhir.
$untukTeks = rtrim(trim($s['untuk']), ". \t");
if ($s['rentang'] !== '') {
    $untukTeks .= ' pada tanggal ' . $s['rentang'];
}
if (!$banyak) {
    if (!empty($p['peran'])) {
        $untukTeks .= ' sebagai ' . $p['peran'];
    }
    $untukTeks .= '.';
}
$tglSurat = tgl_indo($s['tanggal_surat']);
?>
<?php if ($s['status'] === 'dibatalkan'): ?><div class="batal">DIBATALKAN</div><?php endif; ?>

<div class="kop">
    <?php if ($logo): ?><img src="<?= $logo ?>" alt=""><?php endif; ?>
    <div class="k"><?= e($set['kop_baris1']) ?></div>
    <div class="k"><?= e($set['kop_baris2']) ?></div>
</div>

<div class="judul">SURAT TUGAS<br>NOMOR <?= e($s['nomor_surat']) ?></div>

<?php if ($menimbang): ?>
<table class="blok"><tr><td class="l" width="88">Menimbang</td><td class="t" width="16">:</td><td width="376">
    <?php
    $baris = [];
    foreach ($menimbang as $i => $m) {
        $baris[] = [count($menimbang) > 1 ? chr(97 + $i) . '.' : '', $m];
    }
    echo pdf_daftar($baris); ?>
</td></tr></table>
<?php endif; ?>

<?php if ($mengingat): ?>
<table class="blok"><tr><td class="l" width="88">Mengingat</td><td class="t" width="16">:</td><td width="376">
    <?php
    $baris = [];
    foreach ($mengingat as $i => $m) {
        $baris[] = [($i + 1) . '.', $m];
    }
    echo pdf_daftar($baris); ?>
</td></tr></table>
<?php endif; ?>

<div class="tengah"><?= e($set['kalimat_memberi_tugas']) ?></div>

<table class="blok"><tr><td class="l" width="88">Kepada</td><td class="t" width="16">:</td><td width="376">
    <table class="kepada">
    <?php
    $rows = [
        'Nama' => $p['nama'], 'NIP' => $p['nip'], 'Pangkat/Gol' => $p['pangkat_golongan'], 'Jabatan' => $p['jabatan'],
    ];
    foreach ($rows as $label => $nilai):
        if (!$banyak && ($nilai === null || $nilai === '')) { continue; } ?>
        <tr><td class="k1" width="85"><?= $label ?></td><td class="k2" width="14">:</td>
            <td width="277" style="padding-bottom:4px"><?= $banyak ? '<i>(terlampir)</i>' : e($nilai) ?></td></tr>
    <?php endforeach; ?>
    </table>
</td></tr></table>

<table class="blok"><tr><td class="l" width="88">Untuk</td><td class="t" width="16">:</td><td class="isi" width="376"><?= e($untukTeks) ?></td></tr></table>

<?= pdf_ttd($s, $tglSurat) ?>

<?php if ($banyak): ?>
<div class="lampiran">
    <table class="blok" style="width:9cm; margin-left:7.8cm">
        <tr><td style="width:2.2cm">Lampiran</td><td></td></tr>
        <tr><td>Nomor</td><td>: <?= e($s['nomor_surat']) ?></td></tr>
        <tr><td>Tanggal</td><td>: <?= e($tglSurat) ?></td></tr>
    </table>
    <table class="tbl">
        <thead><tr><th width="5%">No</th><th width="24%">Nama</th><th width="19%">NIP</th><th width="15%">Pangkat/Gol</th><th width="27%">Jabatan</th><th width="10%">Peran</th></tr></thead>
        <tbody>
        <?php foreach ($s['penerima'] as $i => $r): ?>
            <tr><td style="text-align:center"><?= $i + 1 ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['nip']) ?></td>
                <td><?= e($r['pangkat_golongan']) ?></td><td><?= e($r['jabatan']) ?></td><td><?= e($r['peran']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?= pdf_ttd($s, $tglSurat) ?>
</div>
<?php endif; ?>
