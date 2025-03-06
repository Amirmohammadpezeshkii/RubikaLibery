<?php
namespace rubi;

class connection{

private static $c = [
'app_version' => '3.8.1',
'lang_code' => 'en',
'package' => 'app.rbmain.a',
'platform' => 'Android'];
public $servers, $auth, $key, $d;

public function __construct($phone, $auth = null, $key = null){
getDCs:
$this->servers = (file_exists(__DIR__ .'/servers')) ? json_decode(file_get_contents(__DIR__ .'/servers'), true) : self::getDCs();
file_put_contents(__DIR__ .'/servers', json_encode($this->servers, 448));
if((filectime(__DIR__ .'/servers') + (6 * 60 * 60)) < time()){
unlink(__DIR__ .'/servers');
goto getDCs;
}
if(empty($auth) or empty($key))
(new signin($phone));
$this->d = file_exists(encryption::secret($phone)) ? json_decode(encryption::openssl(false, file_get_contents(encryption::secret($phone)), encryption::secret($phone)), true) : [];
[$this->key, $this->auth] = [($key ?? $this->d['key'] ?? null), ($auth ?? $this->d['auth'] ?? null)];
echo json_encode(['auth' => $this->auth, 'key' => $this->key], 448);
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
if (isset(($r = self::req($url, $d))['data_enc']))
return json_decode(encryption::openssl(false, $r['data_enc'], encryption::secret($this->auth)), true);
return json_decode($r, true);
}

public function downloadFile($dc_id, $access_hash_rec, $file_id, $mime, $chunk_size = 500 * 1024, $return = '', $start_index = 0){
echo 'use method downloadFile' . PHP_EOL;
while(empty($m[1]) or $start_index <= $m[1]){
curl_setopt($ch = curl_init($u = $this->servers['storage'][$dc_id] .'/GetFile.ashx'), CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'auth: '. encryption::setAuth($this->auth),
'access-hash-rec: '. $access_hash_rec,
'dc-id: '. $dc_id,
'file-id: '. $file_id,
'start-index: '. $start_index,
'last-index: '. ($start_index + $chunk_size - 1)]);
$result = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$return .= substr($result, $header_size);
curl_close($ch);
preg_match('/total_length: (\d+)/', substr($result, 0, $header_size), $m);
if(empty($m[1]))
return 'error';
$start_index += $chunk_size;
}
file_put_contents(($path = encryption::hash() .'.'. $mime), $return);
return $path;
}

private function requestSendFile($file_name, $size, $mime){
return $this->run('requestSendFile', compact('file_name', 'size', 'mime'));
}

public function sendFileToAPI($path, $chunk_size = 500 * 1024){
echo 'use method sendFileToAPI' . PHP_EOL;
$sendFile = $this->requestSendFile(basename($path), filesize($path), pathinfo($path)['extension']);
$file = fopen($path, 'rb');
for ($part = 1; $part <= ceil(filesize($path) / $chunk_size); $part++) {
$data = fread($file, $chunk_size);
curl_setopt($ch = curl_init($sendFile['data']['upload_url']), CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'auth: '. ($this->auth),
'access-hash-send: '. $sendFile['data']['access_hash_send'],
'file-id: '. $sendFile['data']['id'],
'chunk-size: '. strlen($data),
'part-number: '. $part,
'total-part: '. ceil(filesize($path) / $chunk_size)]);
$res = curl_exec($ch);
curl_close($ch);
if ($part == ceil(filesize($path) / $chunk_size)) {
fclose($file);
$res = json_decode($res, true);
$res['data'] += ['file_id' => $sendFile['data']['id'], 'dc_id' => $sendFile['data']['dc_id'], 'path' => $path, 'mime' => pathinfo($path)['extension'], 'file_name' => basename($path), 'size' => filesize($path)];
return $res;
}
}
}

public static function getDCs($t = 10){
echo 'use method getDCs' . PHP_EOL;
while($t--)
if (($DCs = self::req('https://getdcmess.iranlms.ir'))['status_det'] ?? '' == 'OK')
return $DCs['data'];
else
sleep(mt_rand(3, 6));
}

public function onUpdate(callable $callback){
echo 'use method onUpdate' . PHP_EOL;
self::run('getChats');
while (true)
foreach (($this->servers['socket'] ?? []) as $socket)
try{
($client = new \WebSocket\Client($socket, ['timeout' => 60]))->text(json_encode([
'api_version' => '6',
'auth' => $this->auth,
'data' => json_encode(['version' => 2]),
'method' => 'handShake',
'client' => self::$c]));
echo 'connected '. $socket . PHP_EOL;
while (true) {
if(($time ?? 0) <= time() and $time = time() +3)
$client->text('{}');
$message = json_decode($client->receive(), true);
$callback((isset($message['data_enc'])) ? json_decode(encryption::openssl(false, $message['data_enc'], encryption::secret($this->auth)), true) : $message ?? []);
}
}catch(Throwable $e){
sleep(5);
continue;
}
$client->close();
}


}
