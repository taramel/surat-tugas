<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Surat_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Pengaturan_model', 'pengaturan');
    }

    // ------------------------------------------------------------------
    // Daftar dan pencarian
    // ------------------------------------------------------------------

    private function filter(array $f)
    {
        if (!empty($f['tahun'])) {
            $this->db->where('s.tahun', (int) $f['tahun']);
        }
        if (!empty($f['bulan'])) {
            $this->db->where('MONTH(s.tanggal_surat)', (int) $f['bulan'], false);
        }
        if (!empty($f['status'])) {
            $this->db->where('s.status', $f['status']);
        }
        if (!empty($f['flag']) && $f['flag'] === 'terlambat') {
            $this->db->where("s.tanggal_surat > (SELECT MIN(t.mulai) FROM surat_tugas_tanggal t WHERE t.surat_tugas_id = s.id)", null, false);
        }
        if (!empty($f['q'])) {
            $kata = $this->db->escape_like_str($f['q']);
            $this->db->group_start()
                ->like('s.nomor_surat', $f['q'])->or_like('s.untuk', $f['q'])
                ->or_like('s.rujukan_pengirim', $f['q'])->or_like('s.rujukan_hal', $f['q'])
                ->or_where("EXISTS (SELECT 1 FROM surat_tugas_pegawai sp WHERE sp.surat_tugas_id = s.id AND sp.nama LIKE '%{$kata}%' ESCAPE '!')", null, false)
                ->group_end();
        }
    }

    public function daftar(array $f, $limit = null, $offset = 0)
    {
        $this->filter($f);
        $this->db->select("s.*, u.nama AS pembuat,
            (SELECT GROUP_CONCAT(sp.nama ORDER BY sp.urutan SEPARATOR ', ') FROM surat_tugas_pegawai sp WHERE sp.surat_tugas_id = s.id) AS penerima,
            (SELECT COUNT(*) FROM surat_tugas_pegawai sp WHERE sp.surat_tugas_id = s.id) AS jml_penerima", false)
            ->from('surat_tugas s')->join('users u', 'u.id = s.dibuat_oleh', 'left')
            ->order_by('s.tahun', 'DESC')->order_by('s.nomor_urut', 'DESC');
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        $rows = $this->db->get()->result_array();

        if ($rows) {
            $ids = array_column($rows, 'id');
            $tgl = [];
            foreach ($this->db->where_in('surat_tugas_id', $ids)->order_by('mulai')->get('surat_tugas_tanggal')->result_array() as $t) {
                $tgl[$t['surat_tugas_id']][] = $t;
            }
            foreach ($rows as &$r) {
                $r['rentang'] = rentang_tanggal(isset($tgl[$r['id']]) ? $tgl[$r['id']] : []);
            }
        }
        return $rows;
    }

    public function hitung(array $f)
    {
        $this->filter($f);
        $this->db->from('surat_tugas s');
        return $this->db->count_all_results();
    }

    public function ambil($id)
    {
        $s = $this->db->select('s.*, u.nama AS pembuat, t.nama AS ttd_nama, t.nip AS ttd_nip, t.jabatan AS ttd_jabatan', false)
            ->from('surat_tugas s')->join('users u', 'u.id = s.dibuat_oleh', 'left')
            ->join('penandatangan t', 't.id = s.penandatangan_id', 'left')
            ->where('s.id', (int) $id)->get()->row_array();
        if (!$s) {
            return null;
        }
        $s['penerima'] = $this->db->select('sp.*, r.nama AS peran', false)->from('surat_tugas_pegawai sp')
            ->join('peran r', 'r.id = sp.peran_id', 'left')->where('sp.surat_tugas_id', (int) $id)
            ->order_by('sp.urutan')->get()->result_array();
        $s['tanggal'] = $this->db->order_by('mulai')->get_where('surat_tugas_tanggal', ['surat_tugas_id' => (int) $id])->result_array();
        $s['rentang'] = rentang_tanggal($s['tanggal']);
        $s['lama'] = lama_hari($s['tanggal']);
        // surat hasil impor yang daftar penerimanya tidak lengkap (tanpa data pegawai) tidak boleh dicetak ulang atau diubah
        $s['tidak_lengkap'] = false;
        foreach ($s['penerima'] as $p) {
            if ($p['pegawai_id'] === null) {
                $s['tidak_lengkap'] = true;
            }
        }
        return $s;
    }

    // ------------------------------------------------------------------
    // Simpan: nomor diterbitkan di dalam kunci database agar tidak ada nomor ganda
    // ------------------------------------------------------------------

    public function simpan(array $d, array $penerima, array $tanggal, $pilih = 0)
    {
        $tahun = (int) $d['tahun'];
        $kunci = 'surtu_nomor_' . $tahun;
        $ok = $this->db->query('SELECT GET_LOCK(?, 8) AS k', [$kunci])->row();
        if (!$ok || (int) $ok->k !== 1) {
            return [false, 'Nomor sedang diterbitkan oleh pengguna lain. Coba simpan lagi.'];
        }
        $hasil = $this->simpan_terkunci($d, $penerima, $tanggal, (int) $pilih);
        $this->db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
        return $hasil;
    }

    /** Satu surat per penerima dengan nomor berurutan; semuanya berhasil atau tidak satu pun. */
    public function simpan_massal(array $d, array $penerima, array $tanggal)
    {
        $tahun = (int) $d['tahun'];
        $kunci = 'surtu_nomor_' . $tahun;
        $ok = $this->db->query('SELECT GET_LOCK(?, 8) AS k', [$kunci])->row();
        if (!$ok || (int) $ok->k !== 1) {
            return [false, 'Nomor sedang diterbitkan oleh pengguna lain. Coba simpan lagi.'];
        }
        $this->db->trans_begin();
        $ids = [];
        $galat = null;
        foreach ($penerima as $p) {
            list($berhasil, $hasil) = $this->simpan_terkunci($d, [$p], $tanggal, 0);
            if (!$berhasil) {
                $galat = $hasil;
                break;
            }
            $ids[] = $hasil;
        }
        if ($galat !== null) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }
        $this->db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
        return $galat !== null ? [false, $galat] : [true, $ids];
    }

    public function ringkas(array $ids)
    {
        if (!$ids) {
            return [];
        }
        return $this->db->select("s.id, s.nomor_surat, s.status, s.tanggal_surat,
            (SELECT GROUP_CONCAT(sp.nama ORDER BY sp.urutan SEPARATOR ', ') FROM surat_tugas_pegawai sp WHERE sp.surat_tugas_id = s.id) AS penerima", false)
            ->from('surat_tugas s')->where_in('s.id', $ids)->order_by('s.nomor_urut')->get()->result_array();
    }

    private function simpan_terkunci(array $d, array $penerima, array $tanggal, $pilih)
    {
        $tahun = (int) $d['tahun'];
        $baris = null;

        if ($pilih > 0) {
            $terakhir = (int) $this->db->select_max('nomor_urut', 'm')->where('tahun', $tahun)->get('nomor_surat')->row()->m;
            if ($pilih > $terakhir + 1) {
                return [false, 'Nomor urut ' . $pilih . ' terlalu jauh dari nomor terakhir tahun ' . $tahun . ' (' . $terakhir
                    . '). Pilih nomor kosong yang tertinggal, atau kosongkan kolom untuk memakai nomor berikutnya.'];
            }
            $baris = $this->db->get_where('nomor_surat', ['tahun' => $tahun, 'nomor_urut' => $pilih])->row();
            if ($baris && $baris->status !== 'tersedia') {
                return [false, 'Nomor urut ' . $pilih . ' tahun ' . $tahun . ' sudah terpakai.'];
            }
            $urut = $pilih;
        } else {
            $m = $this->db->select_max('nomor_urut', 'm')->where('tahun', $tahun)->get('nomor_surat')->row();
            $urut = (int) $m->m + 1;
        }

        $nomor = format_nomor(
            $this->pengaturan->get('format_nomor', 'B-{urut}/{satker}/KP.{klas}/{tahun}'),
            $urut, $this->pengaturan->get('kode_satker', '61513'),
            $this->pengaturan->get('kode_klasifikasi', '650'), $tahun
        );

        $this->db->trans_start();
        $d['nomor_urut'] = $urut;
        $d['nomor_surat'] = $nomor;
        $this->db->insert('surat_tugas', $d);
        $id = $this->db->insert_id();

        if ($baris) {
            $this->db->where('id', $baris->id)->update('nomor_surat', [
                'status' => 'terpakai', 'surat_tugas_id' => $id, 'tanggal_surat' => $d['tanggal_surat'],
            ]);
        } else {
            $this->db->insert('nomor_surat', [
                'tahun' => $tahun, 'nomor_urut' => $urut, 'status' => 'terpakai',
                'surat_tugas_id' => $id, 'tanggal_surat' => $d['tanggal_surat'],
            ]);
        }
        $this->simpan_anak($id, $penerima, $tanggal);
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            return [false, 'Surat gagal disimpan. Nomor tidak terpakai; silakan coba lagi.'];
        }
        return [true, $id];
    }

    public function perbarui($id, array $d, array $penerima, array $tanggal)
    {
        $this->db->trans_start();
        $this->db->where('id', (int) $id)->update('surat_tugas', $d);
        $this->db->where('surat_tugas_id', (int) $id)->update('nomor_surat', ['tanggal_surat' => $d['tanggal_surat']]);
        $this->simpan_anak((int) $id, $penerima, $tanggal);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /** Penerima memakai salinan data pegawai saat surat dibuat; salinan lama dipertahankan saat surat diubah. */
    private function simpan_anak($id, array $penerima, array $tanggal)
    {
        $lama = [];
        foreach ($this->db->get_where('surat_tugas_pegawai', ['surat_tugas_id' => $id])->result_array() as $r) {
            $lama[$r['pegawai_id']] = $r;
        }
        $this->db->delete('surat_tugas_pegawai', ['surat_tugas_id' => $id]);
        $this->db->delete('surat_tugas_tanggal', ['surat_tugas_id' => $id]);

        $pegawai = $this->db->where_in('id', array_column($penerima, 'pegawai_id') ?: [0])->get('pegawai')->result_array();
        $peta = [];
        foreach ($pegawai as $p) {
            $peta[$p['id']] = $p;
        }

        foreach (array_values($penerima) as $i => $p) {
            $pid = (int) $p['pegawai_id'];
            $sumber = isset($lama[$pid]) ? $lama[$pid] : (isset($peta[$pid]) ? $peta[$pid] : null);
            if (!$sumber) {
                continue;
            }
            $this->db->insert('surat_tugas_pegawai', [
                'surat_tugas_id' => $id, 'pegawai_id' => $pid,
                'peran_id' => !empty($p['peran_id']) ? (int) $p['peran_id'] : null,
                'urutan' => $i + 1,
                'nama' => $sumber['nama'], 'nip' => $sumber['nip'],
                'pangkat_golongan' => $sumber['pangkat_golongan'],
                'jabatan' => $sumber['jabatan'], 'unit_kerja' => $sumber['unit_kerja'],
            ]);
        }
        foreach ($tanggal as $t) {
            $this->db->insert('surat_tugas_tanggal', [
                'surat_tugas_id' => $id, 'mulai' => $t['mulai'], 'selesai' => $t['selesai'],
            ]);
        }
    }

    public function batal($id, $alasan)
    {
        $this->db->trans_start();
        $this->db->where('id', (int) $id)->update('surat_tugas', ['status' => 'dibatalkan', 'alasan_batal' => $alasan]);
        $this->db->where('surat_tugas_id', (int) $id)->update('nomor_surat', ['status' => 'dibatalkan']);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function pulihkan($id)
    {
        $this->db->trans_start();
        $this->db->where('id', (int) $id)->update('surat_tugas', ['status' => 'aktif', 'alasan_batal' => null]);
        $this->db->where('surat_tugas_id', (int) $id)->update('nomor_surat', ['status' => 'terpakai']);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // ------------------------------------------------------------------
    // Ringkasan
    // ------------------------------------------------------------------

    public function rekap_bulanan($tahun)
    {
        $rows = $this->db->select('MONTH(tanggal_surat) AS bln, COUNT(*) AS jml', false)
            ->where(['tahun' => (int) $tahun, 'status' => 'aktif'])->group_by('bln')->get('surat_tugas')->result_array();
        $hasil = array_fill(1, 12, 0);
        foreach ($rows as $r) {
            $hasil[(int) $r['bln']] = (int) $r['jml'];
        }
        return $hasil;
    }

    public function rekap_peran($tahun)
    {
        return $this->db->select("COALESCE(r.nama, 'Tanpa peran') AS peran, COUNT(*) AS jml", false)
            ->from('surat_tugas_pegawai sp')->join('surat_tugas s', 's.id = sp.surat_tugas_id')
            ->join('peran r', 'r.id = sp.peran_id', 'left')
            ->where(['s.tahun' => (int) $tahun, 's.status' => 'aktif'])
            ->group_by('peran')->order_by('jml', 'DESC')->get()->result_array();
    }

    public function jumlah_penugasan_per_pegawai($tahun, $limit = 10)
    {
        return $this->db->select('sp.nama, COUNT(*) AS jml', false)
            ->from('surat_tugas_pegawai sp')->join('surat_tugas s', 's.id = sp.surat_tugas_id')
            ->where(['s.tahun' => (int) $tahun, 's.status' => 'aktif'])
            ->group_by('sp.pegawai_id, sp.nama')->order_by('jml', 'DESC')->limit($limit)->get()->result_array();
    }
}
