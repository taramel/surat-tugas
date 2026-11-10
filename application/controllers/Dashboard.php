<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
    public function index()
    {
        $this->load->model('Surat_model', 'surat');
        $this->load->model('Nomor_model', 'nomor');
        $tahun = (int) date('Y');

        $this->render('dashboard/index', [
            'judul' => 'Ringkasan',
            'tahun' => $tahun,
            'total' => $this->surat->hitung(['tahun' => $tahun, 'status' => 'aktif']),
            'bulan_ini' => $this->surat->hitung(['tahun' => $tahun, 'bulan' => (int) date('n'), 'status' => 'aktif']),
            'dibatalkan' => $this->surat->hitung(['tahun' => $tahun, 'status' => 'dibatalkan']),
            'terakhir' => $this->nomor->maks($tahun),
            'bebas' => count($this->nomor->bebas($tahun)),
            'rekap' => $this->surat->rekap_bulanan($tahun),
            'terbaru' => $this->surat->daftar(['tahun' => $tahun], 8, 0),
            'tanpa_uraian' => $this->db->where('tahun', $tahun)->where('status', 'arsip')
                ->group_start()->where('uraian IS NULL', null, false)->or_where('uraian', '')->group_end()
                ->count_all_results('nomor_surat'),
            'terlambat' => $this->surat->hitung(['tahun' => $tahun, 'status' => 'aktif', 'flag' => 'terlambat']),
        ]);
    }
}
