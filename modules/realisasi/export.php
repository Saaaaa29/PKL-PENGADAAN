<?php
/**
 * modules/realisasi/export.php
 * Export ke .xlsx — pola ZipArchive sama dengan rencana/export.php
 * Kolom vendor: Nama Vendor | Nilai Kontrak Vendor (No.Kontrak & Tgl dihapus)
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$tahun  = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$status = $_GET['status'] ?? '';

$where = ["YEAR(r.tanggal_mulai) = $tahun"];
if ($status) $where[] = "r.status = '" . $db->real_escape_string($status) . "'";
$whereStr = 'WHERE ' . implode(' AND ', $where);

/* ── Ambil data ─────────────────────────────────────────────── */
$qReal = $db->query("
    SELECT r.id, r.nomor_kontrak, r.tanggal_mulai, r.tanggal_selesai,
           r.metode_pengadaan, r.status, r.catatan
    FROM realisasi_kegiatan r $whereStr
    ORDER BY r.tanggal_mulai ASC, r.id ASC");

$realisasiList = [];
while ($r = $qReal->fetch_assoc())
    $realisasiList[$r['id']] = $r + ['items' => [], 'vendors' => []];

if (!empty($realisasiList)) {
    $ids = implode(',', array_keys($realisasiList));

    $qD = $db->query("SELECT realisasi_id, nama_kegiatan, volume, satuan,
                             nilai_satuan, nilai_anggaran, jenis_pengadaan, rencana_id
                      FROM realisasi_detail WHERE realisasi_id IN ($ids)
                      ORDER BY realisasi_id ASC, id ASC");
    while ($d = $qD->fetch_assoc())
        $realisasiList[$d['realisasi_id']]['items'][] = $d;

    $qV = $db->query("SELECT realisasi_id, nama_vendor, nilai_kontrak
                      FROM realisasi_vendor WHERE realisasi_id IN ($ids)
                      ORDER BY realisasi_id ASC, id ASC");
    while ($v = $qV->fetch_assoc())
        $realisasiList[$v['realisasi_id']]['vendors'][] = $v;
}

/* ── helpers ── */
function xe($s)  { return htmlspecialchars((string)$s, ENT_XML1|ENT_QUOTES,'UTF-8'); }
function cl($n)  { $r=''; while($n>0){$r=chr(65+($n-1)%26).$r;$n=intdiv($n-1,26);} return $r; }
function tgl(?string $d): string { return ($d && $d!=='0000-00-00') ? date('d/m/Y',strtotime($d)) : '-'; }

$STR=[]; $SI=[];
function ss($v) { global $STR,$SI; $v=(string)$v;
    if(!isset($SI[$v])){$SI[$v]=count($STR);$STR[]=$v;} return $SI[$v]; }

/* ── Kolom (16 total, A–P) ─────────────────────────────────────
   A  No.
   B  No. Kontrak
   C  Tgl Mulai
   D  Tgl Selesai
   E  Metode Pengadaan
   F  Status
   G  Nama Kegiatan
   H  Jenis Pengadaan
   I  Volume
   J  Satuan
   K  Nilai Satuan (Rp)
   L  Total Item (Rp)
   M  Sumber
   N  Nama Vendor
   O  Nilai Kontrak Vendor (Rp)
   P  Catatan
─────────────────────────────────────────────────────────────── */
$NCOLS   = 16;
$lastCol = cl($NCOLS); // P

/* ── shared strings ── */
ss('REALISASI KEGIATAN PENGADAAN KORPORAT');
ss(defined('APP_FULLNAME') ? APP_FULLNAME : APP_NAME);
ss('TOTAL REALISASI (Rp)'); ss('TOTAL VENDOR (Rp)');
ss('Dari Rencana'); ss('Item Baru'); ss('-');
foreach([
    'No.','No. Kontrak','Tgl Mulai','Tgl Selesai','Metode Pengadaan','Status',
    'Nama Kegiatan','Jenis Pengadaan','Volume','Satuan',
    'Nilai Satuan (Rp)','Total Item (Rp)','Sumber',
    'Nama Vendor','Nilai Kontrak Vendor (Rp)','Catatan',
] as $h) ss($h);

/* pre-add data strings & hitung total */
$totalRealisasi = 0; $totalVendorAll = 0;
foreach ($realisasiList as $real) {
    ss($real['nomor_kontrak'] ?: '-');
    ss(getLabelMetode($real['metode_pengadaan']));
    ss(ucfirst($real['status']));
    ss($real['catatan'] ?: '');
    ss(tgl($real['tanggal_mulai']));
    ss(tgl($real['tanggal_selesai']));
    foreach ($real['items'] as $d) {
        ss($d['nama_kegiatan']); ss(getLabelJenis($d['jenis_pengadaan'])); ss($d['satuan']);
        $totalRealisasi += (float)$d['nilai_anggaran'];
    }
    foreach ($real['vendors'] as $v) {
        ss($v['nama_vendor']);
        $totalVendorAll += (float)$v['nilai_kontrak'];
    }
}

/* ── styles ──
   s=0  default
   s=1  judul       bold13, bg #1E3A52, putih, center
   s=2  instansi    italic9, bg #DBEAFE, center
   s=3  hdr-kolom   bold9, bg #1E3A52, putih, center, wrap
   s=4  data-teks   arial9, border, wrap, left
   s=5  data-center arial9, border, center
   s=6  data-angka  arial9, border, right, #,##0
   s=7  total-label bold9, bg #FFFBEB, right
   s=8  total-angka bold9, bg #FFFBEB, right, #,##0
   s=9  data-teks-zebra (bg #F8FAFC)
   s=10 data-center-zebra
   s=11 data-angka-zebra
*/
$STYLES = '<?xml version="1.0" encoding="UTF-8"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="1">
    <numFmt numFmtId="164" formatCode="#,##0"/>
  </numFmts>
  <fonts count="5">
    <font><sz val="10"/><name val="Arial"/></font>
    <font><sz val="13"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
    <font><sz val="9"/><i/><name val="Arial"/></font>
    <font><sz val="9"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
    <font><sz val="9"/><b/><name val="Arial"/></font>
  </fonts>
  <fills count="6">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A52"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFFBEB"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>
  </fills>
  <borders count="3">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFB0C4D8"/></left>
      <right style="thin"><color rgb="FFB0C4D8"/></right>
      <top style="thin"><color rgb="FFB0C4D8"/></top>
      <bottom style="thin"><color rgb="FFB0C4D8"/></bottom>
    </border>
    <border>
      <left style="thin"><color rgb="FFCCCCCC"/></left>
      <right style="thin"><color rgb="FFCCCCCC"/></right>
      <top style="thin"><color rgb="FFCCCCCC"/></top>
      <bottom style="thin"><color rgb="FFCCCCCC"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="12">
    <xf numFmtId="0"   fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0"   fontId="1" fillId="2" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="2" fillId="3" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="3" fillId="2" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="0" fillId="0" borderId="1" xfId="0"><alignment vertical="top" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="0" fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="top"/></xf>
    <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0"><alignment horizontal="right" vertical="top"/></xf>
    <xf numFmtId="0"   fontId="4" fillId="4" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="4" fillId="4" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="0" fillId="5" borderId="2" xfId="0"><alignment vertical="top" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="0" fillId="5" borderId="2" xfId="0"><alignment horizontal="center" vertical="top"/></xf>
    <xf numFmtId="164" fontId="0" fillId="5" borderId="2" xfId="0"><alignment horizontal="right" vertical="top"/></xf>
  </cellXfs>
</styleSheet>';

/* ── worksheet XML ── urutan BENAR: sheetViews > cols > sheetData > mergeCells ── */
$ws  = '<?xml version="1.0" encoding="UTF-8"?>';
$ws .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

/* 1. sheetViews — freeze 6 kolom kiri, 3 baris atas */
$ws .= '<sheetViews>';
$ws .= '<sheetView workbookViewId="0">';
$ws .= '<pane xSplit="6" ySplit="3" topLeftCell="G4" activePane="bottomRight" state="frozen"/>';
$ws .= '<selection pane="topRight"/>';
$ws .= '<selection pane="bottomLeft"/>';
$ws .= '<selection pane="bottomRight" activeCell="G4" sqref="G4"/>';
$ws .= '</sheetView>';
$ws .= '</sheetViews>';

/* 2. sheetFormatPr */
$ws .= '<sheetFormatPr baseColWidth="10" defaultRowHeight="15"/>';

/* 3. cols */
$ws .= '<cols>';
$ws .= '<col min="1"  max="1"  width="5"  customWidth="1"/>'; // A  No
$ws .= '<col min="2"  max="2"  width="22" customWidth="1"/>'; // B  No. Kontrak
$ws .= '<col min="3"  max="3"  width="11" customWidth="1"/>'; // C  Tgl Mulai
$ws .= '<col min="4"  max="4"  width="11" customWidth="1"/>'; // D  Tgl Selesai
$ws .= '<col min="5"  max="5"  width="20" customWidth="1"/>'; // E  Metode
$ws .= '<col min="6"  max="6"  width="10" customWidth="1"/>'; // F  Status
$ws .= '<col min="7"  max="7"  width="30" customWidth="1"/>'; // G  Nama Kegiatan
$ws .= '<col min="8"  max="8"  width="14" customWidth="1"/>'; // H  Jenis
$ws .= '<col min="9"  max="9"  width="8"  customWidth="1"/>'; // I  Volume
$ws .= '<col min="10" max="10" width="8"  customWidth="1"/>'; // J  Satuan
$ws .= '<col min="11" max="11" width="16" customWidth="1"/>'; // K  Nilai Satuan
$ws .= '<col min="12" max="12" width="16" customWidth="1"/>'; // L  Total Item
$ws .= '<col min="13" max="13" width="12" customWidth="1"/>'; // M  Sumber
$ws .= '<col min="14" max="14" width="30" customWidth="1"/>'; // N  Nama Vendor
$ws .= '<col min="15" max="15" width="18" customWidth="1"/>'; // O  Nilai Vendor
$ws .= '<col min="16" max="16" width="26" customWidth="1"/>'; // P  Catatan
$ws .= '</cols>';

/* 4. sheetData */
$ws .= '<sheetData>';

// Row 1: Judul
$ws .= '<row r="1" ht="26" customHeight="1">';
$ws .= '<c r="A1" t="s" s="1"><v>'.ss('REALISASI KEGIATAN PENGADAAN KORPORAT').'</v></c>';
$ws .= '</row>';

// Row 2: Instansi
$ws .= '<row r="2" ht="14" customHeight="1">';
$ws .= '<c r="A2" t="s" s="2"><v>'.ss(defined('APP_FULLNAME') ? APP_FULLNAME : APP_NAME).'</v></c>';
$ws .= '</row>';

// Row 3: Header kolom
$ws .= '<row r="3" ht="32" customHeight="1">';
foreach([
    'A'=>'No.','B'=>'No. Kontrak','C'=>'Tgl Mulai','D'=>'Tgl Selesai',
    'E'=>'Metode Pengadaan','F'=>'Status',
    'G'=>'Nama Kegiatan','H'=>'Jenis Pengadaan','I'=>'Volume','J'=>'Satuan',
    'K'=>'Nilai Satuan (Rp)','L'=>'Total Item (Rp)','M'=>'Sumber',
    'N'=>'Nama Vendor','O'=>'Nilai Kontrak Vendor (Rp)','P'=>'Catatan',
] as $c => $h)
    $ws .= '<c r="'.$c.'3" t="s" s="3"><v>'.ss($h).'</v></c>';
$ws .= '</row>';

/* Data rows (mulai row 4) */
$rn     = 4;
$no     = 1;
$merges = [];

foreach ($realisasiList as $rid => $real) {
    $items    = $real['items'];
    $vendors  = $real['vendors'];
    $maxBaris = max(count($items), count($vendors), 1);
    $isZebra  = ($no % 2 === 0);

    $sTeks   = $isZebra ? 9  : 4;
    $sCenter = $isZebra ? 10 : 5;
    $sAngka  = $isZebra ? 11 : 6;

    // Merge kolom realisasi (A–F) & catatan (P=16) jika > 1 baris
    if ($maxBaris > 1) {
        $endR = $rn + $maxBaris - 1;
        foreach ([1,2,3,4,5,6,16] as $ci)  // 16 = kolom P
            $merges[] = cl($ci).$rn.':'.cl($ci).$endR;
    }

    for ($i = 0; $i < $maxBaris; $i++) {
        $item    = $items[$i]   ?? null;
        $vendor  = $vendors[$i] ?? null;
        $isFirst = ($i === 0);

        $ws .= '<row r="'.$rn.'" ht="18">';

        // ── A–F: info realisasi (rowspan via merge) ──
        if ($isFirst) {
            $ws .= '<c r="A'.$rn.'" t="n" s="'.$sCenter.'"><v>'.$no.'</v></c>';
            $ws .= '<c r="B'.$rn.'" t="s" s="'.$sTeks.'"><v>'.ss($real['nomor_kontrak'] ?: '-').'</v></c>';
            $ws .= '<c r="C'.$rn.'" t="s" s="'.$sCenter.'"><v>'.ss(tgl($real['tanggal_mulai'])).'</v></c>';
            $ws .= '<c r="D'.$rn.'" t="s" s="'.$sCenter.'"><v>'.ss(tgl($real['tanggal_selesai'])).'</v></c>';
            $ws .= '<c r="E'.$rn.'" t="s" s="'.$sTeks.'"><v>'.ss(getLabelMetode($real['metode_pengadaan'])).'</v></c>';
            $ws .= '<c r="F'.$rn.'" t="s" s="'.$sCenter.'"><v>'.ss(ucfirst($real['status'])).'</v></c>';
        } else {
            foreach ([1,2,3,4,5,6] as $ci)
                $ws .= '<c r="'.cl($ci).$rn.'" s="'.$sTeks.'"/>';
        }

        // ── G–M: item ──
        if ($item) {
            $ws .= '<c r="G'.$rn.'" t="s" s="'.$sTeks.'"><v>'.ss($item['nama_kegiatan']).'</v></c>';
            $ws .= '<c r="H'.$rn.'" t="s" s="'.$sCenter.'"><v>'.ss(getLabelJenis($item['jenis_pengadaan'])).'</v></c>';
            $ws .= '<c r="I'.$rn.'" t="n" s="'.$sCenter.'"><v>'.(float)$item['volume'].'</v></c>';
            $ws .= '<c r="J'.$rn.'" t="s" s="'.$sCenter.'"><v>'.ss($item['satuan']).'</v></c>';
            $ws .= '<c r="K'.$rn.'" t="n" s="'.$sAngka.'"><v>'.(float)$item['nilai_satuan'].'</v></c>';
            $ws .= '<c r="L'.$rn.'" t="n" s="'.$sAngka.'"><v>'.(float)$item['nilai_anggaran'].'</v></c>';
            $ws .= '<c r="M'.$rn.'" t="s" s="'.$sCenter.'"><v>'.ss($item['rencana_id'] ? 'Dari Rencana' : 'Item Baru').'</v></c>';
        } else {
            foreach ([7,8,9,10,11,12,13] as $ci)
                $ws .= '<c r="'.cl($ci).$rn.'" s="'.$sTeks.'"/>';
        }

        // ── N–O: vendor (Nama Vendor | Nilai Kontrak) ──
        if ($vendor) {
            $ws .= '<c r="N'.$rn.'" t="s" s="'.$sTeks.'"><v>'.ss($vendor['nama_vendor']).'</v></c>';
            $ws .= '<c r="O'.$rn.'" t="n" s="'.$sAngka.'"><v>'.(float)$vendor['nilai_kontrak'].'</v></c>';
        } else {
            foreach ([14,15] as $ci)
                $ws .= '<c r="'.cl($ci).$rn.'" s="'.$sTeks.'"/>';
        }

        // ── P: catatan (rowspan via merge) ──
        if ($isFirst)
            $ws .= '<c r="P'.$rn.'" t="s" s="'.$sTeks.'"><v>'.ss($real['catatan'] ?: '').'</v></c>';
        else
            $ws .= '<c r="P'.$rn.'" s="'.$sTeks.'"/>';

        $ws .= '</row>';
        $rn++;
    }

    $no++;
}

// Total row
$ws .= '<row r="'.$rn.'" ht="22" customHeight="1">';
$ws .= '<c r="A'.$rn.'" t="s" s="7"><v>'.ss('TOTAL REALISASI (Rp)').'</v></c>';
$ws .= '<c r="L'.$rn.'" t="n" s="8"><v>'.$totalRealisasi.'</v></c>';
$ws .= '<c r="N'.$rn.'" t="s" s="7"><v>'.ss('TOTAL VENDOR (Rp)').'</v></c>';
$ws .= '<c r="O'.$rn.'" t="n" s="8"><v>'.$totalVendorAll.'</v></c>';
$ws .= '</row>';

$ws .= '</sheetData>';

/* 5. mergeCells — SETELAH sheetData */
$ws .= '<mergeCells>';
$ws .= '<mergeCell ref="A1:'.$lastCol.'1"/>';        // judul
$ws .= '<mergeCell ref="A2:'.$lastCol.'2"/>';        // instansi
$ws .= '<mergeCell ref="A'.$rn.':K'.$rn.'"/>';      // label total realisasi A–K
$ws .= '<mergeCell ref="M'.$rn.':N'.$rn.'"/>';      // label total vendor M–N
foreach ($merges as $ref)
    $ws .= '<mergeCell ref="'.$ref.'"/>';
$ws .= '</mergeCells>';

$ws .= '<pageMargins left="0.5" right="0.5" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>';
$ws .= '<pageSetup orientation="landscape" fitToPage="1" fitToWidth="1" fitToHeight="0"/>';
$ws .= '</worksheet>';

/* ── shared strings final ── */
$ssXml  = '<?xml version="1.0" encoding="UTF-8"?>';
$ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
       .  ' count="'.count($STR).'" uniqueCount="'.count($STR).'">';
foreach ($STR as $s) $ssXml .= '<si><t xml:space="preserve">'.xe($s).'</t></si>';
$ssXml .= '</sst>';

/* ── build XLSX (ZIP) ── */
$tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::OVERWRITE);

$zip->addFromString('[Content_Types].xml',
    '<?xml version="1.0" encoding="UTF-8"?>'
   .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
   .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
   .'<Default Extension="xml" ContentType="application/xml"/>'
   .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
   .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
   .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
   .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
   .'</Types>');

$zip->addFromString('_rels/.rels',
    '<?xml version="1.0" encoding="UTF-8"?>'
   .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
   .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
   .'</Relationships>');

$zip->addFromString('xl/_rels/workbook.xml.rels',
    '<?xml version="1.0" encoding="UTF-8"?>'
   .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
   .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
   .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
   .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
   .'</Relationships>');

$zip->addFromString('xl/workbook.xml',
    '<?xml version="1.0" encoding="UTF-8"?>'
   .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
   .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
   .'<sheets><sheet name="'.xe('Realisasi '.$tahun).'" sheetId="1" r:id="rId1"/></sheets>'
   .'</workbook>');

$zip->addFromString('xl/styles.xml',         $STYLES);
$zip->addFromString('xl/sharedStrings.xml',  $ssXml);
$zip->addFromString('xl/worksheets/sheet1.xml', $ws);
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Realisasi_Kegiatan_'.$tahun.($status ? '_'.ucfirst($status) : '').'.xlsx"');
header('Cache-Control: max-age=0');
readfile($tmp);
unlink($tmp);
exit;