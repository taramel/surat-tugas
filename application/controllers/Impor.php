<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Impor extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->wajib_admin();
        $this->load->model('Nomor_model', 'nomor');
    }

    public function index()
    {
        $this->render('impor/index', ['judul' => 'Impor register lama', 'lap' => null]);
    }

    public function proses()
    {
        $this->wajib_post();
        $salah = validasi_impor(isset($_FILES['berkas']) ? $_FILES['berkas'] : []);
        if ($salah) {
            flash_set('bad', $salah);
            redirect('impor');
        }
        $tahun = (int) $this->input->post('tahun') ?: (int) date('Y');
        $lap = $this->nomor->impor_berkas($_FILES['berkas']['tmp_name'], $_FILES['berkas']['name'], $tahun);
        $this->catat('impor_register', "tahun=$tahun, baru={$lap['baru']}, kosong={$lap['tersedia']}, dilewati={$lap['dilewati']}");
        $this->render('impor/index', ['judul' => 'Impor register lama', 'lap' => $lap]);
    }
}
