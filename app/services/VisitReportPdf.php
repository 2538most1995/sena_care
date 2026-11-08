<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');
require_once APP_ROOT.'/app/models/AssessmentQuestion.php';
require_once APP_ROOT.'/app/services/ReportSettings.php';

/** Shared A4 document and individual visit layout for print and teacher exports. */
class VisitReportPdf
{
    public static function document(string $title): \Mpdf\Mpdf
    {
        require_once APP_ROOT.'/vendor/autoload.php';
        $cache=APP_ROOT.'/storage/pdf-cache';
        if (!is_dir($cache) && !mkdir($cache,0700,true) && !is_dir($cache)) throw new RuntimeException('ไม่สามารถสร้างรายงาน PDF ได้');
        $defaults=(new \Mpdf\Config\FontVariables())->getDefaults();
        $pdf=new \Mpdf\Mpdf([
            'mode'=>'utf-8','format'=>'A4','margin_left'=>18,'margin_right'=>18,
            'margin_top'=>21,'margin_bottom'=>18,'margin_header'=>9,'margin_footer'=>9,
            'tempDir'=>$cache,'fontDir'=>[APP_ROOT.'/resources/fonts'],
            'fontdata'=>$defaults['fontdata']+['thsarabun'=>['R'=>'THSarabunNew.ttf','B'=>'THSarabunNew Bold.ttf','I'=>'THSarabunNew Italic.ttf','BI'=>'THSarabunNew BoldItalic.ttf','useOTL'=>0]],
            'default_font'=>'thsarabun','default_font_size'=>16,
            'shrink_tables_to_fit'=>1,
        ]);
        $pdf->SetTitle($title);
        $pdf->SetAuthor('SENA Care');
        $pdf->SetHTMLHeader('<div style="font-family:thsarabun;text-align:right;font-size:12pt;color:#657b94">SENA CARE &nbsp; | &nbsp; รายงานการติดตามเยี่ยมบ้านผู้เรียน</div>');
        $pdf->SetHTMLFooter('<table style="width:100%;border-top:0.2mm solid #d6dfe7;font-family:thsarabun;font-size:12pt;color:#657b94"><tr><td style="border:0;padding-top:2mm">เอกสารเพื่อการติดตามดูแลผู้เรียน</td><td style="border:0;text-align:right;padding-top:2mm">หน้า {PAGENO} / {nbpg}</td></tr></table>');
        return $pdf;
    }

    public static function html(array $v, ?string $director = null, ?array $signatures = null): string
    {
        $signatures ??= (new ReportSettings())->load();
        if ($director !== null) $signatures['director_name'] = $director;
        $sections=['family'=>[],'learning'=>[],'needs'=>[],'recommendation'=>[],'other'=>[]];
        $labels=[
            'ลักษณะที่อยู่อาศัย'=>'ที่อยู่อาศัย','สภาพที่อยู่อาศัยโดยรวม'=>'สภาพที่พัก',
            'จำนวนสมาชิกในครอบครัว (คน)'=>'สมาชิกครอบครัว (คน)','ผู้เรียนอาศัยอยู่กับใคร'=>'อาศัยอยู่กับ',
            'รายได้ครอบครัวต่อเดือน (โดยประมาณ)'=>'รายได้ครอบครัว','อาชีพหลักของครอบครัว'=>'อาชีพครอบครัว',
            'ระยะทางจากบ้านถึง ศกร. (โดยประมาณ)'=>'ระยะทางถึง ศกร.','พาหนะในการเดินทาง'=>'พาหนะเดินทาง',
            'ผู้เรียนทำงานหรือไม่'=>'สถานะการทำงาน','ผู้เรียนมีโทรศัพท์มือถือหรือไม่'=>'โทรศัพท์มือถือ',
            'ผู้เรียนมีคอมพิวเตอร์/แท็บเล็ตหรือไม่'=>'คอมพิวเตอร์ / แท็บเล็ต','มีอินเทอร์เน็ตใช้งานที่บ้านหรือไม่'=>'อินเทอร์เน็ตที่บ้าน',
            'ปัญหาด้านการเรียน'=>'ปัญหาการเรียน','ปัญหาด้านเศรษฐกิจ'=>'ปัญหาเศรษฐกิจ',
            'ปัญหาด้านครอบครัว'=>'ปัญหาครอบครัว','ผู้เรียนมีความเสี่ยงหลุดจากระบบการศึกษาหรือไม่'=>'ความเสี่ยงหลุดจากระบบ',
        ];
        $positions=[];
        foreach ($v['answers'] as $a) {
            $section=match($a['section_name']) {
                'สภาพที่อยู่อาศัย','ครอบครัว','เศรษฐกิจ','การเดินทาง','การทำงาน'=>'family',
                'อุปกรณ์การเรียน'=>'learning','ปัญหา','ความต้องการ'=>'needs','ข้อเสนอแนะ'=>'recommendation',default=>'other'
            };
            $value=(string)$a['answer_value'];
            if ($a['question_type']==='checkbox') {
                $decoded=json_decode($value,true);
                if (is_array($decoded)) $value=implode(' • ',array_map('strval',$decoded));
            }
            if ($a['question_type']==='yes_no') $value=match($value){'ใช่'=>'มี','ไม่ใช่'=>'ไม่มี',default=>$value};
            $definition=AssessmentQuestion::signature($a);
            if (!empty($a['historical_variant']) && isset($positions[$definition])) {
                [$originalSection,$index]=$positions[$definition];
                $sections[$originalSection][$index][1].="\nคำตอบเดิมอีกชุด: ".$value;
                $sections[$originalSection][$index][2]=true;
            } else {
                $positions[$definition]=[$section,count($sections[$section])];
                $sections[$section][]=[$labels[$a['question_text']]??$a['question_text'],$value,false];
            }
        }
        ob_start();
        include APP_ROOT.'/app/views/reports/visit-pdf.php';
        return ob_get_clean();
    }

    public function pdf(array $visit, ?array $signatures = null): string
    {
        $pdf=self::document('รายงานผลการเยี่ยมบ้านผู้เรียน - '.$visit['firstname'].' '.$visit['lastname']);
        foreach(explode('<!--REPORT_CHUNK-->',self::html($visit, null, $signatures)) as $chunk) $pdf->WriteHTML($chunk);
        return $pdf->Output('',\Mpdf\Output\Destination::STRING_RETURN);
    }
}
