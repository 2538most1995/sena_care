<?php
if (!defined('APP_ROOT')) die();
class TeacherAssignment
{
    // Use EXISTS rather than a join, so joint teachers never duplicate visit rows.
    public static function nameSql(): string
    {
        return "(SELECT GROUP_CONCAT(DISTINCT CONCAT(COALESCE(gt.prefix,''),gt.firstname,' ',gt.lastname) ORDER BY gt.id SEPARATOR ', ') FROM users gt WHERE gt.id=e.teacher_id OR EXISTS(SELECT 1 FROM sdl_enrollment_teachers jt WHERE jt.enrollment_id=e.id AND jt.teacher_id=gt.id))";
    }
}
