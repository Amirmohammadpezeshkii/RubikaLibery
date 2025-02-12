<?php
namespace AmirMohamadPezeshki\Rubika;


class signin extends connection{
public $d;

public function __construct($phone){
$this->d = file_exists(encryption::secret($phone)) ? json_decode(encryption::openssl(false, file_get_contents(encryption::secret($phone)), encryption::secret($phone)), true) : [];
$this->d['auth'] ??= encryption::hash();
$this->d['key'] ??= encryption::crKeys();
parent::__construct($phone, $this->d['auth'], $this->d['key']);
if (($this->run('getMySessions')['status_det'] ?? '') == 'NOT_REGISTERED'){
$code = $this->sendCode($phone);
if(isset($code['data']['phone_code_hash'])){
$sign = $this->signIn($phone, $code['data']['phone_code_hash'], readLine('enter code: '), $this->d['key'][0]);
echo json_encode($sign, 448);
if(isset($sign['data']['auth'])){
$this->d['self']['guide'] = $sign['data']['user']['user_guid'];
openssl_private_decrypt(base64_decode($sign['data']['auth']), $this->d['auth'], openssl_pkey_get_private($this->d['key'][1]), OPENSSL_PKCS1_OAEP_PADDING);
parent::__construct($phone, $this->d['auth'], $this->d['key']);
if(($reg = $this->registerDevice())['status_det'] ?? '' == 'OK'){
echo 'logined'. PHP_EOL;
file_put_contents(encryption::secret($phone), encryption::openssl(true, json_encode($this->d, 448), encryption::secret($phone)));
}else
die( json_encode($reg += ['type' => 'registerDevice']) );
}else
die( json_encode($sign += ['type' => 'sginIn']) );
}else 
die( json_encode($code += ['type' => 'sendCode']) );
}
}

public function sendCode($phone_number, $send_type = 'SMS'){
return $this->run('sendCode', compact('phone_number', 'send_type'), true);
}

public function registerDevice($token_type = 'Firebase', $token = '', $app_version = 'MA_3.3.2', $lang_code = 'fa', $system_version = 'Android', $device_model = 'Android 14', $device_hash = '45010078020100101780', $is_multi_account = false){
return $this->run('registerDevice', compact('token_type', 'token', 'app_version', 'lang_code', 'system_version', 'device_model', 'device_hash', 'is_multi_account'));
}

public function signIn($phone_number, $phone_code_hash, $phone_code, $public_key){
return $this->run('signIn', compact('phone_number', 'phone_code_hash', 'phone_code', 'public_key'), true);
}

}

?>