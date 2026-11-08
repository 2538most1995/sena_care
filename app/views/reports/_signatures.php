<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');
// Shared mPDF markup only; the caller supplies its own PDF table styling.
$signatureColumns = [];
if ($signatures['show_reporter']) $signatureColumns[] = [
    $signatures['reporter_name'] !== '' ? $signatures['reporter_name'] : $reporterDefaultName,
    $signatures['reporter_position'], $signatures['reporter_label'],
];
if ($signatures['show_director']) $signatureColumns[] = [
    $signatures['director_name'], ReportSettings::directorPositionForReport($signatures['director_position']), $signatures['director_label'],
];
// A4 content is 174 mm wide; fixed, equal gutters keep mPDF's column sizing symmetric.
$signatureBlockWidth = $signatureColumns ? 174 / count($signatureColumns) - 2 : 0;
$signatureGutterWidth = $signatureBlockWidth * .32;
$signatureLineWidth = $signatureBlockWidth * .36;
?>
<?php if ($signatureColumns): ?>
<table class="<?= $escape($signatureTableClass) ?>"><tr>
<?php foreach ($signatureColumns as [$signatureName, $signaturePosition, $signatureRole]): ?>
<td style="width:<?= count($signatureColumns) === 1 ? '100' : '50' ?>%;vertical-align:top;padding-left:1mm;padding-right:1mm">
<table style="width:<?= $signatureBlockWidth ?>mm;border-collapse:collapse;font-size:15pt"><tr>
<td style="width:<?= $signatureGutterWidth ?>mm;border:0;padding:0;text-align:right;vertical-align:top;line-height:1.25"><?= $escape($signatures['signature_label']) ?>&nbsp;</td>
<td style="width:<?= $signatureLineWidth ?>mm;border:0;padding:0;text-align:center;vertical-align:top;line-height:1.25">................................</td>
<td style="width:<?= $signatureGutterWidth ?>mm;border:0;padding:0;text-align:left;vertical-align:top;line-height:1.25">&nbsp;<?= $escape($signatureRole) ?></td>
</tr><tr><td colspan="3" style="width:100%;border:0;padding:0;text-align:center;vertical-align:top;line-height:1.25">
(<?= $signatureName !== '' ? $escape($signatureName) : '....................................................' ?>)
<?php if ($signaturePosition !== ''): ?><br><?= nl2br($escape($signaturePosition), false) ?><?php endif; ?>
<?php if ($signatures['show_date']): ?><br><?= $escape($signatures['date_label']) ?> .......... / .......... / ..........<?php endif; ?>
</td></tr></table>
</td>
<?php endforeach; ?>
</tr></table>
<?php endif; ?>
