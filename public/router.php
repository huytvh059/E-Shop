<?php
declare(strict_types=1);
// Chỉ dùng PHP development server, không dùng thay Nginx khi production.
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(preg_match('~^/(assets/|uploads/)[a-zA-Z0-9_./-]+$~D',$path)&&!str_contains($path,'..')&&!preg_match('/\.(php|phtml|phar)(\.|\/|$)/i',$path)&&is_file(__DIR__.$path)){return false;}
require __DIR__.'/index.php';
