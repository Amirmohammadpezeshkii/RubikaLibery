<?php
namespace rubi;

class connection{

private static $c = [
'app_version' => '3.8.1',
'lang_code' => 'en',
'package' => 'app.rbmain.a',
'platform' => 'Android'];
private $servers, $auth, $key, $d;

public function __construct($phone, $auth = null, $key = null){
$this->d = file_exists(encryption::secret($phone)) ? json_decode(encryption::openssl(false, file_get_contents(encryption::secret($phone)), encryption::secret($phone)), true) : [];
[$this->key, $this->auth] = [($this->d['key'] ?? $key), ($this->d['auth'] ?? $auth)];
$this->servers = (file_exists(__DIR__ .'/servers')) ? json_decode(file_get_contents(__DIR__ .'/servers'), true) : self::getDCs();
file_put_contents(__DIR__ .'/servers', json_encode($this->servers, 448));
if(empty($this->key) or empty($this->auth))
(new signin($phone));
}

public static function req($u, $d = []){
curl_setopt($ch = curl_init($u), CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'Connection: keep-alive',
((count($d) > 0) ? 'Content-Type: application/json' : '')]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($d));
$result = curl_exec($ch);
curl_close($ch);
return json_decode($result, true);
}

public function run($m, $i = [], $t = false){
echo 'use method '. $m . PHP_EOL;
$d = [
'api_version' => '6',
(($t) ? 'tmp_session' : 'auth') => ((!$t) ? encryption::setAuth($this->auth) : $this->auth),
'data_enc' => ($s = encryption::openssl(true, json_encode([
'method' => $m,
'input' => $i,
'client' => self::$c], 448), encryption::secret($this->auth)))];
if(!$t) $d['sign'] = encryption::sign($s, $this->key[1]);
foreach (($this->servers['API'] ?? []) as $url)
if (isset(($r = $this->req($url, $d))['data_enc']))
return json_decode(encryption::openssl(false, $r['data_enc'], encryption::secret($this->auth)), true);
return $r;
}

public function getDCs($t = 10){
while($t--)
if (($DCs = self::req('https://getdcmess.iranlms.ir'))['status_det'] ?? '' == 'OK')
return $DCs['data'];
else
sleep(mt_rand(3, 6));
}

public function onUpdate(callable $callback){
self::run('getChats');
while (true)
foreach (($this->servers['socket'] ?? []) as $socket) {
try{
$client = new \WebSocket\Client($socket, ['timeout' => 60]);
$client->text(json_encode([
'api_version' => '6',
'auth' => $this->auth,
'data' => json_encode(['version' => 2]),
'method' => 'handShake',
'client' => self::$c]));
while (true) {
if(($time ?? 0) <= time() and $time = time() +3)
$client->text('{}');
$message = json_decode($client->receive(), true);
$callback((isset($message['data_enc'])) ? json_decode(encryption::openssl(false, $message['data_enc'], encryption::secret($this->auth)), true) : $message);
}
}catch(Throwable $e){
sleep(5);
continue;
}
}
$client->close();
}

}
