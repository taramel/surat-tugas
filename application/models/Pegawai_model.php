<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Pegawai_model extends CI_Model
{
    private function filter($q, $aktif)
    {
        if ($aktif !== null) {
            $this->db->where('aktif', $aktif);
        }
        if ($q !== '') {
            $this->db->group_start()->like('nama', $q)->or_like('nip', $q)
                ->or_like('jabatan', $q)->or_like('unit_kerja', $q)->group_end();
        }
    }

    public function daftar($q = '', $aktif = 1, $limit = 25, $offset = 0)
    {
        $this->filter($q, $aktif);
        return $this->db->order_by('nama')->limit($limit, $offset)->get('pegawai')->result_array();
    }

    public function hitung($q = '', $aktif = 1)
    {
        $this->filter($q, $aktif);
        return $this->db->count_all_results('pegawai');
    }

    public function cari($q)
    {
        $this->filter($q, 1);
        return $this->db->select('id, nama, nip, pangkat_golongan, jabatan, unit_kerja')
            ->order_by('nama')->limit(15)->get('pegawai')->result_array();
    }

    public function ambil($id)
    {
        return $this->db->get_where('pegawai', ['id' => (int) $id])->row_array();
    }

    public function ambil_banyak(array $ids)
    {
        if (!$ids) {
            return [];
        }
        $rows = $this->db->where_in('id', $ids)->get('pegawai')->result_array();
        $peta = [];
        foreach ($rows as $r) {
            $peta[$r['id']] = $r;
        }
        return $peta;
    }

    public function nip_ada($nip, $kecuali = 0)
    {
        if ($nip === '') {
            return false;
        }
        return $this->db->where('nip', $nip)->where('id !=', (int) $kecuali)->count_all_results('pegawai') > 0;
    }

    public function simpan($id, array $data)
    {
        if ($data['nip'] === '') {
            $data['nip'] = null;
        }
        if ($id) {
            $this->db->where('id', $id)->update('pegawai', $data);
            return (int) $id;
        }
        $this->db->insert('pegawai', $data);
        return $this->db->insert_id();
    }

    /** Impor massal: pembaruan berdasarkan NIP bila sudah ada. Mengembalikan [baru, diperbarui]. */
    public function impor(array $baris)
    {
        $baru = $ubah = 0;
        foreach ($baris as $d) {
            $ada = $this->db->get_where('pegawai', ['nip' => $d['nip']])->row();
            if ($ada) {
                $this->db->where('id', $ada->id)->update('pegawai', $d);
                $ubah++;
            } else {
                $this->db->insert('pegawai', $d);
                $baru++;
            }
        }
        return [$baru, $ubah];
    }

    /**
     * Menghapus pegawai yang NIP-nya tidak ada dalam daftar. Pegawai yang sudah tercatat pada surat tugas
     * tidak dihapus, hanya dinonaktifkan, supaya riwayat surat tetap utuh.
     * Mengembalikan [jumlah dihapus, jumlah dinonaktifkan].
     */
    public function bersihkan_selain(array $nips)
    {
        if (!$nips) {
            return [0, 0];
        }
        $tanda = implode(',', array_fill(0, count($nips), '?'));
        $ids = array_column($this->db->query("SELECT id FROM pegawai WHERE nip IS NULL OR nip NOT IN ($tanda)", array_values($nips))->result_array(), 'id');
        $hapus = $nonaktif = 0;
        foreach ($ids as $id) {
            $dipakai = (int) $this->db->where('pegawai_id', $id)->count_all_results('surat_tugas_pegawai');
            if ($dipakai > 0) {
                $this->db->where('id', $id)->update('pegawai', ['aktif' => 0]);
                $nonaktif++;
            } else {
                $this->db->delete('pegawai', ['id' => $id]);
                $hapus++;
            }
        }
        return [$hapus, $nonaktif];
    }

    /** Tahun-tahun ketika pegawai pernah ditugaskan (untuk penyaring riwayat). */
    public function tahun_penugasan($pegawaiId)
    {
        $r = $this->db->select('s.tahun')->distinct()->from('surat_tugas_pegawai sp')
            ->join('surat_tugas s', 's.id = sp.surat_tugas_id')
            ->where('sp.pegawai_id', (int) $pegawaiId)->order_by('s.tahun', 'DESC')->get()->result_array();
        return array_column($r, 'tahun');
    }

    /** Riwayat penugasan seorang pegawai, terbaru lebih dulu. $tahun = 0 untuk semua tahun. */
    public function riwayat_penugasan($pegawaiId, $tahun = 0)
    {
        $this->db->select('s.id, s.tahun, s.nomor_surat, s.tanggal_surat, s.untuk, s.status, pr.nama AS peran', false)
            ->from('surat_tugas_pegawai sp')->join('surat_tugas s', 's.id = sp.surat_tugas_id')
            ->join('peran pr', 'pr.id = sp.peran_id', 'left')->where('sp.pegawai_id', (int) $pegawaiId);
        if ($tahun) {
            $this->db->where('s.tahun', (int) $tahun);
        }
        $rows = $this->db->order_by('s.tanggal_surat', 'DESC')->order_by('s.nomor_urut', 'DESC')->get()->result_array();
        if ($rows) {
            $tgl = [];
            foreach ($this->db->where_in('surat_tugas_id', array_column($rows, 'id'))->order_by('mulai')->get('surat_tugas_tanggal')->result_array() as $t) {
                $tgl[$t['surat_tugas_id']][] = $t;
            }
            foreach ($rows as &$r) {
                $r['tanggal'] = isset($tgl[$r['id']]) ? $tgl[$r['id']] : [];
                $r['rentang'] = rentang_tanggal($r['tanggal']);
            }
            unset($r);
        }
        return $rows;
    }

    /** Semua pegawai aktif, diurutkan menurut nama (untuk pilihan pada formulir). */
    public function semua_aktif()
    {
        return $this->db->select('id, nip, nama, jabatan, unit_kerja')->where('aktif', 1)->order_by('nama')->get('pegawai')->result_array();
    }
}
