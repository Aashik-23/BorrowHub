<?php
// Issue a one-time reset token after verifying the account owner's identity.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/lib.php';
$email=$argv[1]??'';$u=sql('SELECT id FROM users WHERE email=?',[$email])->fetch();
if(!$u){fwrite(STDERR,"Account not found. Usage: php reset-password.php member@example.com\n");exit(1);}
$token=bin2hex(random_bytes(32));begin();
sql('UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL',[$u['id']]);
sql('INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES (?,?,?)',[$u['id'],hash('sha256',$token),date('Y-m-d H:i:s',time()+1800)]);commit();
echo "Reset token (valid for 30 minutes, one use):\n".$token."\nOpen BorrowHub → Sign in → Forgot password → enter this token.\n";
