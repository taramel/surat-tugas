<p class="muted">200 aktivitas terakhir.</p>
<div class="tabel-bungkus"><table class="data kecil">
    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Keterangan</th><th>IP</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
        <tr><td class="nomor"><?= e($r['created_at']) ?></td><td><?= e($r['username']) ?></td><td><?= e($r['aksi']) ?></td>
            <td class="potong"><?= e($r['keterangan']) ?></td><td class="nomor"><?= e($r['ip']) ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div>
