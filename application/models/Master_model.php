<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Master_model extends CI_Model
{
    public function penandatangan()
    {
        return $this->db->order_by('aktif_dari', 'DESC')->get('penandatangan')->result_array();
    }

    /** Penandatangan yang berlaku saat ini. */
    public function penandatangan_aktif()
    {
        $hari = date('Y-m-d');
        return $this->db->where('aktif_dari <=', $hari)
            ->group_start()->where('aktif_sampai IS NULL', null, false)->or_where('aktif_sampai >=', $hari)->group_end()
            ->order_by('aktif_dari', 'DESC')->get('penandatangan')->result_array();
    }

    /** Penandatangan yang berlaku pada tanggal tertentu (yang mulai berlaku paling akhir bila lebih dari satu). */
    public function penandatangan_pada($tanggal)
    {
        return $this->db->where('aktif_dari <=', $tanggal)
            ->group_start()->where('aktif_sampai IS NULL', null, false)->or_where('aktif_sampai >=', $tanggal)->group_end()
            ->order_by('aktif_dari', 'DESC')->limit(1)->get('penandatangan')->row_array();
    }

    public function simpan_penandatangan($id, array $data)
    {
        if ($id) {
            return $this->db->where('id', $id)->update('penandatangan', $data);
        }
        return $this->db->insert('penandatangan', $data);
    }

    // ---- Peran (roles) --------------------------------------------------

    public function peran()
    {
        return $this->db->order_by('nama')->get('peran')->result_array();
    }

    public function simpan_peran($id, array $data)
    {
        if ($id) {
            return $this->db->where('id', $id)->update('peran', $data);
        }
        $this->db->insert('peran', $data);
        return $this->db->insert_id();
    }

    // ---- Dasar hukum ----------------------------------------------------

    /** Teks mengingat (dasar hukum) yang berlaku pada tanggal surat tertentu. */
    public function dasar_hukum_untuk($tanggal)
    {
        $r = $this->db->where('berlaku_mulai <=', $tanggal)
            ->order_by('berlaku_mulai', 'DESC')->limit(1)->get('dasar_hukum')->row();
        return $r ? $r->mengingat : '';
    }

    public function dasar_hukum_semua()
    {
        return $this->db->order_by('berlaku_mulai', 'DESC')->get('dasar_hukum')->result_array();
    }

    public function simpan_dasar_hukum($id, array $data)
    {
        if ($id) {
            return $this->db->where('id', $id)->update('dasar_hukum', $data);
        }
        $this->db->insert('dasar_hukum', $data);
        return $this->db->insert_id();
    }
}
