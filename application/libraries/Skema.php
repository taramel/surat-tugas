<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pembaruan struktur database otomatis. Setiap langkah memeriksa dahulu apakah perubahan diperlukan,
 * sehingga aman dijalankan berulang kali dan pada database dari versi aplikasi yang mana pun.
 */
class Skema
{
    const VERSI = 10;

    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    /** Template awal yang disertakan pada aplikasi: [nama, berlaku mulai, berkas, catatan]. */
    private function template_awal()
    {
        $daftar = [
            ['Surat Tugas - satu penerima', '2000-01-01', 'satu_lama.docx', 'Dasar hukum Peraturan BPS Nomor 5 Tahun 2023 (hingga 7 Juni 2026)'],
            ['Surat Tugas - satu penerima', '2026-06-08', 'satu_baru.docx', 'Dasar hukum Peraturan BPS Nomor 3 Tahun 2026 (mulai 8 Juni 2026)'],
            ['Surat Tugas - banyak penerima (dengan lampiran)', '2000-01-01', 'banyak_lama.docx', 'Dasar hukum Peraturan BPS Nomor 5 Tahun 2023 (hingga 7 Juni 2026)'],
            ['Surat Tugas - banyak penerima (dengan lampiran)', '2026-06-08', 'banyak_baru.docx', 'Dasar hukum Peraturan BPS Nomor 3 Tahun 2026 (mulai 8 Juni 2026)'],
        ];
        // Varian per jenis kegiatan: tata letak sama, kalimat "Untuk" (dan "Menimbang" pada jenis internal) disesuaikan.
        $jenis = [];
        foreach (daftar_jenis_template() as $slug => $j) {
            if ($slug !== 'umum') {
                $jenis[$slug] = $j['judul'];
            }
        }
        $bentuk = ['satu' => 'satu penerima', 'banyak' => 'banyak penerima (dengan lampiran)'];
        $versi = [
            'lama' => ['2000-01-01', 'Dasar hukum Peraturan BPS Nomor 5 Tahun 2023 (hingga 7 Juni 2026)'],
            'baru' => ['2026-06-08', 'Dasar hukum Peraturan BPS Nomor 3 Tahun 2026 (mulai 8 Juni 2026)'],
        ];
        foreach ($jenis as $slug => $judul) {
            foreach ($bentuk as $b => $namaBentuk) {
                foreach ($versi as $v => $info) {
                    $daftar[] = ['Surat Tugas ' . $judul . ' - ' . $namaBentuk, $info[0], $slug . '_' . $b . '_' . $v . '.docx', $info[1]];
                }
            }
        }
        return $daftar;
    }

    /** Menambahkan template bawaan yang belum ada di database (untuk instalasi yang sudah berjalan). */
    private function template_awal_kurang()
    {
        $kurang = [];
        foreach ($this->template_awal() as $t) {
            if ($this->hitung('SELECT COUNT(*) AS n FROM template WHERE nama = ? AND berlaku_mulai = ?', [$t[0], $t[1]]) === 0
                && is_file(APPPATH . 'storage/template_awal/' . $t[2])) {
                $kurang[] = $t;
            }
        }
        return $kurang;
    }

