<section class="panel sempit">
    <?php if ($error): ?><div class="flash flash-bad" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('akun/password') ?>" class="form">
        <?= csrf_field() ?>
        <label class="field"><span>Kata sandi saat ini</span><input type="password" name="lama" autocomplete="current-password" required></label>
        <label class="field"><span>Kata sandi baru</span><input type="password" name="baru" minlength="8" autocomplete="new-password" required>
            <small>Minimal 8 karakter.</small></label>
        <label class="field"><span>Ulangi kata sandi baru</span><input type="password" name="ulang" autocomplete="new-password" required></label>
        <div class="aksi"><button class="btn btn-primary" type="submit">Simpan kata sandi</button></div>
    </form>
</section>
