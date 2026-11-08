<?php if (!defined('APP_ROOT')) die(); $section=null; ?>
<?php foreach ($questions as $q): $value=$answersMap[$q['id']]??null;
if ($q['section_name']!==$section): $section=$q['section_name']; ?>
<h5 class="mt-4 mb-3 text-primary"><?= e($section) ?></h5>
<?php endif; ?>
<fieldset class="mb-4"><legend class="fs-6 fw-semibold"><?= e($q['question_text']) ?> <?= $q['required']?'<span class="text-danger">*</span>':'' ?></legend>
<?php if (in_array($q['question_type'],['radio','checkbox','yes_no'],true)):
    $options=$q['question_type']==='yes_no'?['ใช่','ไม่ใช่']:(json_decode($q['options_json']??'[]',true)?:[]);
    $selected=$q['question_type']==='checkbox'?(is_array($value)?$value:(json_decode($value??'[]',true)?:[])):[$value]; ?>
    <div class="d-flex flex-wrap gap-2 gap-sm-3">
    <?php foreach($options as $i=>$option): $inputId='sq_'.$q['id'].'_'.$i; ?>
    <div class="form-check"><input class="form-check-input" type="<?= $q['question_type']==='checkbox'?'checkbox':'radio' ?>" name="answers[<?= $q['id'] ?>]<?= $q['question_type']==='checkbox'?'[]':'' ?>" id="<?= $inputId ?>" value="<?= e($option) ?>" <?= in_array($option,$selected,true)?'checked':'' ?> <?= $q['required']&&$q['question_type']!=='checkbox'?'required':'' ?>><label class="form-check-label" for="<?= $inputId ?>"><?= e($option) ?></label></div>
    <?php endforeach; ?></div>
<?php elseif ($q['question_type']==='textarea'): ?>
<textarea aria-label="<?= e($q['question_text']) ?>" name="answers[<?= $q['id'] ?>]" class="form-control" rows="2" <?= $q['required']?'required':'' ?>><?= e(is_scalar($value)?(string)$value:'') ?></textarea>
<?php else: ?>
<input aria-label="<?= e($q['question_text']) ?>" name="answers[<?= $q['id'] ?>]" type="<?= $q['question_type']==='number'?'number':'text' ?>" class="form-control" value="<?= e(is_scalar($value)?(string)$value:'') ?>" <?= $q['question_type']==='number'?'min="0"':'' ?> <?= $q['required']?'required':'' ?>>
<?php endif; ?></fieldset>
<?php endforeach; ?>
