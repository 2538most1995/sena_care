<?php if(!defined('APP_ROOT')) die(); $rows=$studentsData['data']; ?>
<div class="container-fluid px-3 px-md-4 py-3">
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><h4 class="fw-bold mb-1">บันทึกเยี่ยมบ้าน</h4><p class="text-muted mb-0"><?= isTeacher()?'ผู้เรียนประจำกลุ่มที่ท่านรับผิดชอบ':'ผู้เรียนจาก SDL_school API ทุกกลุ่มเรียน' ?> · เลือกผู้เรียนแล้วบันทึกได้ทันที</p></div><a class="btn btn-outline-primary" href="<?= e(url('visits/history')) ?>">ประวัติการเยี่ยมทั้งหมด</a></div>
<form method="get" action="<?= e(url('visits')) ?>" class="card card-body border-0 shadow-sm mb-4"><div class="filter-grid">
<div><label class="form-label" for="visitSearch">ค้นหาผู้เรียน</label><input class="form-control" id="visitSearch" name="search" value="<?= e($filters['search']) ?>" placeholder="ชื่อหรือรหัสนักศึกษา"></div>
<div><label class="form-label" for="visitGroup">กลุ่มเรียน / ศกร.</label><select class="form-select" id="visitGroup" name="center_id"><option value="">ทุกกลุ่มเรียน<?= isTeacher()?'ที่รับผิดชอบ':'' ?></option><?php foreach($centers as $center): ?><option value="<?= $center['id'] ?>" <?= $filters['center_id']===(int)$center['id']?'selected':'' ?>><?= e($center['center_name']) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label" for="visitLevel">ระดับชั้น</label><select class="form-select" id="visitLevel" name="level"><?php foreach([''=>'ทุกระดับชั้น','primary'=>'ประถมศึกษา','lower_secondary'=>'มัธยมศึกษาตอนต้น','upper_secondary'=>'มัธยมศึกษาตอนปลาย'] as $value=>$label): ?><option value="<?= $value ?>" <?= $filters['level']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div><label class="form-label" for="visitTerm">ภาคเรียน</label><select class="form-select" id="visitTerm" name="term_id"><?php foreach($terms as $term): ?><option value="<?= $term['id'] ?>" <?= $filters['term_id']===(int)$term['id']?'selected':'' ?>><?= e($term['term_name']) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label" for="visitState">สถานะการเยี่ยม</label><select class="form-select" id="visitState" name="visit_status"><?php foreach([''=>'ทั้งหมด','not_visited'=>'ยังไม่เยี่ยม','visited'=>'เยี่ยมแล้ว'] as $value=>$label): ?><option value="<?= $value ?>" <?= $filters['visit_status']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="filter-actions"><button class="btn btn-primary">กรองรายชื่อ</button><a class="btn btn-outline-secondary" href="<?= e(url('visits')) ?>">ล้างตัวกรอง</a></div>
</div></form>
<p>พบผู้เรียน <?= number_format($studentsData['total']) ?> คน</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table records-table align-middle mb-0"><thead><tr><th>ผู้เรียน / ระดับชั้น</th><th>กลุ่มเรียน / ครูผู้เยี่ยม</th><th>สถานะการเยี่ยม</th><th class="text-center">จัดการ</th></tr></thead><tbody>
<?php foreach($rows as $row): ?>
<?php $rounds=$visitRounds[(int)$row['enrollment_id']]??[]; $selectedVisit=$rounds ? (int)$rounds[count($rounds)-1]['id'] : null; ?>
<tr><td><strong><?= e($row['prefix'].$row['firstname'].' '.$row['lastname']) ?></strong><br><small class="text-muted"><?= e($row['student_code']) ?> · <?= e(educationLevelName($row['education_level'])) ?></small></td><td><?= e($row['center_name']??'-') ?><br><small class="text-muted"><?= e($row['group_teacher_name']?:'ยังไม่ระบุครูประจำกลุ่ม') ?></small></td><td>
<?php if($rounds): ?>
<?php if(count($rounds)>1): ?>
<div class="dropdown d-inline-block">
<button type="button" class="badge bg-success dropdown-toggle visit-round-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="เลือกครั้งที่เยี่ยมของ <?= e($row['prefix'].$row['firstname'].' '.$row['lastname']) ?>"><span data-visit-status-label>เยี่ยมครั้งที่ <?= count($rounds) ?></span></button>
<input type="hidden" data-visit-round data-visit-enrollment="<?= (int)$row['enrollment_id'] ?>" data-visit-base="<?= e(url('visits')) ?>" value="<?= $selectedVisit ?>">
<ul class="dropdown-menu visit-round-menu">
<?php for($i=count($rounds)-1;$i>=0;$i--): $round=$rounds[$i]; ?>
<li><button type="button" class="dropdown-item <?= (int)$round['id']===$selectedVisit?'active':'' ?>" data-visit-choice data-visit-id="<?= (int)$round['id'] ?>" data-visit-round-number="<?= $i+1 ?>" data-visit-label="ครั้งที่ <?= $i+1 ?> · <?= e(thaiDate($round['visit_date'],'short')) ?>" aria-current="<?= (int)$round['id']===$selectedVisit?'true':'false' ?>">ครั้งที่ <?= $i+1 ?> · <?= e(thaiDate($round['visit_date'],'short')) ?></button></li>
<?php endfor; ?>
</ul>
</div>
<div class="small text-muted mt-1" data-visit-round-label aria-live="polite">ครั้งที่ <?= count($rounds) ?> · <?= e(thaiDate($rounds[count($rounds)-1]['visit_date'],'short')) ?></div>
<?php else: ?>
<span class="badge bg-success">เยี่ยมแล้ว 1 ครั้ง</span><br><small class="text-muted"><?= e(thaiDate($rounds[0]['visit_date'],'short')) ?></small>
<?php endif; ?>
<?php else: ?><span class="badge bg-secondary">ยังไม่เยี่ยม</span><?php endif; ?></td>
<td class="text-center"><div class="action-buttons action-buttons-nowrap gap-2">
<?php if(canCreateVisitForCenter((int)($row['learning_center_id'] ?? 0))): ?><a class="btn btn-sm <?= $row['visit_count']?'btn-outline-primary':'btn-primary' ?> btn-icon" href="<?= e(url('visits/create/'.$row['id'].'?term_id='.$filters['term_id'])) ?>" title="<?= $row['visit_count']?'บันทึกการเยี่ยมครั้งใหม่':'บันทึกเยี่ยมบ้าน' ?>" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-house-heart<?= $row['visit_count']?'':'-fill' ?>"></i></a><?php elseif(isTeacher()): ?><button type="button" class="btn btn-sm btn-outline-secondary btn-icon" disabled title="ปิดรับการเยี่ยมบ้านสำหรับศกร.นี้" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-lock"></i></button><?php endif; ?>
<?php if($selectedVisit): ?>
<a class="btn btn-sm btn-outline-secondary btn-icon" data-visit-action="view" href="<?= e(url('visits/'.$selectedVisit)) ?>" title="ดูบันทึกครั้งที่เลือก" aria-label="ดูบันทึกครั้งที่เลือก" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-eye"></i></a>
<a class="btn btn-sm btn-outline-primary btn-icon" data-visit-action="pdf" href="<?= e(url('visits/'.$selectedVisit.'/pdf')) ?>" target="_blank" rel="noopener" title="พิมพ์ PDF ครั้งที่เลือก" aria-label="พิมพ์ PDF ครั้งที่เลือก" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-printer"></i></a>
<?php if(hasRole('teacher','admin','super_admin')): ?><a class="btn btn-sm btn-outline-secondary btn-icon" data-visit-action="edit" href="<?= e(url('visits/'.$selectedVisit.'/edit')) ?>" title="แก้ไขบันทึกครั้งที่เลือก" aria-label="แก้ไขบันทึกครั้งที่เลือก" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-pencil"></i></a><?php endif; ?>
<?php if(canCancelRecords()): ?><form method="post" data-visit-action="cancel" action="<?= e(url('visits/'.$selectedVisit.'/cancel')) ?>" class="d-inline" onsubmit="return confirm('ยืนยันยกเลิกบันทึกการเยี่ยมครั้งที่เลือก? ประวัติจะยังถูกเก็บไว้');"><?= csrfField() ?><button class="btn btn-sm btn-outline-danger btn-icon" title="ยกเลิกบันทึกครั้งที่เลือก" aria-label="ยกเลิกบันทึกครั้งที่เลือก" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-x-circle"></i></button></form><?php endif; ?><?php endif; ?>
</div></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="4" class="text-center py-4">ไม่พบผู้เรียนตามเงื่อนไข</td></tr><?php endif; ?>
</tbody></table></div></div><div class="mt-3"><?= paginationHtml($page,$studentsData['total_pages'],url('visits'),$filters) ?></div>
</div>
