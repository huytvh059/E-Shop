<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database as DB,Auth};
final class AuditService
{
    public static function record(string $action,string $entity,?int $id=null,array $details=[]):void
    {
        DB::execute('INSERT INTO audit_logs(staff_id,action,entity_type,entity_id,details,ip_address) VALUES (?,?,?,?,?,?)',[Auth::staff()['id']??null,$action,$entity,$id,json_encode($details,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$_SERVER['REMOTE_ADDR']??'cli']);
    }
}
