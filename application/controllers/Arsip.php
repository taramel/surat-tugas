<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Penyimpanan berkas surat tugas (hasil pindai/PDF/Word yang sudah ditandatangani). */
class Arsip extends MY_Controller
{
    const MAKS_BYTE = 10485760; // 10 MB

    private $tipe = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Arsip_model', 'arsip');
        $this->load->model('Nomor_model', 'nomor');
    }

    private function kembali_ke()
    {
        $k = (string) $this->input->post('kembali');
        return preg_match('#^(surat/detail|register/ubah|ambil/hasil)/\d+$#', $k) ? $k : 'register';
    }

    public function unggah($nomorId = 0)
    {
        $this->wajib_post();
        $b = $this->nomor->baris($nomorId);
        if (!$b || $b['status'] === 'tersedia') {
            show_404();
        }
        $kembali = $this->kembali_ke() . '#arsip';
        $f = isset($_FILES['berkas']) ? $_FILES['berkas'] : [];

        $salah = null;
        if (empty($f['tmp_name']) || $f['error'] !== UPLOAD_ERR_OK) {
            $salah = ($f && $f['error'] === UPLOAD_ERR_INI_SIZE) ? 'Ukuran berkas melebihi batas server.' : 'Pilih berkas terlebih dahulu.';
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!isset($this->tipe[$ext])) {
                $salah = 'Format berkas harus PDF, Word (.docx), JPG, atau PNG.';
            } elseif ($f['size'] > self::MAKS_BYTE) {
                $salah = 'Ukuran berkas maksimal 10 MB.';
            } elseif (!$this->isi_sesuai($f['tmp_name'], $ext)) {
                $salah = 'Isi berkas tidak sesuai dengan formatnya.';
            }
        }
        if ($salah) {
            flash_set('bad', $salah);
            redirect($kembali);
        }

        if (!is_dir(Arsip_model::folder())) {
            mkdir(Arsip_model::folder(), 0755, true);
        }
        $nama = bin2hex(random_bytes(14)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], Arsip_model::folder() . $nama)) {
            flash_set('bad', 'Berkas tidak dapat disimpan. Periksa izin tulis folder application/storage/arsip.');
            redirect($kembali);
        }
        $asli = mb_substr(preg_replace('/[\x00-\x1F\x7F]/', '', basename($f['name'])), 0, 200);
        $ket = trim((string) $this->input->post('keterangan'));
        $this->arsip->tambah([
            'nomor_surat_id' => (int) $b['id'], 'nama_asli' => $asli, 'berkas' => $nama, 'ukuran' => (int) $f['size'],
            'keterangan' => $ket !== '' ? mb_substr($ket, 0, 200) : null, 'diunggah_oleh' => $this->user['id'],
        ]);
        $this->catat('unggah_arsip', $b['nomor_surat'] . ' - ' . $asli);
        flash_set('ok', 'Berkas disimpan di arsip surat ' . $b['nomor_surat'] . '.');
        redirect($kembali);
    }

    /** Unduh berkas; tambahkan ?lihat=1 untuk membuka PDF/gambar langsung di peramban. */
    public function unduh($id = 0)
    {
        $a = $this->arsip->ambil($id);
        $path = $a ? Arsip_model::folder() . $a['berkas'] : '';
        if (!$a || !is_file($path)) {
            show_404();
        }
        $ext = strtolower(pathinfo($a['berkas'], PATHINFO_EXTENSION));
        $inline = $this->input->get('lihat') === '1' && in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true);
        $aman = preg_replace('/[^A-Za-z0-9 ._()-]+/', '_', $a['nama_asli']);
        header('Content-Type: ' . (isset($this->tipe[$ext]) ? $this->tipe[$ext] : 'application/octet-stream'));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $aman . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function hapus($id = 0)
    {
        $this->wajib_post();
        $a = $this->arsip->ambil($id);
        if (!$a) {
            show_404();
        }
        if ($this->user['role'] !== 'admin' && (int) $a['diunggah_oleh'] !== (int) $this->user['id']) {
            show_error('Hanya admin atau pengunggah yang dapat menghapus berkas ini.', 403, 'Akses ditolak');
        }
        @unlink(Arsip_model::folder() . $a['berkas']);
        $this->arsip->hapus($a['id']);
        $this->catat('hapus_arsip', $a['nama_asli']);
        flash_set('ok', 'Berkas dihapus dari arsip.');
        redirect($this->kembali_ke() . '#arsip');
    }

    private function isi_sesuai($path, $ext)
    {
        $h = (string) file_get_contents($path, false, null, 0, 8);
        switch ($ext) {
            case 'pdf': return strncmp($h, '%PDF', 4) === 0;
            case 'png': return strncmp($h, "\x89PNG", 4) === 0;
            case 'jpg':
            case 'jpeg': return strncmp($h, "\xFF\xD8\xFF", 3) === 0;
            case 'docx':
                $this->load->library('docx_isi');
                return $this->docx_isi->periksa($path) === null;
        }
        return false;
    }
}
