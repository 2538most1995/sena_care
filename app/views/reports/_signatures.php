<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');
// Shared mPDF markup only; the caller supplies its own PDF table styling.
$signatureColumns = [];
if ($signatures['show_reporter']) $signatureColumns[] = [
    $signatures['reporter_name'] !== '' ? $signatures['reporter_name'] : $reporterDefaultName,
    $signatures['reporter_position'], $signatures['reporter_label'],
];
if ($signatures['show_director']) $signatureColumns[] = [
    $signatures['director_name'], $signatures['director_position'], $signatures['director_label'],
];
?>
<?php if ($signatureColumns): ?>
<table class="<?= $escape($signatureTableClass) ?>"><tr>
<?php foreach ($signatureColumns as [$signatureName, $signaturePosition, $signatureRole]): ?>
<td style="width:<?= count($signatureColumns) === 1 ? '100' : '50' ?>%;vertical-align:top">
<?= $escape($signatures['signature_label']) ?> .................................................... <?= $escape($signatureRole) ?><br>
(<?= $signatureName !== '' ? $escape($signatureName) : '....................................................' ?>)
<?php if ($signaturePosition !== ''): ?><br><?= $escape($signaturePosition) ?><?php endif; ?>
<?php if ($signatures['show_date']): ?><br><?= $escape($signatures['date_label']) ?> .......... / .......... / ..........<?php endif; ?>
</td>
<?php endforeach; ?>
</tr></table>
<?php endif; ?>
