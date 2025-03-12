<?php
namespace rubi;

class socket{

public static $opcodes = ['continuation' => 0, 'text' => 1, 'binary' => 2, 'close'=> 8, 'ping' => 9, 'pong' => 10];
protected static $def_opt = [
'filter'=> ['text', 'binary'],
'timeout' => 5];
private $url, $stream;
private $opt = [];
protected $close = false;

public function __construct($u, $opt = []){
preg_match('#([A-z]+)://(.*):([0-9]+)/?(.*)?#', $u, $m);
$this->url = ['scheme' => ($m[1] == 'wss') ? 'ssl' : 'tcp', 'authority' => $m[2], 'port' => $m[3], 'path' => (empty($m[4])) ? '/' : '/'. $m[4]];
$this->opt = array_merge(self::$def_opt, $opt);
}

public function __destruct(){
if ($this->getType() === 'stream')
fclose($this->stream);
}

public function send($p, $c = 'text', bool $m = true){
if (!in_array($c, array_keys(self::$opcodes)) die("Bad opcode '{$opcode}'.Try 'text' or 'binary'.");
if (!$this->isConnected()) $this->connect();
$this->pushMessage($c, $p, $m);
}

public function receive(){
while ($this->isConnected() ? true : $this->connect() and $m = $this->pullMessage())
if (in_array($m[0][2], $this->opt['filter']) and $r = $m[0][1])
break;
else if ($m[0][2] == 'close' and $r = '')
break;
return $r;
}

protected function connect(){
if(!($this->stream = stream_socket_client($this->url['scheme'] .'://'. $this->url['authority'] .':'. $this->url['port'], $errno, $errstr, $this->opt['timeout'], STREAM_CLIENT_CONNECT, stream_context_create())))
die('No socket');
if (!$this->isConnected())
die("Invalid stream");
$this->setTimeout($this->opt['timeout']);
$h = [
'User-Agent'=> 'websocket-client-php',
'Connection'=> 'Upgrade',
'Upgrade' => 'websocket',
'Sec-WebSocket-Key' => $key = self::generateKey(),
'Sec-WebSocket-Version' => '13'];
if (isset($this->opt['origin']))
$h['origin'] = $this->opt['origin'];
if (isset($this->opt['headers']))
$h = array_merge($headers, $this->opt['headers']);
$this->write("GET ". $this->url['path'] ." HTTP/1.1\r\n" . implode("\r\n", array_map(function ($k, $v) {
return $k .': '. $v;
}, array_keys($h), $h)) . "\r\n\r\n");
$r = '';
do {
$r .= $this->gets(1024);
} while (substr_count($r, "\r\n\r\n") == 0);
if (!preg_match('#Sec-WebSocket-Accept:\s(.*)$#mUi', $r, $m)) 
die(sprintf("Connection' failed: Server sent invalid upgrade response: %s", $r));
if (trim($m[1]) !== base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))))
die('Server sent bad upgrade response.');
return true;
}

public function getFrames($c, $p, $m = true, $fs = 4096, $f = []){
foreach (str_split($p, $fs) as $i)
$f[] = [false, $i, 'continuation', $m];
$f[0][2] = $c;
$f[array_key_last($f)][0] = true;
return $f;
}

public function pushMessage($code, $pay, $masked = true){
$f = $this->getFrames($code, $pay, $masked);
foreach ($f as $frame)
$this->pushFrame($frame);
}

public function pullMessage(){
do {
list ($f, $p, $o, $m) = $this->autoRespond($this->pullFrame());
if ($o == 'close')
$this->close();
$pc = ($o == 'continuation') ? $rb['opcode'] : $o;
if (!$f and !($o == 'continuation') and $rb = ['opcode' => $o, 'payload' => $p, 'frames' => 1])
continue;
if ($o == 'continuation' and $rb['payload'] .= $p)
$rb['frames']++;
} while (!$f);
if ($o == 'continuation')
[$p, $f] = [$rb['payload'], $rb['frames']];
return $this->getFrames($pc, $p);
}

