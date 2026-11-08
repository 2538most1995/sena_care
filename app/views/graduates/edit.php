<?php if (!defined('APP_ROOT')) die(); ?>
<div class="<?= !empty($isPanel) ? 'graduate-panel' : 'container-fluid px-3 px-md-4 py-3' ?>">
    <a href="<?= e(url('graduates/' . $graduate['id'])) ?>" data-panel-url="<?= e(url('graduates/' . $graduate['id'])) ?>" class="small d-inline-block mb-3">← กลับหน้าผู้สำเร็จการศึกษา</a>

    <?php include __DIR__ . '/_profile.php'; ?>

    <div class="w-100">
        <?php
        $formAction = url('graduates/' . $graduate['id'] . '/track/' . $tracking['id'] . '/edit');
        $graduateId = (int)$graduate['id'];
        include __DIR__ . '/_form.php';
        ?>
    </div>
</div>
