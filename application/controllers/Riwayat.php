<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Riwayat extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->wajib_admin();
        $this->load->model('Riwayat_model', 'riwayat');
    }

    private function folder()
    {
        $f = APPPATH . 'cache/impor/';
        if (!is_dir($f)) {
            mkdir($f, 0755, true);
        }
        foreach (glob($f . '*') as $berkas) {
            if (is_file($berkas) && basename($berkas) !== '.gitkeep' && filemtime($berkas) < time() - 7200) {
                @unlink($berkas);
            }
        }
        return $f;
    }

    public function index()
    {
        $this->render('riwayat/impor', ['judul' => 'Impor riwayat surat']);
    }

    public function pratinjau()
    {
        $this->wajib_post();
        $salah = validasi_impor(isset($_FILES['berkas']) ? $_FILES['berkas'] : []);
        if ($salah) {
            flash_set('bad', $salah);
            redirect('riwayat');
        }
        $ekstensi = strtolower(pathinfo($_FILES['berkas']['name'], PATHINFO_EXTENSION));
        $ekstensi = $ekstensi === 'xlsx' ? 'xlsx' : 'csv';
        $token = bin2hex(random_bytes(12));
        $tujuan = $this->folder() . $token . '.' . $ekstensi;
        if (!move_uploaded_file($_FILES['berkas']['tmp_name'], $tujuan)) {
            flash_set('bad', 'Berkas tidak dapat disimpan sementara. Periksa izin tulis folder application/cache/impor.');
            redirect('riwayat');
        }
        try {
            $hasil = $this->riwayat->analisis($this->riwayat->baca_berkas($tujuan, $ekstensi));
        } catch (Throwable $e) {
            @unlink($tujuan);
            flash_set('bad', $e->getMessage());
            redirect('riwayat');
        }
        if ($hasil['galat']) {
            @unlink($tujuan);
            flash_set('bad', $hasil['galat']);
            redirect('riwayat');
        }
        $this->render('riwayat/pratinjau', ['judul' => 'Pratinjau impor riwayat', 'h' => $hasil, 'token' => $token,
            'nama_berkas' => $_FILES['berkas']['name']]);
    }

    public function terapkan()
    {
        $this->wajib_post();
        $token = (string) $this->input->post('token');
        $berkas = preg_match('/^[a-f0-9]{24}$/', $token) ? glob($this->folder() . $token . '.*') : [];
        if (!$berkas) {
            flash_set('bad', 'Pratinjau sudah kedaluwarsa. Unggah ulang berkasnya.');
            redirect('riwayat');
        }
        $ekstensi = strtolower(pathinfo($berkas[0], PATHINFO_EXTENSION));
        try {
            $hasil = $this->riwayat->analisis($this->riwayat->baca_berkas($berkas[0], $ekstensi));
        } catch (Throwable $e) {
            flash_set('bad', $e->getMessage());
            redirect('riwayat');
        }
        if ($hasil['galat'] || !$hasil['surat']) {
            @unlink($berkas[0]);
            flash_set('warn', $hasil['galat'] ?: 'Tidak ada surat baru yang dapat diimpor.');
            redirect('riwayat');
        }
        list($ok, $ringkas) = $this->riwayat->terapkan($hasil['surat'], $this->user['id']);
        @unlink($berkas[0]);
        if (!$ok) {
            flash_set('bad', $ringkas);
            redirect('riwayat');
        }
        $this->catat('impor_riwayat', json_encode($ringkas));
        $this->render('riwayat/hasil', ['judul' => 'Impor riwayat selesai', 'r' => $ringkas]);
    }
}