    /**
     * Satu kali: memasukkan daftar pegawai BPS Provinsi (storage/pegawai_bps_prov.csv, dari sheet Daftar Pegawai),
     * lalu menghapus pegawai lain. Yang sudah tercatat pada surat tugas dinonaktifkan agar riwayat tetap utuh.
     */
    private function sinkron_pegawai_awal()
    {
        $fh = fopen(APPPATH . 'storage/pegawai_bps_prov.csv', 'r');
        if (!$fh) {
            throw new RuntimeException('Berkas daftar pegawai tidak dapat dibuka.');
        }
        $nips = [];
        $n = 0;
        while (($r = fgetcsv($fh)) !== false) {
            $n++;
            if ($n === 1 || count($r) < 5) {
                continue;
            }
            $nip = preg_replace('/\D+/', '', $r[0]);
            if ($nip === '' || trim($r[1]) === '') {
                continue;
            }
            $this->jalankan('INSERT INTO pegawai (nip, nama, pangkat_golongan, jabatan, unit_kerja, aktif) VALUES (?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE nama = VALUES(nama), pangkat_golongan = VALUES(pangkat_golongan),
                jabatan = VALUES(jabatan), unit_kerja = VALUES(unit_kerja), aktif = 1',
                [$nip, trim($r[1]), trim($r[2]) !== '' ? trim($r[2]) : null, trim($r[3]) !== '' ? trim($r[3]) : '-', trim($r[4]) !== '' ? trim($r[4]) : null]);
            $nips[] = $nip;
        }
        fclose($fh);
        if (!$nips) {
            throw new RuntimeException('Daftar pegawai pada berkas kosong.');
        }
        $tanda = implode(',', array_fill(0, count($nips), '?'));
        $luar = '(nip IS NULL OR nip NOT IN (' . $tanda . '))';
        if ($this->ada_tabel('surat_tugas_pegawai')) {
            $this->jalankan('DELETE FROM pegawai WHERE ' . $luar . ' AND id NOT IN (SELECT pegawai_id FROM surat_tugas_pegawai WHERE pegawai_id IS NOT NULL)', $nips);
        } else {
            $this->jalankan('DELETE FROM pegawai WHERE ' . $luar, $nips);
        }
        $this->jalankan('UPDATE pegawai SET aktif = 0 WHERE ' . $luar, $nips);
        $this->jalankan('INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)', ['sinkron_pegawai_sheet_2026', date('Y-m-d H:i:s')]);
    }

    private function pasang_template_tambahan()
    {
        $folder = APPPATH . 'storage/template/';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        foreach ($this->template_awal_kurang() as $t) {
            $tujuan = bin2hex(random_bytes(12)) . '.docx';
            if (!copy(APPPATH . 'storage/template_awal/' . $t[2], $folder . $tujuan)) {
                throw new RuntimeException('Template tidak dapat disalin. Periksa izin tulis folder application/storage/template.');
            }
            $this->jalankan('INSERT INTO template (nama, berlaku_mulai, berkas, catatan) VALUES (?, ?, ?, ?)', [$t[0], $t[1], $tujuan, $t[3]]);
        }
    }

    /** Lokasi berkas template bawaan untuk nama dan tanggal berlaku tertentu, atau null bila bukan template bawaan. */
    public function berkas_awal($nama, $berlakuMulai)
    {
        foreach ($this->template_awal() as $t) {
            if ($t[0] === $nama && $t[1] === (string) $berlakuMulai) {
                return APPPATH . 'storage/template_awal/' . $t[2];
            }
        }
        return null;
    }

    // ---- pemeriksaan -----------------------------------------------------

