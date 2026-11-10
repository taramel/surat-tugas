<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Template_model extends CI_Model
{
    public static function folder()
    {
        return APPPATH . 'storage/template/';
    }

    public function semua()
    {
        return $this->db->order_by('nama')->order_by('berlaku_mulai', 'DESC')->get('template')->result_array();
    }

    /** Nama deret template yang masih aktif. */
    public function nama_aktif()
    {
        $rows = $this->db->distinct()->select('nama')->where('aktif', 1)->order_by('nama')->get('template')->result_array();
        return array_column($rows, 'nama');
    }

    /** Versi template yang berlaku pada tanggal tertentu; bila belum ada, versi paling awal. */
    public function untuk($nama, $tanggal)
    {
        $r = $this->db->where(['nama' => $nama, 'aktif' => 1])->where('berlaku_mulai <=', $tanggal)
            ->order_by('berlaku_mulai', 'DESC')->limit(1)->get('template')->row_array();
        if (!$r) {
            $r = $this->db->where(['nama' => $nama, 'aktif' => 1])->order_by('berlaku_mulai', 'ASC')->limit(1)->get('template')->row_array();
        }
        return $r;
    }

    public function ambil($id)
    {
        return $this->db->get_where('template', ['id' => (int) $id])->row_array();
    }

    public function tambah(array $d)
    {
        $this->db->insert('template', $d);
        return $this->db->insert_id();
    }

    public function set_aktif($id, $nilai)
    {
        return $this->db->where('id', (int) $id)->update('template', ['aktif' => $nilai ? 1 : 0]);
    }

    /** Memeriksa berkas template. Template bawaan yang hilang dari disk dipulihkan otomatis dari folder template_awal. */
    public function berkas_ada(array $t)
    {
        $tujuan = self::folder() . $t['berkas'];
        if (is_file($tujuan)) {
            return true;
        }
        $this->load->library('skema');
        $sumber = $this->skema->berkas_awal($t['nama'], $t['berlaku_mulai']);
        if (!$sumber || !is_file($sumber)) {
            return false;
        }
        if (!is_dir(self::folder())) {
            mkdir(self::folder(), 0755, true);
        }
        return @copy($sumber, $tujuan);
    }
}
