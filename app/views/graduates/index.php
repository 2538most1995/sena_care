<?php if (!defined('APP_ROOT')) die(); ?>
<div class="container-fluid px-3 px-md-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-award-fill text-primary me-2"></i>ติดตามผู้สำเร็จการศึกษา</h4>
            <p class="text-muted mb-0 small">ผู้สำเร็จการศึกษาจาก SDL_school · <?= isTeacher() ? 'แสดงเฉพาะกลุ่มเรียนที่ท่านรับผิดชอบ' : 'ทุกกลุ่มเรียน' ?> · บันทึกผลการนำวุฒิการศึกษาไปใช้</p>
        </div>
        <?php if (isAdmin()): ?>
        <form method="post" action="<?= e(url('graduates/sync')) ?>" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="term" value="<?= e($filters['term']) ?>">
            <button class="btn btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>ดึงข้อมูลจาก API</button>
            <small class="d-block text-muted mt-2">ทุกภาคเรียนตั้งแต่ <?= e(GraduateSyncService::HISTORY_START_TERM) ?> ถึงล่าสุด</small>
        </form>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= e(url('graduates')) ?>" class="card card-body border-0 shadow-sm mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small" for="gradTerm">ภาคเรียนที่สำเร็จ</label>
                <select class="form-select" id="gradTerm" name="term">
                    <?php if (!$terms): ?><option value="">-</option><?php endif; ?>
                    <?php foreach ($terms as $term): ?>
                    <option value="<?= e($term) ?>" <?= $filters['term'] === $term ? 'selected' : '' ?>><?= e($term) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="gradCenter">กลุ่มเรียน / ศกร.</label>
                <select class="form-select" id="gradCenter" name="center_id">
                    <option value="">ทุกกลุ่มเรียน</option>
                    <?php foreach ($centers as $center): ?>
                    <option value="<?= (int)$center['id'] ?>" <?= (int)$filters['center_id'] === (int)$center['id'] ? 'selected' : '' ?>><?= e($center['center_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small" for="gradSearch">ค้นหา</label>
                <input class="form-control" id="gradSearch" name="search" value="<?= e($filters['search']) ?>" placeholder="ชื่อหรือรหัสนักศึกษา">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="gradTrack">สถานะติดตาม</label>
                <select class="form-select" id="gradTrack" name="tracking_status">
                    <?php foreach (['' => 'ทั้งหมด', 'tracked' => 'ติดตามแล้ว', 'untracked' => 'ยังไม่ติดตาม'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $filters['tracking_status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>กรอง</button>
            </div>
        </div>
    </form>

    <p>พบ <?= number_format($roster['total']) ?> คน</p>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table records-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ผู้สำเร็จการศึกษา</th>
                        <th>ระดับ</th>
                        <th>กลุ่มเรียน</th>
                        <th>ภาคเรียน</th>
                        <th>ผลการติดตาม</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($roster['data'] as $g): $count = (int)$g['tracking_count']; ?>
                    <tr data-graduate-id="<?= (int)$g['id'] ?>">
                        <td>
                            <strong><?= e($g['full_name']) ?></strong>
                            <br><small class="text-muted"><?= e($g['student_code']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e($g['level_label'] ?: '-') ?></span></td>
                        <td><?= e($g['group_name'] ?: '-') ?></td>
                        <td><?= e($g['graduation_term']) ?></td>
                        <td data-tracking-status>
                            <?php if ($count): ?>
                            <span class="badge bg-success">ติดตามแล้ว <?= $count ?> ครั้ง</span>
                            <?php if (!empty($g['latest_tracked_at'])): ?><br><small class="text-muted">ล่าสุด <?= e(thaiDateTime($g['latest_tracked_at'])) ?></small><?php endif; ?>
                            <?php else: ?>
                            <span class="badge bg-secondary">ยังไม่ติดตาม</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm <?= $count ? 'btn-outline-primary' : 'btn-primary' ?> btn-icon"
                               href="<?= e(url('graduates/' . $g['id'])) ?>"
                               data-panel-url="<?= e(url('graduates/' . $g['id'])) ?>"
                               aria-haspopup="dialog" aria-controls="graduatePanelModal"
                               aria-label="<?= e((hasRole('executive') ? 'ดูผลการติดตาม: ' : 'บันทึก/ดูผลการติดตาม: ') . $g['full_name']) ?>"
                               title="<?= hasRole('executive') ? 'ดูรายละเอียด' : 'บันทึก/ดูผลการติดตาม' ?>"
                               data-bs-toggle="tooltip" data-bs-placement="top">
                                <i class="bi <?= hasRole('executive') ? 'bi-eye' : 'bi-clipboard2-check' ?>"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$roster['data']): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-award fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <span class="d-block">ไม่พบผู้สำเร็จการศึกษา</span>
                            <?php if (isAdmin()): ?>
                            <small class="d-block mt-1">กด "ดึงข้อมูลจาก API" เพื่อนำเข้าข้อมูลผู้สำเร็จการศึกษาของภาคเรียนที่เลือก</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3"><?= paginationHtml($page, $roster['total_pages'], url('graduates'), ['term' => $filters['term'], 'search' => $filters['search'], 'center_id' => $filters['center_id'], 'tracking_status' => $filters['tracking_status']]) ?></div>
</div>

<div class="modal fade" id="graduatePanelModal" tabindex="-1" aria-labelledby="graduatePanelTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header gap-3">
                <h5 class="modal-title fw-bold" id="graduatePanelTitle"><i class="bi bi-clipboard2-check text-primary me-2" aria-hidden="true"></i>ผลการติดตามผู้สำเร็จการศึกษา</h5>
                <button type="button" class="btn-close ms-auto flex-shrink-0" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body" id="graduatePanelBody" aria-live="polite" tabindex="-1"></div>
            <div class="modal-footer d-none"></div>
        </div>
    </div>
</div>
