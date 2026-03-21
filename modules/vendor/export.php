<?php
/**
 * modules/vendor/export.php
 * Export data vendor ke Excel (.xlsx) — gaya sama dengan rencana/export.php
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$cari  = trim($_GET['cari'] ?? '');

$cariSQL = '';
if ($cari !== '') {
    $cariEsc = $db->real_escape_string($cari);
    $cariSQL = "AND v.nama_vendor LIKE '%$cariEsc%'";
}

$qVendor = $db->query("
    SELECT
        v.nama_vendor,
        COUNT(DISTINCT v.realisasi_id)   AS jumlah_kontrak,
        SUM(v.nilai_kontrak)             AS total_nilai,
        AVG(v.nilai_kontrak)             AS rata_nilai,
        MIN(v.nilai_kontrak)             AS min_nilai,
        MAX(v.nilai_kontrak)             AS max_nilai,
        MIN(r.tanggal_mulai)             AS tgl_pertama,
        MAX(r.tanggal_mulai)             AS tgl_terakhir,
        GROUP_CONCAT(
            DISTINCT r.nomor_kontrak
            ORDER BY r.tanggal_mulai ASC
            SEPARATOR ', '
        )                                AS daftar_kontrak,
        GROUP_CONCAT(
            DISTINCT r.metode_pengadaan
            ORDER BY r.tanggal_mulai ASC
            SEPARATOR ','
        )                                AS daftar_metode
    FROM realisasi_vendor v
    JOIN realisasi_kegiatan r ON r.id = v.realisasi_id
    WHERE YEAR(r.tanggal_mulai) = $tahun
      AND r.status != 'batal'
      $cariSQL
    GROUP BY v.nama_vendor
    ORDER BY jumlah_kontrak DESC, total_nilai DESC
");

$vendors = [];
while ($row = $qVendor->fetch_assoc()) $vendors[] = $row;

/* ── helpers ── */
function xe($s)  { return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
function cl($n)  { $r = ''; while ($n > 0) { $r = chr(65 + ($n - 1) % 26) . $r; $n = intdiv($n - 1, 26); } return $r; }

$STR = []; $SI = [];
function ss($v)  {
    global $STR, $SI; $v = (string)$v;
    if (!isset($SI[$v])) { $SI[$v] = count($STR); $STR[] = $v; }
    return $SI[$v];
}

/* ── shared strings ── */
$judul = 'DATA VENDOR / PENYEDIA TAHUN ' . $tahun
    . ($cari ? ' — Filter: ' . $cari : '');
$inst  = defined('APP_FULLNAME') ? APP_FULLNAME : APP_NAME;

ss($judul);
ss($inst);
ss('TOTAL (' . count($vendors) . ' vendor)');

foreach ([
    'No.', 'Nama Vendor / Penyedia', 'Frekuensi Kontrak',
    'Total Nilai (Rp)', 'Rata-rata per Kontrak (Rp)',
    'Nilai Terendah (Rp)', 'Nilai Tertinggi (Rp)',
    'Tgl. Pertama', 'Tgl. Terakhir',
    'No. Kontrak', 'Metode Pengadaan',
] as $h) ss($h);

/* pre-add data strings */
foreach ($vendors as $v) {
    ss($v['nama_vendor']);
    $metodes = $v['daftar_metode']
        ? implode(', ', array_unique(array_map(
            fn($m) => getLabelMetode(trim($m)),
            explode(',', $v['daftar_metode'])
          )))
        : '-';
    ss($metodes);
    ss($v['daftar_kontrak'] ?: '-');
    ss($v['tgl_pertama']  ? date('d/m/Y', strtotime($v['tgl_pertama']))  : '-');
    ss($v['tgl_terakhir'] ? date('d/m/Y', strtotime($v['tgl_terakhir'])) : '-');
}

/* ── styles ──
   s=0  default
   s=1  judul       bold13, bg #1E3A52, putih, center
   s=2  instansi    italic9, bg #DBEAFE, center
   s=3  hdr-info    bold9, bg #1E3A52, putih, center, wrap
   s=4  data-teks   arial9, border, wrap left
   s=5  data-center arial9, border, center
   s=6  data-angka  arial9, border, right, #,##0
   s=7  data-tgl    arial9, border, center
   s=8  total-label bold9, bg #FFFBEB, right
   s=9  total-angka bold9, bg #FFFBEB, right, #,##0
*/
$STYLES = '<?xml version="1.0" encoding="UTF-8"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="1">
    <numFmt numFmtId="164" formatCode="#,##0"/>
  </numFmts>
  <fonts count="7">
    <font><sz val="10"/><name val="Arial"/></font>
    <font><sz val="13"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
    <font><sz val="9"/><i/><name val="Arial"/></font>
    <font><sz val="9"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
    <font><sz val="8"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
    <font><sz val="9"/><name val="Arial"/></font>
    <font><sz val="9"/><b/><name val="Arial"/></font>
  </fonts>
  <fills count="6">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A52"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFFBEB"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>
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
  <cellXfs count="10">
    <xf numFmtId="0"   fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0"   fontId="1" fillId="2" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="2" fillId="3" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="3" fillId="2" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="5" fillId="0" borderId="1" xfId="0"><alignment vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="5" fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="164" fontId="5" fillId="0" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="5" fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="6" fillId="4" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="6" fillId="4" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
  </cellXfs>
</styleSheet>';

/* ── worksheet ── urutan: sheetViews > sheetFormatPr > cols > sheetData > mergeCells ── */
$lastCol = cl(11); // K

$ws  = '<?xml version="1.0" encoding="UTF-8"?>';
$ws .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

/* 1. sheetViews — freeze 2 kolom kiri + 4 baris atas */
$ws .= '<sheetViews>';
$ws .= '<sheetView workbookViewId="0">';
$ws .= '<pane xSplit="2" ySplit="4" topLeftCell="C5" activePane="bottomRight" state="frozen"/>';
$ws .= '<selection pane="topRight"/>';
$ws .= '<selection pane="bottomLeft"/>';
$ws .= '<selection pane="bottomRight" activeCell="C5" sqref="C5"/>';
$ws .= '</sheetView>';
$ws .= '</sheetViews>';

/* 2. sheetFormatPr */
$ws .= '<sheetFormatPr baseColWidth="10" defaultRowHeight="15"/>';

/* 3. cols */
$ws .= '<cols>';
$ws .= '<col min="1"  max="1"  width="5"   customWidth="1"/>';  // No.
$ws .= '<col min="2"  max="2"  width="36"  customWidth="1"/>';  // Nama Vendor
$ws .= '<col min="3"  max="3"  width="13"  customWidth="1"/>';  // Frekuensi
$ws .= '<col min="4"  max="4"  width="20"  customWidth="1"/>';  // Total Nilai
$ws .= '<col min="5"  max="5"  width="20"  customWidth="1"/>';  // Rata-rata
$ws .= '<col min="6"  max="6"  width="20"  customWidth="1"/>';  // Min
$ws .= '<col min="7"  max="7"  width="20"  customWidth="1"/>';  // Max
$ws .= '<col min="8"  max="8"  width="12"  customWidth="1"/>';  // Tgl Pertama
$ws .= '<col min="9"  max="9"  width="12"  customWidth="1"/>';  // Tgl Terakhir
$ws .= '<col min="10" max="10" width="36"  customWidth="1"/>';  // No. Kontrak
$ws .= '<col min="11" max="11" width="22"  customWidth="1"/>';  // Metode
$ws .= '</cols>';

/* 4. sheetData */
$ws .= '<sheetData>';

// Row 1: Judul
$ws .= '<row r="1" ht="26" customHeight="1">';
$ws .= '<c r="A1" t="s" s="1"><v>' . ss($judul) . '</v></c>';
$ws .= '</row>';

// Row 2: Instansi
$ws .= '<row r="2" ht="14" customHeight="1">';
$ws .= '<c r="A2" t="s" s="2"><v>' . ss($inst) . '</v></c>';
$ws .= '</row>';

// Row 3: Kosong
$ws .= '<row r="3" ht="5" customHeight="1"></row>';

// Row 4: Header kolom
$ws .= '<row r="4" ht="36" customHeight="1">';
$headers = [
    'A' => 'No.',
    'B' => 'Nama Vendor / Penyedia',
    'C' => 'Frekuensi Kontrak',
    'D' => 'Total Nilai (Rp)',
    'E' => 'Rata-rata per Kontrak (Rp)',
    'F' => 'Nilai Terendah (Rp)',
    'G' => 'Nilai Tertinggi (Rp)',
    'H' => 'Tgl. Pertama',
    'I' => 'Tgl. Terakhir',
    'J' => 'No. Kontrak',
    'K' => 'Metode Pengadaan',
];
foreach ($headers as $col => $label)
    $ws .= '<c r="' . $col . '4" t="s" s="3"><v>' . ss($label) . '</v></c>';
$ws .= '</row>';

// Data rows (mulai row 5)
$rn = 5;
$grandTotal = 0;
$grandKontrak = 0;

foreach ($vendors as $no => $v) {
    $metodes = $v['daftar_metode']
        ? implode(', ', array_unique(array_map(
            fn($m) => getLabelMetode(trim($m)),
            explode(',', $v['daftar_metode'])
          )))
        : '-';
    $tglPertama  = $v['tgl_pertama']  ? date('d/m/Y', strtotime($v['tgl_pertama']))  : '-';
    $tglTerakhir = $v['tgl_terakhir'] ? date('d/m/Y', strtotime($v['tgl_terakhir'])) : '-';
    $kontrak     = $v['daftar_kontrak'] ?: '-';

    $grandTotal   += (float)$v['total_nilai'];
    $grandKontrak += (int)$v['jumlah_kontrak'];

    $ws .= '<row r="' . $rn . '" ht="18">';
    $ws .= '<c r="A' . $rn . '" t="n" s="5"><v>' . ($no + 1) . '</v></c>';
    $ws .= '<c r="B' . $rn . '" t="s" s="4"><v>' . ss($v['nama_vendor']) . '</v></c>';
    $ws .= '<c r="C' . $rn . '" t="n" s="5"><v>' . (int)$v['jumlah_kontrak'] . '</v></c>';
    $ws .= '<c r="D' . $rn . '" t="n" s="6"><v>' . (float)$v['total_nilai'] . '</v></c>';
    $ws .= '<c r="E' . $rn . '" t="n" s="6"><v>' . (float)$v['rata_nilai']  . '</v></c>';
    $ws .= '<c r="F' . $rn . '" t="n" s="6"><v>' . (float)$v['min_nilai']   . '</v></c>';
    $ws .= '<c r="G' . $rn . '" t="n" s="6"><v>' . (float)$v['max_nilai']   . '</v></c>';
    $ws .= '<c r="H' . $rn . '" t="s" s="7"><v>' . ss($tglPertama)  . '</v></c>';
    $ws .= '<c r="I' . $rn . '" t="s" s="7"><v>' . ss($tglTerakhir) . '</v></c>';
    $ws .= '<c r="J' . $rn . '" t="s" s="4"><v>' . ss($kontrak)     . '</v></c>';
    $ws .= '<c r="K' . $rn . '" t="s" s="4"><v>' . ss($metodes)     . '</v></c>';
    $ws .= '</row>';
    $rn++;
}

// Total row
$labelTotal = 'TOTAL (' . count($vendors) . ' vendor)';
$ws .= '<row r="' . $rn . '" ht="22" customHeight="1">';
$ws .= '<c r="A' . $rn . '" t="s" s="8"><v>' . ss($labelTotal) . '</v></c>';
$ws .= '<c r="C' . $rn . '" t="n" s="8"><v>' . $grandKontrak . '</v></c>';
$ws .= '<c r="D' . $rn . '" t="n" s="9"><v>' . $grandTotal   . '</v></c>';
foreach (['E','F','G','H','I','J','K'] as $ec)
    $ws .= '<c r="' . $ec . $rn . '" s="8"></c>';
$ws .= '</row>';

$ws .= '</sheetData>';

/* 5. mergeCells — SETELAH sheetData */
$ws .= '<mergeCells>';
$ws .= '<mergeCell ref="A1:' . $lastCol . '1"/>';
$ws .= '<mergeCell ref="A2:' . $lastCol . '2"/>';
$ws .= '<mergeCell ref="A' . $rn . ':B' . $rn . '"/>';
$ws .= '</mergeCells>';

$ws .= '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>';
$ws .= '<pageSetup orientation="landscape"/>';
$ws .= '</worksheet>';

/* ── shared strings final ── */
$ssXml  = '<?xml version="1.0" encoding="UTF-8"?>';
$ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
       .  ' count="' . count($STR) . '" uniqueCount="' . count($STR) . '">';
foreach ($STR as $s) $ssXml .= '<si><t xml:space="preserve">' . xe($s) . '</t></si>';
$ssXml .= '</sst>';

/* ── build XLSX (ZIP) ── */
$tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::OVERWRITE);

