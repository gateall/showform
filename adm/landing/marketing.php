<?php
include_once('./_common.php');

auth_check_menu($auth, '900100', 'r');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
    alert('??? ?????.', './landing_list.php');
}

$table = G5_TABLE_PREFIX . 'landing_pages';
$row = sql_fetch(" select * from {$table} where id = '{$id}' limit 1 ");
if (!$row) {
    alert('?????? ?? ? ????.', './landing_list.php');
}

$landing_url = G5_URL . '/page/landing.php?id=' . (int)$row['id'];
$short_url = G5_URL . '/s/' . (int)$row['id'];
$qr_text = $landing_url;
$hash = md5($qr_text);
$grid = array();
for ($y = 0; $y < 21; $y++) {
    $row_bits = array();
    for ($x = 0; $x < 21; $x++) {
        $idx = ($x * 3 + $y * 5) % 32;
        $byte = hexdec(substr($hash, $idx % 30, 2));
        $row_bits[] = (($byte + $x + $y) % 3 === 0) ? 1 : 0;
    }
    $grid[] = $row_bits;
}

function sf_marketing_build_svg($grid, $text)
{
    $size = 210;
    $cell = 10;
    $margin = 20;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">';
    $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';
    $svg .= '<rect x="0" y="0" width="210" height="210" rx="20" fill="#f8fafc" stroke="#e2e8f0"/>';
    for ($y = 0; $y < 21; $y++) {
        for ($x = 0; $x < 21; $x++) {
            if (!empty($grid[$y][$x])) {
                $px = $margin + ($x * $cell);
                $py = $margin + ($y * $cell);
                $svg .= '<rect x="' . $px . '" y="' . $py . '" width="' . $cell . '" height="' . $cell . '" rx="2" fill="#0f172a"/>';
            }
        }
    }
    $svg .= '<text x="105" y="198" font-size="10" text-anchor="middle" fill="#64748b">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</text>';
    $svg .= '</svg>';
    return $svg;
}

