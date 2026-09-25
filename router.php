<?php
// Router for the optional PHP development server; Apache uses .htaccess.
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)?:'/');
if($path==='/' || $path==='/index.php'){require __DIR__.'/index.php';return true;}
if($path==='/api.php'){require __DIR__.'/api.php';return true;}
if(preg_match('#^/assets/[a-zA-Z0-9_.-]+\.(css|js|svg)$#',$path) || preg_match('#^/uploads/[a-f0-9]{36}\.(jpg|png|webp)$#',$path)){
    if(is_file(__DIR__.$path))return false;
}
http_response_code(404);echo 'Not found';return true;
