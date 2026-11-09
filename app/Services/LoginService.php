<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database as DB;
use App\Core\Auth;
use App\Core\Validator;
final class LoginService
{
    public static function attempt(array $input,bool $staff=false): void
    {
        $email=Validator::email($input['email']??''); $password=Validator::text($input['password']??'',1,200);
        $key=hash('sha256',($staff?'staff':'customer').'|'.$email.'|'.($_SERVER['REMOTE_ADDR']??'cli'));
        // Khóa bộ đếm để các lần đăng nhập đồng thời không vượt giới hạn.
        $result=DB::transaction(static function () use($key,$email,$password,$staff): array {
            DB::execute('INSERT IGNORE INTO login_attempts(identity_hash,window_started_at) VALUES (?,NOW())',[$key]);
            $attempt=DB::execute('SELECT * FROM login_attempts WHERE identity_hash=? FOR UPDATE',[$key])->fetch();
            if ($attempt['blocked_until'] && strtotime($attempt['blocked_until'])>time()) {return ['error'=>'Đăng nhập sai quá nhiều lần. Thử lại sau 15 phút.'];}
            if (strtotime($attempt['window_started_at'])<time()-900) {DB::execute('UPDATE login_attempts SET failures=0,window_started_at=NOW(),blocked_until=NULL WHERE identity_hash=?',[$key]);$attempt['failures']=0;}
            $user=DB::execute($staff?'SELECT * FROM staff WHERE email=?':'SELECT * FROM users WHERE email=?',[$email])->fetch();
            $dummy='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
            $valid=password_verify($password,$user['password_hash']??$dummy);
            if (!$user || !$valid || !$user['is_active']) {
                $failures=(int)$attempt['failures']+1;
                DB::execute('UPDATE login_attempts SET failures=?,blocked_until=? WHERE identity_hash=?',[$failures,$failures>=5?date('Y-m-d H:i:s',time()+900):null,$key]);
                return ['error'=>'Email hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.'];
            }
            DB::execute('DELETE FROM login_attempts WHERE identity_hash=?',[$key]);
            return ['id'=>(int)$user['id']];
        });
        if (isset($result['error'])) {throw new \DomainException($result['error']);}
        Auth::login($result['id'],$staff);
    }
}