private function pullFrame(){
$data = $this->read(2);
list ($byte_1, $byte_2) = array_values(unpack('C*', $data));
$final = (bool)($byte_1 & 0b10000000);
$rsv = $byte_1 & 0b01110000;
$opcode_int= $byte_1 & 0b00001111;
$opcode_ints = array_flip(self::$opcodes);
if (!array_key_exists($opcode_int, $opcode_ints))
die("Bad opcode in websocket frame: {$opcode_int}");
$opcode = $opcode_ints[$opcode_int];
$masked = (bool)($byte_2 & 0b10000000);
$payload = '';
$payload_length = $byte_2 & 0b01111111;
if ($payload_length > 125) {
if ($payload_length === 126) {
$data = $this->read(2);
$payload_length = current(unpack('n', $data));
} else
[$data, $payload_length] = [$this->read(8), current(unpack('J', $data))];
}
if ($masked)
$masking_key = $this->read(4);
if ($payload_length > 0) {
$data = $this->read($payload_length);
if ($masked)
for ($i = 0; $i < $payload_length; $i++) 
$payload .= ($data[$i] ^ $masking_key[$i % 4]);
else
$payload = $data;
}
return [$final, $payload, $opcode, $masked];
}

private function pushFrame($frame){
list ($final, $payload, $opcode, $masked) = [$frame[0] , ($frame[1] ?? ''), $frame[2] , ($frame[3] ?? true)];
$data = '';
$byte_1 = $final ? 0b10000000 : 0b00000000;
$byte_1 |= self::$opcodes[$opcode];
$data .= pack('C', $byte_1);
$byte_2 = $masked ? 0b10000000 : 0b00000000;
if (strlen($payload) > 65535) {
$data .= pack('C', $byte_2 | 0b01111111);
$data .= pack('J', strlen($payload));
} elseif (strlen($payload) > 125) {
$data .= pack('C', $byte_2 | 0b01111110);
$data .= pack('n', strlen($payload));
} else
$data .= pack('C', $byte_2 | strlen($payload));
if ($masked) {
$mask = '';
for ($i = 0; $i < 4; $i++)
$mask .= chr(rand(0, 255));
$data .= $mask;
for ($i = 0; $i < strlen($payload); $i++)
$data .= $payload[$i] ^ $mask[$i % 4];
} else
$data .= $payload;
$this->write($data);
}

private function autoRespond($frame){
list ($f, $p, $c, $m) = $frame;
switch ($c) {
case 'ping':
$this->pushMessage('pong', $p, $m);
return [$f, $p, $c, $m];
case 'close':
if (strlen($p) > 0)
[$sb, $s] = [$p[0] . $p[1], current(unpack('n', $p)), $s];
if (strlen($p) >= 2)
$p = substr($p, 2);
if (!$this->close)
$this->pushMessage('close', ($sb ?? '') .'Close acknowledged: '. ($s ?? ''), $m);
else
$this->close = false;
$this->disconnect();
return [$f, $p, $c, $m];
default:
return [$f, $p, $c, $m];
}
}

public function disconnect(){
if ($this->isConnected()) return fclose($this->stream);
}

public function isConnected(): bool{
return in_array($this->getType(), ['stream']);
}

public function getType(){
return ($this->stream ?? false) ? get_resource_type($this->stream) : null;
}

public function setTimeout($s, $m = 0){
$this->opt['timeout'] = $s;
if (!$this->isConnected()) return;
return stream_set_timeout($this->stream, $s, $m);
}

public function gets($l){
if (!($g = fgets($this->stream, $l)))
die('Could not read from stream');
return $g;
}

public function read($l){
$d = '';
while (strlen($d) < $l)
if (!empty(stream_get_meta_data($this->stream)['timed_out']))
die('Client read timeout');
else if (!($r = fread($this->stream, $l - strlen($d))))
die("Broken frame");
else if ($r === '')
die("Empty read; connection dead?");
else $d .= $r;
return $d;
}

public function write($d){
if (!($w = fwrite($this->stream, $d))) die("Failed to write");
if ($w < strlen($d)) die("Could only write {$w} out of ". strlen($d) ." bytes.");
return $w;
}

public function close($s = 1000, $msg = 'ttfn', $ss = ''){
if (!$this->isConnected()) return;
foreach (str_split(sprintf('%016b', $s), 8) as $b)
$ss .= chr(bindec($b));
$this->pushMessage('close', $ss . $msg, true);
while ($this->close = true)
if ($this->pullMessage()[0][2] == 'close')
break;
}

protected static function generateKey($l = 16, $k = ''){
for ($i = 0; $i < $l; $i++)
$k .= chr(rand(33, 126));
return base64_encode($k);
}
}