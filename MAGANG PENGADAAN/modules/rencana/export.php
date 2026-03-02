<?php
/**
 * modules/rencana/export.php
 * Export ke .xlsx — format jadwal bulan (sama dengan print.php)
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$jenis = $_GET['jenis'] ?? '';

$where = ["tahun = $tahun"];
if ($jenis && array_key_exists($jenis, LABEL_JENIS))
    $where[] = "jenis_pengadaan = '" . $db->real_escape_string($jenis) . "'";
$whereStr = 'WHERE ' . implode(' AND ', $where);

$result = $db->query("SELECT * FROM rencana_kegiatan $whereStr ORDER BY bulan_rencana ASC, id ASC");
$rows = []; $total = 0;
while ($r = $result->fetch_assoc()) { $rows[] = $r; $total += $r['nilai_anggaran']; }

/* ── helpers ── */
function xe($s)  { return htmlspecialchars((string)$s, ENT_XML1|ENT_QUOTES,'UTF-8'); }
function cl($n)  { $r=''; while($n>0){$r=chr(65+($n-1)%26).$r;$n=intdiv($n-1,26);} return $r; }

$STR=[]; $SI=[];
function ss($v)  { global $STR,$SI; $v=(string)$v;
    if(!isset($SI[$v])){$SI[$v]=count($STR);$STR[]=$v;} return $SI[$v]; }

/* ── shared strings ── */
$iJudul = ss('RENCANA KEGIATAN PENGADAAN TAHUN '.$tahun);
$iInst  = ss(defined('APP_FULLNAME') ? APP_FULLNAME : APP_NAME);
$iTotal = ss('TOTAL ANGGARAN');
ss('●');
foreach(['No.','Nama Kegiatan','Jenis Pengadaan','Metode Pengadaan',
         'Volume & Satuan','Nilai Anggaran (Rp)','Jadwal Pelaksanaan (Bulan)'] as $h) ss($h);
foreach(NAMA_BULAN as $b=>$nm) ss(substr($nm,0,3));

/* pre-add data strings */
foreach($rows as $r){
    ss($r['nama_kegiatan']); ss(getLabelJenis($r['jenis_pengadaan']));
    ss(getLabelMetode($r['metode_pengadaan']));
    ss(formatAngka($r['volume']).' '.$r['satuan']);
}

/* ── styles ──
   s=0  default
   s=1  judul       bold13, bg #1E3A52, putih, center
   s=2  instansi    italic9, bg #DBEAFE, center
   s=3  hdr-info    bold9, bg #1E3A52, putih, center, wrap
   s=4  hdr-bulan   bold8, bg #1E40AF, putih, center
   s=5  data-teks   arial9, border, wrap left
   s=6  data-center arial9, border, center
   s=7  data-angka  arial9, border, right, #,##0
   s=8  total-label bold9, bg #FFFBEB, right
   s=9  total-angka bold9, bg #FFFBEB, right, #,##0
   s=10 bulan-ada   bold9, bg #1E40AF, putih, center
   s=11 bulan-kosong arial9, bg #F1F5F9, center
   s=12 total-bulan  bg #FFFBEB, border
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
  <fills count="7">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A52"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E40AF"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFFBEB"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>
  </fills>
  <borders count="4">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFB0C4D8"/></left>
      <right style="thin"><color rgb="FFB0C4D8"/></right>
      <top style="thin"><color rgb="FFB0C4D8"/></top>
      <bottom style="thin"><color rgb="FFB0C4D8"/></bottom>
    </border>
    <border>
      <left style="thin"><color rgb="FF3B82F6"/></left>
      <right style="thin"><color rgb="FF3B82F6"/></right>
      <top style="thin"><color rgb="FF3B82F6"/></top>
      <bottom style="thin"><color rgb="FF3B82F6"/></bottom>
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
  <cellXfs count="13">
    <xf numFmtId="0"   fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0"   fontId="1" fillId="2" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="2" fillId="3" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="3" fillId="2" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="4" fillId="4" borderId="2" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="5" fillId="0" borderId="1" xfId="0"><alignment vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="5" fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="164" fontId="5" fillId="0" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="6" fillId="5" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="6" fillId="5" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="3" fillId="4" borderId="2" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="5" fillId="6" borderId="3" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="5" fillId="5" borderId="3" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
  </cellXfs>
</styleSheet>';

/* ── worksheet XML ── urutan BENAR: sheetViews > cols > sheetData > mergeCells ── */
$lastCol = cl(18); // R

$ws  = '<?xml version="1.0" encoding="UTF-8"?>';
$ws .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

/* 1. sheetViews (freeze panes) — HARUS pertama */
$ws .= '<sheetViews>';
$ws .= '<sheetView workbookViewId="0">';
$ws .= '<pane xSplit="6" ySplit="5" topLeftCell="G6" activePane="bottomRight" state="frozen"/>';
$ws .= '<selection pane="topRight"/>';
$ws .= '<selection pane="bottomLeft"/>';
$ws .= '<selection pane="bottomRight" activeCell="G6" sqref="G6"/>';
$ws .= '</sheetView>';
$ws .= '</sheetViews>';

/* 2. sheetFormatPr */
$ws .= '<sheetFormatPr baseColWidth="8" defaultRowHeight="15"/>';

/* 3. cols */
$ws .= '<cols>';
$ws .= '<col min="1" max="1" width="5"    customWidth="1"/>';
$ws .= '<col min="2" max="2" width="36"   customWidth="1"/>';
$ws .= '<col min="3" max="3" width="16"   customWidth="1"/>';
$ws .= '<col min="4" max="4" width="18"   customWidth="1"/>';
$ws .= '<col min="5" max="5" width="14"   customWidth="1"/>';
$ws .= '<col min="6" max="6" width="20"   customWidth="1"/>';
$ws .= '<col min="7" max="18" width="5.2" customWidth="1"/>';
$ws .= '</cols>';

