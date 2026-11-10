<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pembaca berkas .xlsx sederhana (tanpa pustaka tambahan). Membutuhkan ekstensi PHP zip dan SimpleXML.
 * Sel tanggal dikembalikan sebagai objek DateTime, sel lain sebagai teks, angka, atau boolean.
 */
class Xlsx_reader
{
    private $zip;
    private $stringBersama = null;
    private $formatTanggal = [];

    public function tersedia()
    {
        return class_exists('ZipArchive') && function_exists('simplexml_load_string');
    }

    /** Daftar sheet: [['nama' => ..., 'tampil' => bool], ...] */
    public function daftar_sheet($path)
    {
        $this->buka($path);
        $wb = $this->xml('xl/workbook.xml');
        $hasil = [];
        if ($wb && isset($wb->sheets->sheet)) {
            foreach ($wb->sheets->sheet as $sh) {
                $hasil[] = ['nama' => (string) $sh['name'], 'tampil' => ((string) $sh['state']) !== 'hidden'];
            }
        }
        $this->tutup();
        return $hasil;
    }

    /** Seluruh baris tidak kosong pada sheet bernama $nama; setiap baris berupa larik berindeks kolom (0 = A). */
    public function baca($path, $nama)
    {
        $this->buka($path);
        try {
            $file = $this->lokasi_sheet($nama);
            if ($file === null) {
                return [];
            }
            $this->muat_gaya();
            $this->muat_string_bersama();
            $ws = $this->xml($file);
            $baris = [];
            if ($ws && isset($ws->sheetData->row)) {
                foreach ($ws->sheetData->row as $row) {
                    $isi = [];
                    $kosong = true;
                    foreach ($row->c as $c) {
                        $kolom = $this->indeks_kolom((string) $c['r']);
                        $nilai = $this->nilai_sel($c);
                        if ($nilai !== null && $nilai !== '') {
                            $kosong = false;
                        }
                        $isi[$kolom] = $nilai;
                    }
                    if (!$kosong) {
                        $baris[] = $isi;
                    }
                }
            }
            return $baris;
        } finally {
            $this->tutup();
        }
    }

    // ------------------------------------------------------------------

    private function buka($path)
    {
        $this->zip = new ZipArchive();
        if ($this->zip->open($path) !== true) {
            throw new RuntimeException('Berkas xlsx tidak dapat dibuka.');
        }
        $this->stringBersama = null;
        $this->formatTanggal = [];
    }

    private function tutup()
    {
        if ($this->zip) {
            $this->zip->close();
            $this->zip = null;
        }
    }

    private function xml($nama)
    {
        $isi = $this->zip->getFromName($nama);
        if ($isi === false) {
            return null;
        }
        $x = @simplexml_load_string($isi, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        return $x ?: null;
    }

    private function lokasi_sheet($nama)
    {
        $wb = $this->xml('xl/workbook.xml');
        $rel = $this->xml('xl/_rels/workbook.xml.rels');
        if (!$wb || !$rel) {
            return null;
        }
        $rid = null;
        foreach ($wb->sheets->sheet as $sh) {
            if ((string) $sh['name'] === $nama) {
                $atribut = $sh->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $rid = (string) $atribut['id'];
            }
        }
        if ($rid === null) {
            return null;
        }
        foreach ($rel->Relationship as $r) {
            if ((string) $r['Id'] === $rid) {
                $target = (string) $r['Target'];
                return ltrim($target[0] === '/' ? $target : 'xl/' . $target, '/');
            }
        }
        return null;
    }

    private function muat_string_bersama()
    {
        $this->stringBersama = [];
        $x = $this->xml('xl/sharedStrings.xml');
        if (!$x) {
            return;
        }
        foreach ($x->si as $si) {
            if (isset($si->t)) {
                $this->stringBersama[] = (string) $si->t;
            } else {
                $teks = '';
                foreach ($si->r as $r) {
                    $teks .= (string) $r->t;
                }
                $this->stringBersama[] = $teks;
            }
        }
    }

    private function muat_gaya()
    {
        $this->formatTanggal = [];
        $x = $this->xml('xl/styles.xml');
        if (!$x) {
            return;
        }
        $kustom = [];
        if (isset($x->numFmts->numFmt)) {
            foreach ($x->numFmts->numFmt as $f) {
                $kustom[(int) $f['numFmtId']] = (string) $f['formatCode'];
            }
        }
        $bawaan = array_merge(range(14, 22), range(27, 36), range(45, 47), range(50, 58));
        $i = 0;
        if (isset($x->cellXfs->xf)) {
            foreach ($x->cellXfs->xf as $xf) {
                $id = (int) $xf['numFmtId'];
                $tanggal = in_array($id, $bawaan, true);
                if (!$tanggal && isset($kustom[$id])) {
                    $kode = preg_replace('/"[^"]*"|\[[^\]]*\]|\\\\./', '', $kustom[$id]);
                    $tanggal = (bool) preg_match('/[dmy]/i', $kode) && !preg_match('/^general$/i', trim($kode));
                }
                $this->formatTanggal[$i++] = $tanggal;
            }
        }
    }

    private function indeks_kolom($ref)
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $n = 0;
        foreach (str_split($m[1] ?? 'A') as $huruf) {
            $n = $n * 26 + (ord($huruf) - 64);
        }
        return $n - 1;
    }

    private function nilai_sel($c)
    {
        $tipe = (string) $c['t'];
        if ($tipe === 'inlineStr') {
            return (string) $c->is->t;
        }
        if (!isset($c->v)) {
            return null;
        }
        $v = (string) $c->v;
        if ($tipe === 's') {
            return isset($this->stringBersama[(int) $v]) ? $this->stringBersama[(int) $v] : '';
        }
        if ($tipe === 'str') {
            return $v;
        }
        if ($tipe === 'b') {
            return $v === '1';
        }
        if ($tipe === 'e') {
            return null;
        }
        if (!is_numeric($v)) {
            return $v;
        }
        $gaya = isset($c['s']) ? (int) $c['s'] : 0;
        if (!empty($this->formatTanggal[$gaya])) {
            $tanggal = new DateTime('1899-12-30 00:00:00');
            $tanggal->modify('+' . (int) floor((float) $v) . ' days');
            return $tanggal;
        }
        return (float) $v == (int) $v ? (int) $v : (float) $v;
    }
}
