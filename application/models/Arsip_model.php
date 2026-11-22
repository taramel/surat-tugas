<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Berkas surat tugas yang disimpan (misalnya hasil pindai surat yang sudah ditandatangani), terikat pada nomor di register. */
class Arsip_model extends CI_Model
{
    public static function folder()
    {
        return APPPATH . 'storage/arsip/';
    }

    private function siap()
    {
        return $this->db->table_exists('arsip_berkas');
    }

    public function daftar($nomorId)
    {
        if (!$this->siap() || !$nomorId) {
            return [];
        }
        return $this->db->select('a.*, u.nama AS pengunggah')->from('arsip_berkas a')
            ->join('users u', 'u.id = a.diunggah_oleh', 'left')
            ->where('a.nomor_surat_id', (int) $nomorId)->order_by('a.created_at', 'DESC')->order_by('a.id', 'DESC')
            ->get()->result_array();
    }

    public function jumlah($nomorId)
    {
        return $this->siap() ? (int) $this->db->where('nomor_surat_id', (int) $nomorId)->count_all_results('arsip_berkas') : 0;
    }

    public function ambil($id)
    {
        return $this->siap() ? $this->db->get_where('arsip_berkas', ['id' => (int) $id])->row_array() : null;
    }

    public function tambah(array $d)
    {
        $this->db->insert('arsip_berkas', $d);
        return $this->db->insert_id();
    }

    public function hapus($id)
    {
        return $this->db->delete('arsip_berkas', ['id' => (int) $id]);
    }

    /** Baris register (nomor_surat) yang bersesuaian dengan sebuah surat tugas. */
    public function nomor_id_surat($suratId)
    {
        $r = $this->db->select('id')->where('surat_tugas_id', (int) $suratId)->limit(1)->get('nomor_surat')->row_array();
        if ($r) {
            return (int) $r['id'];
        }
        $s = $this->db->select('tahun, nomor_urut')->get_where('surat_tugas', ['id' => (int) $suratId])->row_array();
        if (!$s) {
            return 0;
        }
        $r = $this->db->select('id')->where(['tahun' => $s['tahun'], 'nomor_urut' => $s['nomor_urut']])->limit(1)->get('nomor_surat')->row_array();
        return $r ? (int) $r['id'] : 0;
    }
}
