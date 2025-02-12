<?php
namespace AmirMohamadPezeshki\Rubika;

class encryption{

public static function secret($a){
$n = substr($a, 16, 8) . substr($a, 0, 8) . substr($a, 24, 8) . substr($a, 8, 8);
for ($s = 0; $s < strlen($n); $s++)
$n[$s] = ctype_digit($n[$s]) ? chr((ord($n[$s]) - ord('0') + 5) % 10 + ord('0')) : chr((ord($n[$s]) - ord('a') + 9) % 26 + ord('a'));
return $n;
}

public static function sign($d, $k){
openssl_sign($d, $s, openssl_pkey_get_private($k), OPENSSL_ALGO_SHA256);
return base64_encode($s);
}

public static function setAuth($a){
return preg_replace_callback('/[a-zA-Z0-9]/', function ($m) {
if (ctype_lower($m[0]))
return chr(((32 - (ord($m[0]) - 97)) % 26) + 97);
else if (ctype_upper($m[0]))
return chr(((29 - (ord($m[0]) - 65)) % 26) + 65);
else if (ctype_digit($m[0]))
return chr(((13 - (ord($m[0]) - 48)) % 10) + 48);
return $m[0];
}, $a);
}

public static function crKeys(){
$keyGenerator = openssl_pkey_new([
'private_key_bits' => 1024,
'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($keyGenerator, $privateKey);
$publicKey = base64_encode(openssl_pkey_get_details($keyGenerator)['key']);
return array(chunk_split(self::setAuth($publicKey), 64, PHP_EOL), $privateKey);
}

public static function hash($l = 32, $r = ''){
while($l--)
$r .= ($range = range('a', 'z'))[array_rand($range)];
return $r;
}

public static function openssl($i, $d, $k = null){
if ($i)
return base64_encode(openssl_encrypt($d, 'aes-256-cbc', $k, OPENSSL_RAW_DATA, str_repeat(chr(0x0), 16)));
else
return openssl_decrypt(base64_decode($d), 'aes-256-cbc', $k, OPENSSL_RAW_DATA);
}
}