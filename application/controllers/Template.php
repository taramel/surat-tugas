<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Template extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Template_model', 'templat');
        $this->load->model('Master_model', 'master');
        $this->load->model('Pengaturan_model', 'pengaturan');
        $this->load->library('docx_isi');
    }

    public function index()
    {
        $seri = [];
        foreach ($this->templat->nama_aktif() as $nama) {
            $t = $this->templat->untuk($nama, date('Y-m-d'));
            if ($t) {
                $seri[] = $t;
            }
        }
        $this->render('template/index', ['judul' => 'Template Word', 'seri' => $seri]);
    }

    /** Unduh template dengan isian umum (nomor dan tanggal dikosongkan untuk diisi manual). */
    public function unduh($id = 0)
    {
        $t = $this->templat->ambil($id);
        if (!$t || !$this->templat->berkas_ada($t)) {
            show_404();
        }
        $ttd = $this->master->penandatangan_pada(date('Y-m-d'));
        $isi = $this->docx_isi->isi(Template_model::folder() . $t['berkas'], [
            'NOMOR' => 'B-.../' . $this->pengaturan->get('kode_satker', '61513') . '/KP.' . $this->pengaturan->get('kode_klasifikasi', '650') . '/' . date('Y'),
            'TANGGAL' => '... ........... ' . date('Y'), 'TEMPAT' => $this->pengaturan->get('tempat_default', 'Pontianak'),
            'TTD_JABATAN' => $ttd ? $ttd['jabatan'] : '', 'TTD_NAMA' => $ttd ? $ttd['nama'] : '', 'TTD_NIP' => $ttd ? (string) $ttd['nip'] : '',
            'URAIAN' => '[nama kegiatan]', 'TAHUN' => date('Y'), 'PEMBUAT' => $this->user['nama'],
        ]);
        $this->catat('unduh_template', $t['nama']);
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Template - ' . preg_replace('/[^A-Za-z0-9 ._-]+/', '', $t['nama']) . '.docx"');
        header('Content-Length: ' . strlen($isi));
        echo $isi;
        exit;
    }

    // ---- khusus admin ---------------------------------------------------

    public function kelola()
    {
        $this->wajib_admin();
        $semua = $this->templat->semua();
        $nama = array_values(array_unique(array_column($semua, 'nama')));
        $this->render('template/kelola', ['judul' => 'Kelola template', 'semua' => $semua, 'nama_ada' => $nama]);
    }

    public function simpan()
    {
        $this->wajib_admin();
        $this->wajib_post();
        $nama = trim((string) $this->input->post('nama'));
        $mulai = trim((string) $this->input->post('berlaku_mulai'));
        $berkas = isset($_FILES['berkas']) ? $_FILES['berkas'] : [];
        $d = DateTime::createFromFormat('Y-m-d', $mulai);

        $salah = null;
        if ($nama === '' || mb_strlen($nama) > 150) {
            $salah = 'Nama template wajib diisi (maksimal 150 karakter).';
        } elseif (!$d || $d->format('Y-m-d') !== $mulai) {
            $salah = 'Tanggal mulai berlaku belum diisi dengan benar.';
        } elseif (empty($berkas['tmp_name']) || $berkas['error'] !== UPLOAD_ERR_OK) {
            $salah = 'Pilih berkas Word (.docx) terlebih dahulu.';
        } elseif (strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION)) !== 'docx') {
            $salah = 'Berkas harus berformat .docx.';
        } elseif ($berkas['size'] > 10 * 1024 * 1024) {
            $salah = 'Ukuran berkas maksimal 10 MB.';
        } elseif (($p = $this->docx_isi->periksa($berkas['tmp_name'])) !== null) {
            $salah = $p;
        }
        if ($salah) {
            flash_set('bad', $salah);
            redirect('template/kelola');
        }

        $penanda = $this->docx_isi->penanda($berkas['tmp_name']);
        $tujuan = bin2hex(random_bytes(12)) . '.docx';
        if (!is_dir(Template_model::folder())) {
            mkdir(Template_model::folder(), 0755, true);
        }
        if (!move_uploaded_file($berkas['tmp_name'], Template_model::folder() . $tujuan)) {
            flash_set('bad', 'Berkas tidak dapat disimpan. Periksa izin tulis folder application/storage/template.');
            redirect('template/kelola');
        }
        $this->templat->tambah([
            'nama' => $nama, 'berlaku_mulai' => $mulai, 'berkas' => $tujuan,
            'catatan' => trim((string) $this->input->post('catatan')) ?: null, 'diunggah_oleh' => $this->user['id'],
        ]);
        $this->catat('unggah_template', $nama . ' mulai ' . $mulai);
        $pesan = 'Template "' . $nama . '" mulai ' . tgl_indo($mulai) . ' disimpan.';
        if (!in_array('NOMOR', $penanda, true)) {
            $pesan .= ' Perhatian: penanda {{NOMOR}} tidak ditemukan, sehingga nomor surat tidak akan terisi otomatis.';
        }
        flash_set(in_array('NOMOR', $penanda, true) ? 'ok' : 'warn', $pesan);
        redirect('template/kelola');
    }

    public function status($id = 0)
    {
        $this->wajib_admin();
        $this->wajib_post();
        $t = $this->templat->ambil($id);
        if (!$t) {
            show_404();
        }
        $this->templat->set_aktif($t['id'], !$t['aktif']);
        $this->catat($t['aktif'] ? 'nonaktifkan_template' : 'aktifkan_template', $t['nama'] . ' ' . $t['berlaku_mulai']);
        flash_set('ok', 'Template ' . ($t['aktif'] ? 'dinonaktifkan.' : 'diaktifkan kembali.'));
        redirect('template/kelola');
    }

    /** Unduh berkas template apa adanya (dengan penanda), untuk diedit di Word. */
    public function mentah($id = 0)
    {
        $this->wajib_admin();
        $t = $this->templat->ambil($id);
        if (!$t || !$this->templat->berkas_ada($t)) {
            show_404();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9 ._-]+/', '', $t['nama']) . ' (' . $t['berlaku_mulai'] . ').docx"');
        readfile(Template_model::folder() . $t['berkas']);
        exit;
    }
}
