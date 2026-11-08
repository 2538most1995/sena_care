<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

class SdlSyncService
{
    private PDO $pdo;
    private SdlApiClient $api;
    public function __construct(?SdlApiClient $api = null)
    {
        $this->pdo = Database::getConnection();
        $this->api = $api ?? new SdlApiClient();
    }
    public function linkedUser(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT source_key FROM sdl_links WHERE entity='teacher' AND local_id=?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    private function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt;
    }
    private function link(string $entity, string $key, int $id, array $snapshot): void
    {
        $this->execute('INSERT INTO sdl_links(entity,source_key,local_id,snapshot) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE snapshot=VALUES(snapshot),source_present=1,synced_at=CURRENT_TIMESTAMP', [$entity,$key,$id,json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
    private function localId(string $entity, string $key): ?int
    {
        $id = $this->execute('SELECT local_id FROM sdl_links WHERE entity=? AND source_key=?',[$entity,$key])->fetchColumn();
        return $id === false ? null : (int)$id;
    }
    public function sync(): array
    {
        if (!$this->pdo->query("SELECT GET_LOCK('sena_care_sdl_sync',0)")->fetchColumn()) throw new RuntimeException('กำลังซิงก์ข้อมูลอยู่ กรุณารอสักครู่');
        try {
            // Fetch the complete snapshot before changing any destination records.
            $teachers = $this->api->all('teachers');
            $groups = $this->api->request('groups')['data'];
            $students = $this->api->all('students');
            $summary = ['students'=>0,'teachers'=>0,'groups'=>0,'unassigned'=>0,'conflicts'=>0];
            $this->pdo->beginTransaction();
            $district = $students[0]['district'] ?? null;
            // Token is fixed to one district. Never place imported data in the demo organisation.
            $districtId = (int)($district['id'] ?? $teachers[0]['district_id'] ?? 0);
            if (!$districtId) throw new RuntimeException('ไม่พบอำเภอในข้อมูลต้นทาง');
            $org = $this->localId('district',(string)$districtId);
            if (!$org) {
                $this->execute('INSERT INTO organizations(organization_name,short_name) VALUES(?,?)',[$district['name'] ?? 'SDL_school', 'SDL']);
                $org=(int)$this->pdo->lastInsertId();
                $this->link('district',(string)$districtId,$org,[]);
            }
            if (!empty($district['name'])) {
                $this->execute('UPDATE organizations SET organization_name=? WHERE id=?', [$district['name'], $org]);
            }
            if (!$students && $this->execute("SELECT COUNT(*) FROM sdl_links WHERE entity='student'")->fetchColumn()) {
                throw new RuntimeException('ต้นทางส่งรายชื่อนักศึกษาเป็นศูนย์ ระบบยกเลิกการซิงก์เพื่อให้ตรวจสอบแหล่งข้อมูลก่อน');
            }
            $teacherIds=[];
            foreach ($teachers as $teacher) {
                if ((int)$teacher['district_id'] !== $districtId) throw new RuntimeException('ข้อมูลครูข้ามอำเภอ');
                $id=$this->localId('teacher',(string)$teacher['id']);
                if (!$id && $this->execute('SELECT id FROM users WHERE username=? OR (email IS NOT NULL AND email=?)',[$teacher['username'],$teacher['email'] ?? null])->fetchColumn()) {
                    throw new RuntimeException('ชื่อบัญชีครูซ้ำกับบัญชีใน SENA Care: '.$teacher['username'].' กรุณาเปลี่ยนชื่อบัญชีเดิมก่อน');
                }
                $name=preg_split('/\s+/u',trim($teacher['display_name'] ?? $teacher['name']),2);
                if (!$id) {
                    $this->execute("INSERT INTO users(username,password,firstname,lastname,role,organization_id) VALUES(?,?,?,?,'teacher',?)",[$teacher['username'],password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),$name[0] ?? '',$name[1] ?? '',$org]);
                    $id=(int)$this->pdo->lastInsertId();
                }
                $this->execute("UPDATE users SET username=?,email=?,firstname=?,lastname=?,organization_id=?,role='teacher',status=IF(status='locked','locked','active') WHERE id=?",[$teacher['username'],$teacher['email'] ?: null,$name[0] ?? '',$name[1] ?? '',$org,$id]);
                $this->link('teacher',(string)$teacher['id'],$id,$teacher);
                $teacherIds[(string)$teacher['id']]=$id;
                $summary['teachers']++;
            }
            // Disable linked teachers no longer present/active at the source.
            foreach ($this->execute("SELECT local_id,source_key FROM sdl_links WHERE entity='teacher'")->fetchAll() as $old) {
                if (!isset($teacherIds[$old['source_key']])) $this->execute("UPDATE users SET status='inactive' WHERE id=?",[$old['local_id']]);
            }
            $centers=[];
            foreach ($groups as $group) {
                $code=(string)$group['code'];
                $key=$districtId.':'.$code;
                $groupName = trim((string)$group['name']);
                if ($groupName === 'ศกร.ตำบลชายนา') {
                    $groupName = 'ศกร.ระดับตำบลชายนา';
                }
                $centerSuffix = (strlen($code) >= 6 && in_array(substr($code, 0, 2), ['21','22','23'])) ? substr($code, 2) : $code;

                $id = $this->localId('group', $key);
                if (!$id) {
                    $id = (int)$this->execute(
                        'SELECT id FROM learning_centers WHERE center_code = ? OR center_code = ? OR center_name = ? LIMIT 1',
                        [$centerSuffix, 'LC-' . $centerSuffix, $groupName]
                    )->fetchColumn();
                }
                if (!$id) {
                    $this->execute('INSERT INTO learning_centers(organization_id,center_code,center_name,status) VALUES(?,?,?,?)', [$org, $centerSuffix, $groupName, 'active']);
                    $id = (int)$this->pdo->lastInsertId();
                }
                $this->link('group', $key, $id, $group);
                $centers[$code] = $id;
                $summary['groups']++;
            }
            $sourceTerms = [];
            $seenStudents = [];
            foreach ($students as $student) {
                if ((int)$student['district']['id']!==$districtId) throw new RuntimeException('ข้อมูลนักศึกษาข้ามอำเภอ');
                $code=(string)$student['code'];
                if (isset($seenStudents[$code])) throw new RuntimeException('ต้นทางมีรหัสนักศึกษาซ้ำข้ามระดับ: '.$code);
                $seenStudents[$code] = true;
                $term=$student['current_term'];
                if (!preg_match('#^([12])/(25\d{2})$#',$term,$match)) throw new RuntimeException('ภาคเรียนต้นทางไม่ถูกต้อง: '.$term);
                $this->execute('INSERT INTO academic_terms(academic_year,term_no,term_name) VALUES(?,?,?) ON DUPLICATE KEY UPDATE term_name=VALUES(term_name)',[(int)$match[2],(int)$match[1],$term]);
                $termId=(int)$this->execute('SELECT id FROM academic_terms WHERE academic_year=? AND term_no=?',[(int)$match[2],(int)$match[1]])->fetchColumn();
                $sourceTerms[$termId] = (int)$match[2] * 10 + (int)$match[1];
                $id=$this->localId('student',$districtId.':'.$code);
                if (!$id) {
                    $existing=$this->execute('SELECT id FROM students WHERE student_code=?',[$code])->fetchColumn();
                    if ($existing) throw new RuntimeException('รหัสนักศึกษาซ้ำกับข้อมูลเดิม: '.$code.' กรุณาตรวจสอบก่อนเชื่อม');
                    $this->execute('INSERT INTO students(student_code,firstname,lastname) VALUES(?,?,?)',[$code,$student['name']['first_name'],$student['name']['last_name']]);
                    $id=(int)$this->pdo->lastInsertId();
                }
                $status=match($student['status']['code']) {'graduated'=>'graduated','dropped'=>'dropped','transferred'=>'transferred','inactive'=>'inactive',default=>'active'};
                $this->execute('UPDATE students SET prefix=?,firstname=?,lastname=?,student_status=? WHERE id=?',[$student['name']['prefix'],$student['name']['first_name'],$student['name']['last_name'],$status,$id]);
                $groupCode=(string)$student['group']['code']; $groupName=(string)$student['group']['name'];
                if ($groupName === 'ศกร.ตำบลชายนา') {
                    $groupName = 'ศกร.ระดับตำบลชายนา';
                }
                $center=$centers[$groupCode] ?? $this->localId('group',$districtId.':'.$groupCode);
                if (!$center && ($groupCode!=='' || $groupName!=='')) {
                    $centerSuffix = (strlen($groupCode) >= 6 && in_array(substr($groupCode, 0, 2), ['21','22','23'])) ? substr($groupCode, 2) : $groupCode;
                    $center = (int)$this->execute(
                        'SELECT id FROM learning_centers WHERE center_code = ? OR center_code = ? OR center_name = ? LIMIT 1',
                        [$centerSuffix, 'LC-' . $centerSuffix, $groupName]
                    )->fetchColumn();
                    if (!$center) {
                        $this->execute('INSERT INTO learning_centers(organization_id,center_code,center_name,status) VALUES(?,?,?,?)',[$org,$centerSuffix,$groupName ?: $groupCode,'active']);
                        $center=(int)$this->pdo->lastInsertId();
                    }
                    $centers[$groupCode]=$center;
                    $this->link('group',$districtId.':'.$groupCode,$center,['code'=>$groupCode,'name'=>$groupName]); $summary['groups']++;
                }
                $owners=[];
                foreach ($teachers as $teacher) {
                    $assigned=array_map('trim',$teacher['assigned_groups'] ?? []);
                    // Match SDL_school StudentAccessScope, including zero-padded ITW codes.
                    $unpad = static fn(string $value): string => ltrim($value, '0') === '' ? '0' : ltrim($value, '0');
                    $normalizedGroups = array_map($unpad, $assigned);
                    if (($groupCode!=='' && (in_array($groupCode,$assigned,true) || in_array($unpad($groupCode),$normalizedGroups,true))) || ($groupName!=='' && in_array($groupName,$assigned,true))) $owners[]=$teacherIds[(string)$teacher['id']];
                }
                $owners=array_values(array_unique($owners));
                if (count($owners)>1) $summary['conflicts']++;
                if (!$owners) $summary['unassigned']++;
                $level=match((int)$student['level']['id']) {1=>'primary',2=>'lower_secondary',3=>'upper_secondary',default=>throw new RuntimeException('ระดับการศึกษาไม่ถูกต้อง')};
                $enrollmentStatus=match($status){'graduated'=>'completed','dropped'=>'dropped','transferred'=>'transferred','inactive'=>'inactive',default=>'active'};
                $this->execute('INSERT INTO enrollments(student_id,academic_term_id,learning_center_id,teacher_id,education_level,group_name,enrollment_status) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE learning_center_id=VALUES(learning_center_id),teacher_id=VALUES(teacher_id),education_level=VALUES(education_level),group_name=VALUES(group_name),enrollment_status=VALUES(enrollment_status)',[$id,$termId,$center,count($owners)===1?$owners[0]:null,$level,mb_substr($groupName,0,50),$enrollmentStatus]);
                $enrollmentId=(int)$this->execute('SELECT id FROM enrollments WHERE student_id=? AND academic_term_id=?',[$id,$termId])->fetchColumn();
                $this->execute('DELETE FROM sdl_enrollment_teachers WHERE enrollment_id=?',[$enrollmentId]);
                foreach ($owners as $ownerId) $this->execute('INSERT INTO sdl_enrollment_teachers(enrollment_id,teacher_id) VALUES(?,?)',[$enrollmentId,$ownerId]);
                $this->link('student',$districtId.':'.$code,$id,$student); $summary['students']++;
            }
            foreach ($this->execute("SELECT source_key,local_id FROM sdl_links WHERE entity='student'")->fetchAll() as $old) {
                $oldCode = substr($old['source_key'], strpos($old['source_key'], ':') + 1);
                if (!isset($seenStudents[$oldCode])) {
                    $this->execute("UPDATE sdl_links SET source_present=0 WHERE entity='student' AND local_id=?",[$old['local_id']]);
                    $this->execute("UPDATE students SET student_status='inactive' WHERE id=? AND student_status='active'", [$old['local_id']]);
                    foreach (array_keys($sourceTerms) as $sourceTermId) {
                        $this->execute('DELETE st FROM sdl_enrollment_teachers st JOIN enrollments e ON e.id=st.enrollment_id WHERE e.student_id=? AND e.academic_term_id=?',[$old['local_id'],$sourceTermId]);
                        $this->execute("UPDATE enrollments SET enrollment_status='inactive',teacher_id=NULL WHERE student_id=? AND academic_term_id=? AND enrollment_status='active'", [$old['local_id'], $sourceTermId]);
                    }
                }
            }
            if ($sourceTerms) {
                arsort($sourceTerms);
                $currentTermId = array_key_first($sourceTerms);
                $this->execute('UPDATE academic_terms SET is_current = (id = ?)', [$currentTermId]);
            }
            $this->execute('INSERT INTO sdl_sync_runs(summary) VALUES(?)',[json_encode($summary,JSON_THROW_ON_ERROR)]);
            $this->pdo->commit();
            return $summary;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        } finally { $this->pdo->query("SELECT RELEASE_LOCK('sena_care_sdl_sync')"); }
    }
}
