<?php
if (!defined('APP_ROOT')) { die('Direct access not permitted'); }

class AssessmentQuestion
{
    // Include the entire definition: different options or risk rules are not duplicates.
    public static function signature(array $q): string
    {
        $values = [];
        foreach (['form_id','section_name','question_text','question_type','options_json','required','risk_weight','risk_trigger_value','sort_order'] as $key) {
            $value = $q[$key] ?? null;
            $values[] = $key === 'options_json' && $value !== null
                ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : ($value === null ? null : (string)$value);
        }
        return hash('sha256', json_encode($values, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public static function active(PDO $pdo, int $formId = 1): array
    {
        $stmt = $pdo->prepare("SELECT q.* FROM assessment_questions q JOIN assessment_forms f ON f.id=q.form_id WHERE q.form_id=? AND q.status='active' AND f.status='active' ORDER BY q.sort_order,q.id");
        $stmt->execute([$formId]);
        return $stmt->fetchAll();
    }

    public static function validateAnswers(PDO $pdo, mixed $submitted): array
    {
        if (!is_array($submitted)) throw new RuntimeException('ข้อมูลแบบประเมินไม่ถูกต้อง');
        $questions = array_column(self::active($pdo), null, 'id');
        foreach ($submitted as $id => $_) {
            if (!ctype_digit((string)$id) || !isset($questions[(int)$id])) throw new RuntimeException('แบบประเมินมีการปรับปรุง กรุณารีเฟรชหน้าแล้วกรอกใหม่');
        }
        $answers = [];
        foreach ($questions as $id => $q) {
            $value = $submitted[$id] ?? null;
            if (is_string($value)) $value = trim($value);
            if ($value === null || $value === '' || $value === []) {
                if ($q['required']) throw new RuntimeException('กรุณาตอบ: '.$q['question_text']);
                continue;
            }
            $options = json_decode($q['options_json'] ?? '[]',true) ?: [];
            if ($q['question_type'] === 'checkbox') {
                if (!is_array($value) || count(array_filter($value,'is_string')) !== count($value) || array_diff($value,$options)) throw new RuntimeException('ตัวเลือกไม่ถูกต้อง: '.$q['question_text']);
                $value = json_encode(array_values(array_unique($value)),JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } else {
                if (!is_scalar($value)) throw new RuntimeException('คำตอบไม่ถูกต้อง: '.$q['question_text']);
                $value = trim((string)$value);
                if (in_array($q['question_type'],['radio','select'],true) && !in_array($value,$options,true)) throw new RuntimeException('ตัวเลือกไม่ถูกต้อง: '.$q['question_text']);
                if ($q['question_type'] === 'yes_no' && !in_array($value,['ใช่','ไม่ใช่'],true)) throw new RuntimeException('กรุณาเลือก ใช่ หรือ ไม่ใช่');
                if ($q['question_type'] === 'number' && (!is_numeric($value) || (float)$value < 0)) throw new RuntimeException('กรุณาระบุจำนวนที่ถูกต้อง');
            }
            $answers[$id] = $value;
        }
        return $answers;
    }

    public static function retireDuplicates(PDO $pdo): int
    {
        $seen = []; $count = 0;
        $stmt = $pdo->prepare("UPDATE assessment_questions SET status='inactive' WHERE id=?");
        foreach ($pdo->query("SELECT * FROM assessment_questions WHERE status='active' ORDER BY id")->fetchAll() as $q) {
            $key = self::signature($q);
            if (isset($seen[$key])) { $stmt->execute([$q['id']]); $count += $stmt->rowCount(); }
            else { $seen[$key] = true; }
        }
        return $count;
    }

    public static function answersForActive(PDO $pdo, array $historical): array
    {
        $active=self::active($pdo); $ids=array_column($active,'id'); $map=[]; $fallback=[];
        $definitions=[]; foreach($active as $q) $definitions[self::signature($q)]=$q['id'];
        foreach($historical as $answer) {
            $key=self::signature($answer);
            if (!isset($definitions[$key])) continue;
            $id=$definitions[$key];
            if (!array_key_exists($id,$fallback)) $fallback[$id]=$answer['answer_value'];
            if (in_array($answer['question_id'],$ids,true)) $map[$id]=$answer['answer_value'];
        }
        return $map+$fallback;
    }

    public static function uniqueAnswers(array $answers): array
    {
        $seen = []; $definitions = []; $result = [];
        foreach ($answers as $answer) {
            $definition = self::signature($answer);
            $key = $definition . ':' . (string)$answer['answer_value'];
            if (!isset($seen[$key])) {
                $answer['historical_variant'] = isset($definitions[$definition]);
                $definitions[$definition] = true; $seen[$key] = true; $result[] = $answer;
            }
        }
        return $result; // Conflicting historical answers remain visible; nothing is deleted.
    }
}