$zip->addFromString('[Content_Types].xml',
    '<?xml version="1.0" encoding="UTF-8"?>'
   . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
   . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
   . '<Default Extension="xml"  ContentType="application/xml"/>'
   . '<Override PartName="/xl/workbook.xml"            ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
   . '<Override PartName="/xl/worksheets/sheet1.xml"   ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
   . '<Override PartName="/xl/sharedStrings.xml"       ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
   . '<Override PartName="/xl/styles.xml"              ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
   . '</Types>');

$zip->addFromString('_rels/.rels',
    '<?xml version="1.0" encoding="UTF-8"?>'
   . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
   . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
   . '</Relationships>');

$zip->addFromString('xl/_rels/workbook.xml.rels',
    '<?xml version="1.0" encoding="UTF-8"?>'
   . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
   . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"    Target="worksheets/sheet1.xml"/>'
   . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
   . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"        Target="styles.xml"/>'
   . '</Relationships>');

$zip->addFromString('xl/workbook.xml',
    '<?xml version="1.0" encoding="UTF-8"?>'
   . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
   . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
   . '<sheets><sheet name="' . xe('Vendor ' . $tahun) . '" sheetId="1" r:id="rId1"/></sheets>'
   . '</workbook>');

$zip->addFromString('xl/styles.xml',                $STYLES);
$zip->addFromString('xl/sharedStrings.xml',         $ssXml);
$zip->addFromString('xl/worksheets/sheet1.xml',     $ws);
$zip->close();

$outFile = 'Vendor_' . $tahun
    . ($cari ? '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $cari) : '')
    . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $outFile . '"');
header('Cache-Control: max-age=0');
readfile($tmp);
unlink($tmp);
exit;