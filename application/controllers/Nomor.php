<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Nomor extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Nomor_model', 'nomor');
        $this->load->model('Pengaturan_model', 'pengaturan');
    }

    public function index()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $register = $this->nomor->register($tahun);
        $maks = $register ? max(array_keys($register)) : 0;

        $this->render('nomor/index', [
            'judul' => 'Register nomor', 'tahun' => $tahun, 'register' => $register, 'maks' => $maks,
            'bebas' => $this->nomor->bebas($tahun),
            'tahun_opsi' => $this->nomor->tahun_tersedia(),
            'format' => $this->pengaturan->get('format_nomor'),
            'satker' => $this->pengaturan->get('kode_satker'),
            'klas' => $this->pengaturan->get('kode_klasifikasi'),
        ]);
    }

    public function tersedia()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $this->json(['tahun' => $tahun, 'nomor' => $this->nomor->bebas($tahun), 'berikutnya' => $this->nomor->maks($tahun) + 1]);
    }

    public function impor()
    {
        $this->wajib_admin();
        $this->render('nomor/impor', ['judul' => 'Impor register lama', 'lap' => null]);
    }

    public function proses_impor()
    {
        $this->wajib_admin();
        $this->wajib_post();
        $salah = validasi_impor(isset($_FILES['berkas']) ? $_FILES['berkas'] : []);
        if ($salah) {
            flash_set('bad', $salah);
            redirect('nomor/impor');
        }
        $tahun = (int) $this->input->post('tahun') ?: (int) date('Y');
        $lap = $this->nomor->impor_berkas($_FILES['berkas']['tmp_name'], $_FILES['berkas']['name'], $tahun);
        $this->catat('impor_nomor', "tahun=$tahun, baru={$lap['baru']}, tersedia={$lap['tersedia']}, dilewati={$lap['dilewati']}");
        $this->render('nomor/impor', ['judul' => 'Impor register lama', 'lap' => $lap]);
    }
}
