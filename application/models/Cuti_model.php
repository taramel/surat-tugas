<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Catatan cuti dan kehadiran khusus pegawai, jenis cuti, serta saldo cuti tahunan. */
class Cuti_model extends CI_Model
{
    // ---- Jenis ------------------------------------------------------------
    public function jenis($aktifSaja = false)
    {
        if ($aktifSaja) {
            $this->db->where('aktif', 1);
        }
        return $this->db->order_by('kategori')->order_by('kode')->get('jenis_cuti')->result_array();
    }

    public function jenis_ambil($id)
    {
        return $this->db->get_where('jenis_cuti', ['id' => (int) $id])->row_array();
    }

    public function jenis_simpan($id, array $d)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('jenis_cuti', $d);
            return (int) $id;
        }
        $this->db->insert('jenis_cuti', $d);
        return $this->db->insert_id();
    }

    public function jenis_menurut_kode()
    {
        $peta = [];
        foreach ($this->db->get('jenis_cuti')->result_array() as $j) {
            $peta[strtoupper($j['kode'])] = $j;
        }
        return $peta;
    }

    // ---- Catatan cuti -------------------------------------------------------
    private function saring(array $f)
    {
        $this->db->from('cuti c')->join('jenis_cuti j', 'j.id = c.jenis_id');
        $awal = $f['tahun'] . '-01-01';
        $akhir = $f['tahun'] . '-12-31';
        if (!empty($f['bulan'])) {
            $awal = sprintf('%04d-%02d-01', $f['tahun'], $f['bulan']);
            $akhir = date('Y-m-t', strtotime($awal));
        }
        $this->db->where('c.mulai <=', $akhir)->where('c.selesai >=', $awal);
        if (!empty($f['jenis'])) {
            $this->db->where('c.jenis_id', (int) $f['jenis']);
        }
        if (!empty($f['q'])) {
            $this->db->like('c.nama_pegawai', $f['q']);
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
        return $this->db->select('c.*, j.kode, j.nama AS jenis_nama, j.kategori')
            ->order_by('c.mulai', 'DESC')->order_by('c.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function ambil($id)
    {
        return $this->db->select('c.*, j.setengah_hari')->from('cuti c')->join('jenis_cuti j', 'j.id = c.jenis_id')
            ->where('c.id', (int) $id)->get()->row_array();
    }

    public function simpan($id, array $d)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('cuti', $d);
            return (int) $id;
        }
        $this->db->insert('cuti', $d);
        return $this->db->insert_id();
    }

    public function hapus($id)
    {
        return $this->db->delete('cuti', ['id' => (int) $id]);
    }

    /** Apakah pegawai sudah punya catatan pada rentang tanggal tersebut (selain catatan $kecuali)? */
    public function tumpang_tindih($pegawaiId, $mulai, $selesai, $kecuali = 0)
    {
        $this->db->where('pegawai_id', (int) $pegawaiId)->where('mulai <=', $selesai)->where('selesai >=', $mulai);
        if ($kecuali) {
            $this->db->where('id !=', (int) $kecuali);
        }
        return $this->db->count_all_results('cuti') > 0;
    }

    /** Hapus catatan hasil impor pada satu bulan (agar impor ulang tidak menggandakan data). */
    public function hapus_impor_bulan($tahun, $bulan)
    {
        $awal = sprintf('%04d-%02d-01', $tahun, $bulan);
        $akhir = date('Y-m-t', strtotime($awal));
        $this->db->where('sumber', 'impor')->where('mulai <=', $akhir)->where('selesai >=', $awal)->delete('cuti');
        return $this->db->affected_rows();
    }

    // ---- Kalender ---------------------------------------------------------
    /** Mengembalikan [daftar pegawai, peta[pegawai_id][tanggal] = ['kode','kategori']]. */
    public function kalender($tahun, $bulan, $hanyaAda = true, $sertakanSurat = true)
    {
        $awal = sprintf('%04d-%02d-01', $tahun, $bulan);
        $akhir = date('Y-m-t', strtotime($awal));
        $rows = $this->db->select('c.pegawai_id, c.mulai, c.selesai, j.kode, j.kategori')->from('cuti c')
            ->join('jenis_cuti j', 'j.id = c.jenis_id')
            ->where('c.pegawai_id IS NOT NULL', null, false)->where('c.mulai <=', $akhir)->where('c.selesai >=', $awal)
            ->get()->result_array();
        $peta = [];
        foreach ($rows as $r) {
            $dari = max($r['mulai'], $awal);
            $sampai = min($r['selesai'], $akhir);
            for ($t = strtotime($dari), $z = strtotime($sampai); $t <= $z; $t += 86400) {
                $peta[$r['pegawai_id']][(int) date('j', $t)] = ['kode' => $r['kode'], 'kategori' => $r['kategori']];
            }
        }
        if ($sertakanSurat) {
            // Penugasan dari surat tugas aktif ditampilkan otomatis (kode ST); catatan cuti didahulukan bila bentrok.
            $sql = "SELECT sp.pegawai_id, t.mulai, t.selesai FROM surat_tugas_tanggal t
                    JOIN surat_tugas s ON s.id = t.surat_tugas_id AND s.status = 'aktif'
                    JOIN surat_tugas_pegawai sp ON sp.surat_tugas_id = s.id
                    WHERE sp.pegawai_id IS NOT NULL AND t.mulai <= ? AND t.selesai >= ?";
            foreach ($this->db->query($sql, [$akhir, $awal])->result_array() as $r) {
                $dari = max($r['mulai'], $awal);
                $sampai = min($r['selesai'], $akhir);
                for ($t = strtotime($dari), $z = strtotime($sampai); $t <= $z; $t += 86400) {
                    $hari = (int) date('j', $t);
                    if (!isset($peta[$r['pegawai_id']][$hari])) {
                        $peta[$r['pegawai_id']][$hari] = ['kode' => 'ST', 'kategori' => 'surat'];
                    }
                }
            }
        }
        $pegawai = $this->db->select('id, nama, unit_kerja')->where('aktif', 1)->order_by('nama')->get('pegawai')->result_array();
        if ($hanyaAda) {
            $pegawai = array_values(array_filter($pegawai, function ($p) use ($peta) {
                return isset($peta[$p['id']]);
            }));
        }
        return [$pegawai, $peta];
    }

    // ---- Saldo ------------------------------------------------------------
    public function saldo_tahun($tahun)
    {
        $sql = 'SELECT p.id, p.nama, p.jabatan, p.unit_kerja,
                COALESCE(s.jatah, 12) AS jatah, COALESCE(s.sisa_n1, 0) AS sisa_n1, COALESCE(s.sisa_n2, 0) AS sisa_n2,
                (SELECT COALESCE(SUM(c.jumlah_hari), 0) FROM cuti c JOIN jenis_cuti j ON j.id = c.jenis_id
                    WHERE c.pegawai_id = p.id AND j.potong_saldo = 1 AND YEAR(c.mulai) = ?) AS terpakai
                FROM pegawai p LEFT JOIN saldo_cuti s ON s.pegawai_id = p.id AND s.tahun = ?
                WHERE p.aktif = 1 ORDER BY p.nama';
        $rows = $this->db->query($sql, [(int) $tahun, (int) $tahun])->result_array();
        foreach ($rows as &$r) {
            $r['sisa'] = (float) $r['jatah'] + (float) $r['sisa_n1'] + (float) $r['sisa_n2'] - (float) $r['terpakai'];
        }
        unset($r);
        return $rows;
    }

    public function saldo_ambil($pegawaiId, $tahun)
    {
        $r = $this->db->get_where('saldo_cuti', ['pegawai_id' => (int) $pegawaiId, 'tahun' => (int) $tahun])->row_array();
        return $r ?: ['pegawai_id' => (int) $pegawaiId, 'tahun' => (int) $tahun, 'jatah' => 12, 'sisa_n1' => 0, 'sisa_n2' => 0];
    }

    public function saldo_simpan($pegawaiId, $tahun, $jatah, $n1, $n2)
    {
        $this->db->query('INSERT INTO saldo_cuti (pegawai_id, tahun, jatah, sisa_n1, sisa_n2) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE jatah = VALUES(jatah), sisa_n1 = VALUES(sisa_n1), sisa_n2 = VALUES(sisa_n2)',
            [(int) $pegawaiId, (int) $tahun, $jatah, $n1, $n2]);
    }

    public function terpakai($pegawaiId, $tahun)
    {
        $r = $this->db->query('SELECT COALESCE(SUM(c.jumlah_hari), 0) AS n FROM cuti c JOIN jenis_cuti j ON j.id = c.jenis_id
            WHERE c.pegawai_id = ? AND j.potong_saldo = 1 AND YEAR(c.mulai) = ?', [(int) $pegawaiId, (int) $tahun])->row_array();
        return (float) $r['n'];
    }

    // ---- Ringkasan untuk dashboard -------------------------------------------
    public function hari_ini()
    {
        $h = date('Y-m-d');
        return $this->db->select('c.pegawai_id, c.nama_pegawai, c.selesai, j.kode, j.nama AS jenis_nama, j.kategori')
            ->from('cuti c')->join('jenis_cuti j', 'j.id = c.jenis_id')
            ->where('c.mulai <=', $h)->where('c.selesai >=', $h)->order_by('c.nama_pegawai')->get()->result_array();
    }

    public function penugasan_hari_ini()
    {
        $h = date('Y-m-d');
        $sql = "SELECT DISTINCT p.id, p.nama, s.id AS surat_id, s.nomor_surat
                FROM surat_tugas_tanggal t
                JOIN surat_tugas s ON s.id = t.surat_tugas_id AND s.status = 'aktif'
                JOIN surat_tugas_pegawai sp ON sp.surat_tugas_id = s.id
                JOIN pegawai p ON p.id = sp.pegawai_id
                WHERE t.mulai <= ? AND t.selesai >= ? ORDER BY p.nama";
        return $this->db->query($sql, [$h, $h])->result_array();
    }

    /** Pegawai dengan sisa cuti tahunan paling sedikit (hanya yang memang punya jatah/sisa). */
    public function saldo_menipis($tahun, $batas, $maks = 8)
    {
        $rows = array_filter($this->saldo_tahun($tahun), function ($r) use ($batas) {
            return ((float) $r['jatah'] + (float) $r['sisa_n1'] + (float) $r['sisa_n2']) > 0 && $r['sisa'] <= $batas;
        });
        usort($rows, function ($a, $b) {
            return $a['sisa'] <=> $b['sisa'];
        });
        return array_slice($rows, 0, $maks);
    }
}
