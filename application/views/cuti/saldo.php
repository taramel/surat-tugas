<form class="saring" method="get" action="<?= site_url('cuti/saldo') ?>">
    <label class="field"><span>Tahun</span><input type="number" name="tahun" value="<?= (int) $tahun ?>" min="2000" max="2100"></label>
    <div class="aksi"><button class="btn" type="submit"><?= ikon('cari') ?>Terapkan</button></div>
</form>
<p class="muted">Sisa = jatah tahun ini + sisa tahun lalu + sisa dua tahun lalu &minus; cuti tahunan terpakai (jenis yang memotong saldo, misalnya CT dan CST). Jatah dan sisa awal diatur per pegawai.</p>
<div class="tabel-bungkus">
    <table class="data">
        <thead><tr><th>Pegawai</th><th class="angka-kanan">Jatah</th><th class="angka-kanan">Sisa N-1</th><th class="angka-kanan">Sisa N-2</th><th class="angka-kanan">Terpakai</th><th class="angka-kanan">Sisa</th><th class="angka-kanan">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><a href="<?= site_url('pegawai/riwayat/' . $r['id']) ?>"><?= e($r['nama']) ?></a><br><span class="muted"><?= e($r['unit_kerja']) ?></span></td>
                <td class="angka-kanan"><?= e(angka_hari($r['jatah'])) ?></td>
                <td class="angka-kanan"><?= e(angka_hari($r['sisa_n1'])) ?></td>
                <td class="angka-kanan"><?= e(angka_hari($r['sisa_n2'])) ?></td>
                <td class="angka-kanan"><?= e(angka_hari($r['terpakai'])) ?></td>
                <td class="angka-kanan"><span class="badge <?= $r['sisa'] < 0 ? 'badge-bad' : ($r['sisa'] == 0 ? 'badge-warn' : 'badge-ok') ?>"><?= e(angka_hari($r['sisa'])) ?></span></td>
                <td class="aksi-baris"><a class="btn btn-ikon" href="<?= site_url('cuti/saldo_ubah/' . $r['id'] . '?tahun=' . (int) $tahun) ?>" title="Atur saldo" aria-label="Atur saldo <?= e($r['nama']) ?>"><?= ikon('ubah') ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
