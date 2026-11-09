<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
if(PHP_SAPI!=='cli'||App\Core\Env::get('DB_NAME')!=='eshop_test'){fwrite(STDERR,"Chỉ import vào DB eshop_test qua CLI.\n");exit(1);}
$db=App\Core\Database::connection();
$db->exec(file_get_contents(BASE_PATH.'/database/schema.sql'));
$db->exec(file_get_contents(BASE_PATH.'/database/seed.sql'));
echo "Imported test schema and seed.\n";
