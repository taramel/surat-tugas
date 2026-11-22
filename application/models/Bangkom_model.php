<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Catatan pengembangan kompetensi (Bangkom) pegawai dan rekap jam pelajaran (JP). */
class Bangkom_model extends CI_Model
{
    private function saring(array $f)
    {
        $this->db->from('bangkom b')
            ->where('b.mulai <=', $f['tahun'] . '-12-31')->where('b.selesai >=', $f['tahun'] . '-01-01');
        if (!empty($f['q'])) {
            $this->db->group_start()->like('b.nama_pegawai', $f['q'])->or_like('b.kegiatan', $f['q'])->group_end();
        }
        if (!empty($f['kategori'])) {
            $this->db->where('b.kategori', $f['kategori']);
        }
    }

    public function hitung(array $f)
    {
        $this->saring($f);
        return (int) $this->db->count_all_results();
    }

    public function daftar(array $f, $limit, $offset)
    {
        $this->saring($f);
        return $this->db->select('b.*')->order_by('b.mulai', 'DESC')->order_by('b.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function kategori_tahun($tahun)
    {
        $r = $this->db->select('kategori')->distinct()->where('mulai <=', $tahun . '-12-31')->where('selesai >=', $tahun . '-01-01')
            ->where('kategori IS NOT NULL', null, false)->where('kategori !=', '')->order_by('kategori')->get('bangkom')->result_array();
        return array_column($r, 'kategori');
    }

    public function ambil($id)
    {
        return $this->db->get_where('bangkom', ['id' => (int) $id])->row_array();
    }

    public function simpan($id, array $d)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('bangkom', $d);
            return (int) $id;
        }
        $this->db->insert('bangkom', $d);
        return $this->db->insert_id();
    }

    public function hapus($id)
    {
        return $this->db->delete('bangkom', ['id' => (int) $id]);
    }

    /** Sudah ada catatan yang sama (pegawai, nama kegiatan, tanggal mulai)? Dipakai agar impor ulang tidak menggandakan. */
    public function ada($pegawaiId, $kegiatan, $mulai)
    {
        return $this->db->where('pegawai_id', (int) $pegawaiId)->where('mulai', $mulai)
            ->where('LOWER(kegiatan) =', mb_strtolower($kegiatan))->count_all_results('bangkom') > 0;
    }

    /** Rekap JP per pegawai aktif pada satu tahun. */
    public function rekap($tahun)
    {
        $sql = 'SELECT p.id, p.nama, p.jabatan, p.unit_kerja,
                COALESCE(SUM(b.jp), 0) AS jp, COUNT(b.id) AS kegiatan
                FROM pegawai p
                LEFT JOIN bangkom b ON b.pegawai_id = p.id AND b.mulai <= ? AND b.selesai >= ?
                WHERE p.aktif = 1 GROUP BY p.id, p.nama, p.jabatan, p.unit_kerja ORDER BY p.nama';
        return $this->db->query($sql, [(int) $tahun . '-12-31', (int) $tahun . '-01-01'])->result_array();
    }

    /** Riwayat Bangkom satu pegawai pada satu tahun. */
    public function riwayat($pegawaiId, $tahun)
    {
        return $this->db->where('pegawai_id', (int) $pegawaiId)->where('mulai <=', $tahun . '-12-31')->where('selesai >=', $tahun . '-01-01')
            ->order_by('mulai', 'DESC')->get('bangkom')->result_array();
    }
}
