<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('APP_ROOT',dirname(__DIR__));require APP_ROOT.'/app/config/database.php';
$p=Database::getConnection();
if(!$p->query("SHOW COLUMNS FROM sdl_links LIKE 'source_present'")->fetch()) $p->exec('ALTER TABLE sdl_links ADD source_present TINYINT(1) NOT NULL DEFAULT 1');
echo "Source membership schema ready. Run php scripts/sdl-sync.php to reconcile current roster.\n";