    private function ada_tabel($tabel)
    {
        $q = $this->ci->db->query('SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$tabel]);
        return $q && (int) $q->row()->n > 0;
    }

    private function ada_kolom($tabel, $kolom)
    {
        $q = $this->ci->db->query('SELECT COUNT(*) AS n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$tabel, $kolom]);
        return $q && (int) $q->row()->n > 0;
    }

    private function jalankan($sql, array $param = [])
    {
        $ok = $this->ci->db->query($sql, $param);
        if ($ok === false) {
            $e = $this->ci->db->error();
            throw new RuntimeException('Perintah database gagal: ' . $e['message']);
        }
        return $ok;
    }

    private function hitung($sql, array $param = [])
    {
        $q = $this->ci->db->query($sql, $param);
        return $q ? (int) $q->row()->n : 0;
    }

    public function versi_sekarang()
    {
        if (!$this->ada_tabel('pengaturan')) {
            return 0;
        }
        $q = $this->ci->db->query("SELECT nilai FROM pengaturan WHERE kunci = 'versi_skema'");
        $r = $q ? $q->row() : null;
        return $r ? (int) $r->nilai : 1;
    }

    public function perlu_pembaruan()
    {
        if ($this->versi_sekarang() < self::VERSI) {
            return true;
        }
        if ($this->ada_tabel('template') && $this->hitung('SELECT COUNT(*) AS n FROM template') === 0) {
            $this->pasang_template_awal();
        }
        return false;
    }

    // ---- template awal ---------------------------------------------------

    private function pasang_template_awal()
    {
        $q = $this->ci->db->query("SELECT GET_LOCK('surtu_seed_template', 5) AS k");
        try {
            if ($this->hitung('SELECT COUNT(*) AS n FROM template') > 0) {
                return 0;
            }
            $folder = APPPATH . 'storage/template/';
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
            $n = 0;
            foreach ($this->template_awal() as $t) {
                $sumber = APPPATH . 'storage/template_awal/' . $t[2];
                if (!is_file($sumber)) {
                    continue;
                }
                $tujuan = bin2hex(random_bytes(12)) . '.docx';
                if (!copy($sumber, $folder . $tujuan)) {
                    throw new RuntimeException('Template awal tidak dapat disalin. Periksa izin tulis folder application/storage/template.');
                }
                $this->jalankan('INSERT INTO template (nama, berlaku_mulai, berkas, catatan) VALUES (?, ?, ?, ?)', [$t[0], $t[1], $tujuan, $t[3]]);
                $n++;
            }
            return $n;
        } finally {
            $this->ci->db->query("SELECT RELEASE_LOCK('surtu_seed_template')");
        }
    }

    // ---- langkah-langkah -------------------------------------------------

    private function kolom_baru()
    {
        return [
            'nomor_surat' => 'VARCHAR(100) NULL AFTER nomor_urut',
            'dibuat_oleh' => 'INT UNSIGNED NULL AFTER pembuat',
            'template_nama' => 'VARCHAR(150) NULL AFTER tanggal_surat',
            'penandatangan_id' => 'INT UNSIGNED NULL AFTER template_nama',
            'tautan_arsip' => 'VARCHAR(400) NULL AFTER penandatangan_id',
            'alasan_batal' => 'VARCHAR(300) NULL AFTER tautan_arsip',
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ];
    }

    private function langkah()
    {
        return [
            ['Kolom "wajib ganti kata sandi" pada pengguna',
                function () { return !$this->ada_kolom('users', 'wajib_ganti_sandi'); },
                function () { $this->jalankan('ALTER TABLE users ADD COLUMN wajib_ganti_sandi TINYINT(1) NOT NULL DEFAULT 0 AFTER aktif'); }],

            ['Tabel template Word dan pemasangan template awal',
                function () { return !$this->ada_tabel('template') || $this->hitung('SELECT COUNT(*) AS n FROM template') === 0; },
                function () {
                    $this->jalankan('CREATE TABLE IF NOT EXISTS template (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        nama VARCHAR(150) NOT NULL,
                        berlaku_mulai DATE NOT NULL,
                        berkas VARCHAR(80) NOT NULL,
                        catatan VARCHAR(300) NULL,
                        aktif TINYINT(1) NOT NULL DEFAULT 1,
                        diunggah_oleh INT UNSIGNED NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_template_nama (nama, berlaku_mulai)
                    ) ENGINE=InnoDB');
                    $this->pasang_template_awal();
                }],

            ['Template tambahan per jenis kegiatan',
                function () { return $this->ada_tabel('template') && count($this->template_awal_kurang()) > 0; },
                function () { $this->pasang_template_tambahan(); }],

            ['Tabel arsip berkas surat tugas',
                function () { return !$this->ada_tabel('arsip_berkas'); },
                function () {
                    $this->jalankan('CREATE TABLE IF NOT EXISTS arsip_berkas (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        nomor_surat_id INT UNSIGNED NOT NULL,
                        nama_asli VARCHAR(200) NOT NULL,
                        berkas VARCHAR(80) NOT NULL,
                        ukuran INT UNSIGNED NOT NULL DEFAULT 0,
                        keterangan VARCHAR(200) NULL,
                        diunggah_oleh INT UNSIGNED NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_arsip_nomor (nomor_surat_id)
                    ) ENGINE=InnoDB');
                }],

            ['Daftar pegawai BPS Provinsi dari sheet Daftar Pegawai',
                function () {
                    return $this->ada_tabel('pegawai') && $this->ada_tabel('pengaturan')
                        && is_file(APPPATH . 'storage/pegawai_bps_prov.csv')
                        && $this->hitung('SELECT COUNT(*) AS n FROM pengaturan WHERE kunci = ?', ['sinkron_pegawai_sheet_2026']) === 0;
                },
                function () { $this->sinkron_pegawai_awal(); }],

            ['Kolom baru pada register nomor',
                function () {
                    foreach (array_keys($this->kolom_baru()) as $k) {
                        if (!$this->ada_kolom('nomor_surat', $k)) {
                            return true;
                        }
                    }
                    return false;
                },
                function () {
                    foreach ($this->kolom_baru() as $k => $ddl) {
                        if (!$this->ada_kolom('nomor_surat', $k)) {
                            $this->jalankan('ALTER TABLE nomor_surat ADD COLUMN ' . $k . ' ' . $ddl);
                        }
                    }
                }],

            ['Tabel pegawai BPS',
                function () { return !$this->ada_tabel('pegawai'); },
                function () {
                    $this->jalankan('CREATE TABLE IF NOT EXISTS pegawai (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        nip VARCHAR(30) NULL UNIQUE,
                        nama VARCHAR(150) NOT NULL,
                        pangkat_golongan VARCHAR(100) NULL,
                        jabatan VARCHAR(200) NOT NULL,
                        unit_kerja VARCHAR(200) NULL,
                        aktif TINYINT(1) NOT NULL DEFAULT 1,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB');
                }],

            ['Tabel peran penugasan',
                function () { return !$this->ada_tabel('peran'); },
                function () {
                    $this->jalankan('CREATE TABLE IF NOT EXISTS peran (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        nama VARCHAR(100) NOT NULL UNIQUE
                    ) ENGINE=InnoDB');
                    $this->jalankan("INSERT IGNORE INTO peran (nama) VALUES
                        ('Peserta'), ('Pengawas'), ('Narasumber'), ('Panitia'), ('Mentor'), ('Innas'), ('Inda'), ('Admin Bangkom')");
                }],

            ['Tabel dasar hukum',
                function () { return !$this->ada_tabel('dasar_hukum'); },
                function () {
                    $this->jalankan('CREATE TABLE IF NOT EXISTS dasar_hukum (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        berlaku_mulai DATE NOT NULL,
                        keterangan VARCHAR(150) NULL,
                        mengingat TEXT NOT NULL,
                        UNIQUE KEY uq_dasar_mulai (berlaku_mulai)
                    ) ENGINE=InnoDB');
                    $this->jalankan("INSERT IGNORE INTO dasar_hukum (berlaku_mulai, keterangan, mengingat) VALUES
                        ('2000-01-01', 'Peraturan BPS Nomor 5 Tahun 2023 (hingga 7 Juni 2026)', 'Undang-Undang Nomor 16 Tahun 1997 tentang Statistik;\r\nPeraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik;\r\nPeraturan Pemerintah Nomor 45 Tahun 2013 tentang Tata Cara Pelaksanaan Anggaran Pendapatan dan Belanja Negara sebagaimana telah diubah dengan Peraturan Pemerintah 50 Tahun 2018;\r\nPeraturan Presiden Republik Indonesia Nomor 86 Tahun 2007 tentang Badan Pusat Statistik (Lembaran Negara Republik Indonesia Tahun 2007 Nomor 139), sebagaimana telah diubah dengan Peraturan Presiden Nomor 1 Tahun 2025 (Lembaran Negara Republik Indonesia Tahun 2025 Nomor 4);\r\nPeraturan BPS Nomor 5 Tahun 2023 tentang Organisasi dan Tata Kerja BPS Provinsi dan BPS Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2023 Nomor 429), sebagaimana telah diubah dengan Peraturan BPS Nomor 3 Tahun 2025 tentang Perubahan atas Peraturan BPS Nomor 5 Tahun 2023 tentang Organisasi dan Tata Kerja Badan Pusat Statistik Provinsi dan BPS Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2025 Nomor 683);'),
                        ('2026-06-08', 'Peraturan BPS Nomor 3 Tahun 2026 (mulai 8 Juni 2026)', 'Undang-Undang Nomor 16 Tahun 1997 tentang Statistik;\r\nPeraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik;\r\nPeraturan Pemerintah Nomor 45 Tahun 2013 tentang Tata Cara Pelaksanaan Anggaran Pendapatan dan Belanja Negara sebagaimana telah diubah dengan Peraturan Pemerintah 50 Tahun 2018;\r\nPeraturan Presiden Republik Indonesia Nomor 86 Tahun 2007 tentang Badan Pusat Statistik (Lembaran Negara Republik Indonesia Tahun 2007 Nomor 139), sebagaimana telah diubah dengan Peraturan Presiden Nomor 1 Tahun 2025 (Lembaran Negara Republik Indonesia Tahun 2025 Nomor 4);\r\nPeraturan Badan Pusat Statistik Nomor 3 Tahun 2026 tentang Organisasi dan Tata Kerja Badan Pusat Statistik Provinsi dan Badan Pusat Statistik Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2026 Nomor 331);')");
                }],

            ['Tabel surat tugas utama',
                function () { return !$this->ada_tabel('surat_tugas'); },
                function () {
                    $this->jalankan('CREATE TABLE IF NOT EXISTS surat_tugas (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        tahun SMALLINT UNSIGNED NOT NULL,
                        nomor_urut INT UNSIGNED NOT NULL,
                        nomor_surat VARCHAR(100) NOT NULL,
                        tanggal_surat DATE NOT NULL,
                        tempat VARCHAR(100) NOT NULL DEFAULT "Pontianak",
                        asal ENUM("internal","eksternal") NOT NULL DEFAULT "eksternal",
                        unit_pemohon VARCHAR(200) NULL,
                        rujukan_pengirim VARCHAR(200) NULL,
                        rujukan_nomor VARCHAR(200) NULL,
                        rujukan_hal VARCHAR(300) NULL,
                        menimbang_a TEXT NULL,
                        untuk TEXT NOT NULL,
                        penandatangan_id INT UNSIGNED NULL,
                        status ENUM("aktif","dibatalkan") NOT NULL DEFAULT "aktif",
                        sumber ENUM("aplikasi","impor") NOT NULL DEFAULT "aplikasi",
                        tautan_arsip VARCHAR(400) NULL,
                        alasan_batal VARCHAR(300) NULL,
                        catatan VARCHAR(300) NULL,
                        dibuat_oleh INT UNSIGNED NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uq_surat (tahun, nomor_urut),
                        INDEX idx_surat_tahun_status (tahun, status),
                        INDEX idx_surat_tanggal (tanggal_surat),
                        CONSTRAINT fk_surat_user FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL,
                        CONSTRAINT fk_surat_ttd FOREIGN KEY (penandatangan_id) REFERENCES penandatangan(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB');
                }],

            ['Tabel relasi pegawai dan tanggal surat tugas',
                function () { return !$this->ada_tabel('surat_tugas_pegawai') || !$this->ada_tabel('surat_tugas_tanggal'); },
                function () {
                    $this->jalankan('CREATE TABLE IF NOT EXISTS surat_tugas_pegawai (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        surat_tugas_id INT UNSIGNED NOT NULL,
                        pegawai_id INT UNSIGNED NULL,
                        peran_id INT UNSIGNED NULL,
                        urutan TINYINT UNSIGNED NOT NULL DEFAULT 1,
                        nama VARCHAR(150) NOT NULL,
                        nip VARCHAR(30) NULL,
                        pangkat_golongan VARCHAR(100) NULL,
                        jabatan VARCHAR(200) NULL,
                        unit_kerja VARCHAR(200) NULL,
                        INDEX idx_stp_surat (surat_tugas_id),
                        INDEX idx_stp_pegawai (pegawai_id),
                        CONSTRAINT fk_stp_surat FOREIGN KEY (surat_tugas_id) REFERENCES surat_tugas(id) ON DELETE CASCADE,
                        CONSTRAINT fk_stp_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE SET NULL,
                        CONSTRAINT fk_stp_peran FOREIGN KEY (peran_id) REFERENCES peran(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB');
                    $this->jalankan('CREATE TABLE IF NOT EXISTS surat_tugas_tanggal (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        surat_tugas_id INT UNSIGNED NOT NULL,
                        mulai DATE NOT NULL,
                        selesai DATE NOT NULL,
                        INDEX idx_stt_surat (surat_tugas_id),
                        CONSTRAINT fk_stt_surat FOREIGN KEY (surat_tugas_id) REFERENCES surat_tugas(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB');
                }],

            ['Tautan surat_tugas_id dan nomor_asli pada register nomor',
                function () {
                    return !$this->ada_kolom('nomor_surat', 'surat_tugas_id') || !$this->ada_kolom('nomor_surat', 'nomor_asli');
                },
                function () {
                    if (!$this->ada_kolom('nomor_surat', 'surat_tugas_id')) {
                        $this->jalankan('ALTER TABLE nomor_surat ADD COLUMN surat_tugas_id INT UNSIGNED NULL AFTER penandatangan_id');
                    }
                    if (!$this->ada_kolom('nomor_surat', 'nomor_asli')) {
                        $this->jalankan('ALTER TABLE nomor_surat ADD COLUMN nomor_asli VARCHAR(120) NULL AFTER alasan_batal');
                    }
                }],

            ['Pengaturan bawaan aplikasi',
                function () {
                    $kunci = ['kode_satker', 'kode_klasifikasi', 'format_nomor', 'tempat_default', 'awalan_untuk', 'kop_baris1', 'kop_baris2'];
                    foreach ($kunci as $k) {
                        if ($this->hitung('SELECT COUNT(*) AS n FROM pengaturan WHERE kunci = ?', [$k]) === 0) {
                            return true;
                        }
                    }
                    return false;
                },
                function () {
                    $defaults = [
                        'kode_satker' => '61513',
                        'kode_klasifikasi' => '650',
                        'format_nomor' => 'B-{urut}/{satker}/KP.{klas}/{tahun}',
                        'tempat_default' => 'Pontianak',
                        'awalan_untuk' => 'Mengikuti Kegiatan',
                        'kop_baris1' => 'BADAN PUSAT STATISTIK',
                        'kop_baris2' => 'PROVINSI KALIMANTAN BARAT',
                        'menimbang_a' => 'bahwa merujuk surat dari {pengirim} nomor {nomor} hal {hal}, maka dipandang perlu menyesuaikan dengan jadwal kegiatan tersebut;',
                        'menimbang_b' => 'bahwa untuk pelaksanaannya perlu dikeluarkan Surat Tugas Kepala BPS Provinsi Kalimantan Barat;',
                        'kalimat_memberi_tugas' => 'Memberi Tugas:'
                    ];
                    foreach ($defaults as $k => $v) {
                        $this->jalankan('INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES (?, ?)', [$k, $v]);
                    }
                }],
        ];
    }

    /** Daftar judul langkah yang masih diperlukan. */
    public function rencana()
    {
        $r = [];
        foreach ($this->langkah() as $l) {
            if ($l[1]()) {
                $r[] = $l[0];
            }
        }
        if ($this->versi_sekarang() < self::VERSI && !$r) {
            $r[] = 'Menandai database sebagai versi terbaru';
        }
        return $r;
    }

    /** Menjalankan semua langkah yang diperlukan. Mengembalikan daftar pekerjaan yang dilakukan. */
    public function terapkan()
    {
        $selesai = [];
        foreach ($this->langkah() as $l) {
            if ($l[1]()) {
                $l[2]();
                $selesai[] = $l[0];
            }
        }
        $this->jalankan("INSERT INTO pengaturan (kunci, nilai) VALUES ('versi_skema', ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)", [(string) self::VERSI]);
        return $selesai;
    }
}