/* 4. sheetData */
$ws .= '<sheetData>';

// Row 1: Judul
$ws .= '<row r="1" ht="26" customHeight="1">';
$ws .= '<c r="A1" t="s" s="1"><v>'.ss('RENCANA KEGIATAN PENGADAAN TAHUN '.$tahun).'</v></c>';
$ws .= '</row>';

// Row 2: Instansi
$ws .= '<row r="2" ht="14" customHeight="1">';
$ws .= '<c r="A2" t="s" s="2"><v>'.ss(defined('APP_FULLNAME') ? APP_FULLNAME : APP_NAME).'</v></c>';
$ws .= '</row>';

// Row 3: Kosong
$ws .= '<row r="3" ht="5" customHeight="1"></row>';

// Row 4: Header baris 1
$ws .= '<row r="4" ht="30" customHeight="1">';
foreach(['A'=>'No.','B'=>'Nama Kegiatan','C'=>'Jenis Pengadaan',
         'D'=>'Metode Pengadaan','E'=>'Volume & Satuan','F'=>'Nilai Anggaran (Rp)'] as $c=>$h)
    $ws .= '<c r="'.$c.'4" t="s" s="3"><v>'.ss($h).'</v></c>';
$ws .= '<c r="G4" t="s" s="3"><v>'.ss('Jadwal Pelaksanaan (Bulan)').'</v></c>';
$ws .= '</row>';

// Row 5: Header baris 2 — nama bulan (G-R)
$ws .= '<row r="5" ht="20" customHeight="1">';
foreach(['A','B','C','D','E','F'] as $c)
    $ws .= '<c r="'.$c.'5" s="3"></c>';
foreach(NAMA_BULAN as $b=>$nm){
    $col = cl(6+$b);
    $ws .= '<c r="'.$col.'5" t="s" s="4"><v>'.ss(substr($nm,0,3)).'</v></c>';
}
$ws .= '</row>';

// Data rows (mulai row 6)
$rn = 6;
foreach($rows as $no => $row){
    $volSat    = formatAngka($row['volume']).' '.$row['satuan'];
    $ws .= '<row r="'.$rn.'" ht="18">';
    $ws .= '<c r="A'.$rn.'" t="n" s="6"><v>'.($no+1).'</v></c>';
    $ws .= '<c r="B'.$rn.'" t="s" s="5"><v>'.ss($row['nama_kegiatan']).'</v></c>';
    $ws .= '<c r="C'.$rn.'" t="s" s="6"><v>'.ss(getLabelJenis($row['jenis_pengadaan'])).'</v></c>';
    $ws .= '<c r="D'.$rn.'" t="s" s="6"><v>'.ss(getLabelMetode($row['metode_pengadaan'])).'</v></c>';
    $ws .= '<c r="E'.$rn.'" t="s" s="6"><v>'.ss($volSat).'</v></c>';
    $ws .= '<c r="F'.$rn.'" t="n" s="7"><v>'.(float)$row['nilai_anggaran'].'</v></c>';
    // Bulan G-R
    for($b=1; $b<=12; $b++){
        $col = cl(6+$b);
        $ada = bulanAda($row['bulan_rencana'], $b);
        if($ada)
            $ws .= '<c r="'.$col.$rn.'" t="s" s="10"><v>'.ss('').'</v></c>';
        else
            $ws .= '<c r="'.$col.$rn.'" s="11"></c>';
    }
    $ws .= '</row>';
    $rn++;
}

// Total row
$ws .= '<row r="'.$rn.'" ht="22" customHeight="1">';
$ws .= '<c r="A'.$rn.'" t="s" s="8"><v>'.ss('TOTAL ANGGARAN').'</v></c>';
$ws .= '<c r="F'.$rn.'" t="n" s="9"><v>'.$total.'</v></c>';
for($b=1;$b<=12;$b++)
    $ws .= '<c r="'.cl(6+$b).$rn.'" s="12"></c>';
$ws .= '</row>';

$ws .= '</sheetData>';

/* 5. mergeCells — SETELAH sheetData */
$ws .= '<mergeCells>';
$ws .= '<mergeCell ref="A1:'.$lastCol.'1"/>';
$ws .= '<mergeCell ref="A2:'.$lastCol.'2"/>';
foreach(['A','B','C','D','E','F'] as $c)
    $ws .= '<mergeCell ref="'.$c.'4:'.$c.'5"/>';
$ws .= '<mergeCell ref="G4:'.$lastCol.'4"/>';
$ws .= '<mergeCell ref="A'.$rn.':E'.$rn.'"/>';
$ws .= '</mergeCells>';

$ws .= '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>';
$ws .= '</worksheet>';

/* ── shared strings final ── */
$ssXml  = '<?xml version="1.0" encoding="UTF-8"?>';
$ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
       .  ' count="'.count($STR).'" uniqueCount="'.count($STR).'">';
foreach($STR as $s) $ssXml .= '<si><t xml:space="preserve">'.xe($s).'</t></si>';
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
   .'<sheets><sheet name="'.xe('Rencana '.$tahun).'" sheetId="1" r:id="rId1"/></sheets>'
   .'</workbook>');

$zip->addFromString('xl/styles.xml',         $STYLES);
$zip->addFromString('xl/sharedStrings.xml',  $ssXml);
$zip->addFromString('xl/worksheets/sheet1.xml', $ws);
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Rencana_Kegiatan_'.$tahun.'.xlsx"');
header('Cache-Control: max-age=0');
readfile($tmp);
unlink($tmp);
exit;