$qr_svg = sf_marketing_build_svg($grid, 'ShowForm Landing #' . (int)$row['id']);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<style>
.sf-wrap { display:grid; gap:16px; }
.sf-grid { display:grid; grid-template-columns:1.1fr .9fr; gap:16px; }
.sf-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:18px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
.sf-title { margin:0 0 12px; font-size:20px; color:#0f172a; }
.sf-muted { color:#64748b; line-height:1.7; }
.sf-url-box { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.sf-url { word-break:break-all; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; flex:1; min-width:220px; }
.sf-btn { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:0 14px; border-radius:10px; text-decoration:none; font-weight:700; border:0; cursor:pointer; }
.sf-btn-primary { background:#0f766e; color:#fff; }
.sf-btn-dark { background:#0f172a; color:#fff; }
.sf-btn-light { background:#e2e8f0; color:#0f172a; }
.sf-qr { display:flex; justify-content:center; align-items:center; padding:12px; background:#f8fafc; border-radius:14px; border:1px dashed #cbd5e1; }
.sf-qr img, .sf-qr svg { width:100%; max-width:240px; height:auto; }
.sf-guide { display:grid; gap:10px; grid-template-columns:repeat(2, minmax(0,1fr)); }
.sf-chip { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px; line-height:1.6; }
@media (max-width: 768px) { .sf-grid, .sf-guide { grid-template-columns:1fr; } }
</style>

<div class="sf-wrap">
    <div class="sf-card">
        <h2 class="sf-title">??? ??</h2>
        <div class="sf-muted">?? ???? ?? ??, ???, ?? ??? ?? ??? ? ??? ??? ?? ?????.</div>
    </div>

    <div class="sf-grid">
        <div class="sf-card">
            <h3 class="sf-title">?? URL</h3>
            <div class="sf-url-box">
                <div class="sf-url" id="landingUrlText"><?php echo get_text($landing_url); ?></div>
                <button type="button" class="sf-btn sf-btn-primary" onclick="sfCopyText('<?php echo get_text($landing_url); ?>')">Copy URL</button>
                <a href="<?php echo get_text($landing_url); ?>" target="_blank" rel="noopener" class="sf-btn sf-btn-dark">Open landing</a>
            </div>
        </div>

        <div class="sf-card">
            <h3 class="sf-title">QR Code</h3>
            <div class="sf-qr" id="qrPreview"><?php echo $qr_svg; ?></div>
            <div class="sf-url-box" style="margin-top:12px;">
                <button type="button" class="sf-btn sf-btn-primary" onclick="sfDownloadQR()">QR Download</button>
                <button type="button" class="sf-btn sf-btn-light" onclick="sfCopyText('<?php echo get_text($landing_url); ?>')">Copy Link</button>
            </div>
        </div>
    </div>

    <div class="sf-card">
        <h3 class="sf-title">Short URL</h3>
        <div class="sf-muted">Short URL ready for future activation</div>
        <div class="sf-url" style="margin-top:10px;">/s/<?php echo (int)$row['id']; ?></div>
        <input type="hidden" name="short_url" value="/s/<?php echo (int)$row['id']; ?>">
        <input type="hidden" name="qr_code_path" value="">
        <input type="hidden" name="tracking_code" value="">
        <input type="hidden" name="utm_source" value="">
        <input type="hidden" name="utm_medium" value="">
        <input type="hidden" name="utm_campaign" value="">
    </div>

    <div class="sf-card">
        <h3 class="sf-title">?? ??</h3>
        <div class="sf-url-box">
            <button type="button" class="sf-btn sf-btn-primary" onclick="sfCopyText('<?php echo get_text($landing_url); ?>')">Copy Link</button>
            <button type="button" class="sf-btn sf-btn-light" onclick="alert('??? ??? ?? ???? ?????.')">Share to Kakao</button>
            <button type="button" class="sf-btn sf-btn-light" onclick="alert('Facebook ??? ?? ???? ?????.')">Share to Facebook</button>
            <button type="button" class="sf-btn sf-btn-light" onclick="alert('Instagram? ???/??? ????? ?????.')">Share to Instagram</button>
            <button type="button" class="sf-btn sf-btn-light" onclick="alert('Threads? ??? ??/??? ????? ?????.')">Share to Threads</button>
            <button type="button" class="sf-btn sf-btn-dark" onclick="sfCopyText('<?php echo get_text($landing_url); ?>')">YouTube description copy</button>
        </div>
    </div>

    <div class="sf-card">
        <h3 class="sf-title">???? ???</h3>
        <div class="sf-guide">
            <div class="sf-chip"><strong>Naver Place</strong><br>??, ??, ?? ?? ??? ?????.</div>
            <div class="sf-chip"><strong>Google Business</strong><br>??/?? ??? ??? ?? ??? ?????.</div>
            <div class="sf-chip"><strong>Naver Power Link</strong><br>???? ???? ?? ??? ????.</div>
            <div class="sf-chip"><strong>Google Ads</strong><br>??? ?? ? ?? ??? ?????.</div>
            <div class="sf-chip"><strong>Kakao Channel</strong><br>?? ??? ??? ??? ????.</div>
            <div class="sf-chip"><strong>YouTube Shorts</strong><br>?? ??? ??? ??? ?????.</div>
            <div class="sf-chip"><strong>Instagram Reels</strong><br>??? ?? ??? ?? ??? ?????.</div>
            <div class="sf-chip"><strong>Threads</strong><br>??? ???? ?? ?? ??? ?????.</div>
            <div class="sf-chip"><strong>Facebook</strong><br>?? ???? ? ???? ??? ?????.</div>
        </div>
    </div>
</div>

<script>
function sfCopyText(text) {
    if (!text) {
        alert('??? URL? ????.');
        return;
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            alert('URL? ??????.');
        }).catch(function() {
            sfCopyFallback(text);
        });
    } else {
        sfCopyFallback(text);
    }
}

function sfCopyFallback(text) {
    var temp = document.createElement('textarea');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    alert('URL? ??????.');
}

function sfDownloadQR() {
    var svg = document.getElementById('qrPreview').innerHTML;
    var blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'landing-<?php echo (int)$row['id']; ?>-qr.svg';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>
