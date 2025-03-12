<?php
namespace rubi;

class client{

public static $opcodes = ['continuation' => 0, 'text' => 1, 'binary' => 2, 'close'=> 8, 'ping' => 9, 'pong' => 10];
protected static $default_options = [
'context' => null,
'filter'=> ['text', 'binary'],
'fragment_size' => 4096,
'headers' => null,
'origin'=> null,
'persistent'=> false,
'return_obj'=> false,
'timeout' => 5];
private $url, $stream, $read_buffer;
private $options = [];
protected $is_closing = false;
protected $close_status = null;

public function __construct($uri, $options = []){
preg_match('#([A-z]+)://(.*):([0-9]+)/?(.*)?#', $uri, $m);
$this->url = ['scheme' => ($m[1] == 'wss') ? 'ssl' : 'tcp', 'authority' => $m[2], 'port' => $m[3], 'path' => (empty($m[4])) ? '/' : '/'. $m[4]];
$this->options = array_merge(self::$default_options, $options);
}

public function __destruct(){
if ($this->getType() === 'stream')
fclose($this->stream);
}

public function send($payload, $opcode = 'text', bool $masked = true): void{
if (!$this->isConnected()) $this->connect();
if (!in_array($opcode, ['continuation', 'text', 'binary', 'close', 'ping', 'pong']))
die("Bad opcode '{$opcode}'.Try 'text' or 'binary'.");
$this->pushMessage($opcode, $payload, $masked);
}

public function receive(){
if (!$this->isConnected())
$this->connect();
while (true) {
$message = $this->pullMessage();
if (in_array($message[0][2], $this->options['filter'])) {
[$return] = [$this->options['return_obj'] ? $message : $message[0][1]];
break;
} elseif ($message[0][2] == 'close') {
[$return] = [$this->options['return_obj'] ? $message : null];
break;
}
}
return $return;
}

protected function connect(): void{
if (isset($this->options['context'])) {
if (@get_resource_type($this->options['context']) === 'stream-context')
$context = $this->options['context'];
else
die("Stream context in \$options['context'] isn't a valid context.");
} else
$context = stream_context_create();
$persistent = $this->options['persistent'] === true;
$flags = STREAM_CLIENT_CONNECT;
$flags = $persistent ? $flags | STREAM_CLIENT_PERSISTENT : $flags;
try {
$this->stream = stream_socket_client($this->url['scheme'] .'://'. $this->url['authority'] .':'. $this->url['port'], $errno, $errstr, $this->options['timeout'], $flags, $context);
if (!$this->stream)
die('No socket');
} catch (ErrorException $e) {
die("Could not open socket to \"{$host_uri->getAuthority()}\": {$e->getMessage()} ({$e->getCode()}).");
}
if (!$this->isConnected())
die("Invalid stream");
if (!$persistent or $this->tell() == 0) {
$this->setTimeout($this->options['timeout']);
$key = self::generateKey();
$headers = [
'User-Agent'=> 'websocket-client-php',
'Connection'=> 'Upgrade',
'Upgrade' => 'websocket',
'Sec-WebSocket-Key' => $key,
'Sec-WebSocket-Version' => '13'];
if (isset($this->options['origin']))
$headers['origin'] = $this->options['origin'];
if (isset($this->options['headers']))
$headers = array_merge($headers, $this->options['headers']);
$header = "GET ". $this->url['path'] ." HTTP/1.1\r\n" . implode("\r\n", array_map(function ($key, $value) {
return "$key: $value";
}, array_keys($headers), $headers)) . "\r\n\r\n";
$this->write($header);
$response = '';
try {
do {
$response .= $this->gets(1024);
} while (substr_count($response, "\r\n\r\n") == 0);
} catch (Exception $e) {
die('Client handshake error', $e->getCode(), $e->getData(), $e);
}
if (!preg_match('#Sec-WebSocket-Accept:\s(.*)$#mUi', $response, $matches)) 
die(sprintf("Connection' failed: Server sent invalid upgrade response: %s", $response));
if (trim($matches[1]) !== base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))))
die('Server sent bad upgrade response.');
}
}

public function getFrames($optcode, $payload, $masked = true, $framesize = 4096, $frames = []){
$split = str_split($payload, $framesize) ?: [''];
foreach ($split as $pay)
$frames[] = [false, $pay, 'continuation', $masked];
$frames[0][2] = $optcode;
$frames[array_key_last($frames)][0] = true;
return $frames;
}

public function pushMessage($optcode, $payload, $masked = true){
$frames = $this->getFrames($optcode, $payload, $masked, $this->options['fragment_size']);
foreach ($frames as $frame)
$this->pushFrame($frame);
}

public function pullMessage(){
do {
list ($final, $payload, $opcode, $masked) = $this->autoRespond($this->pullFrame());
if ($opcode == 'close')
$this->close();
$payload_opcode = ($opcode == 'continuation') ? $this->read_buffer['opcode'] : $opcode;
if (!$final and !($opcode == 'continuation')) {
$this->read_buffer = ['opcode' => $opcode, 'payload' => $payload, 'frames' => 1];
continue;
}
if ($opcode == 'continuation') {
$this->read_buffer['payload'] .= $payload;
$this->read_buffer['frames']++;
}
} while (!$final);
$frames = 1;
if ($opcode == 'continuation')
[$payload, $frames, $this->read_buffer] = [$this->read_buffer['payload'], $this->read_buffer['frames'], null];
return $this->getFrames($payload_opcode, $payload);
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
list ($final, $payload, $opcode, $masked) = $frame;
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
list ($final, $payload, $opcode, $masked) = $frame;
switch ($opcode) {
case 'ping':
$this->pushMessage('pong', $payload, $masked);
return [$final, $payload, $opcode, $masked];
case 'close':
$status_bin = '';
$status = '';
if (strlen($payload) > 0)
[$status_bin, $status, $this->close_status] = [$payload[0] . $payload[1], current(unpack('n', $payload)), $status];
if (strlen($payload) >= 2)
$payload = substr($payload, 2);
if (!$this->is_closing)
$this->pushMessage('close', "{$status_bin}Close acknowledged: {$status}", $masked);
else
$this->is_closing = false;
$this->disconnect();
return [$final, $payload, $opcode, $masked];
default:
return [$final, $payload, $opcode, $masked];
}
}

public function disconnect(){
if ($this->isConnected()) return fclose($this->stream);
}

public function isConnected(): bool{
return in_array($this->getType(), ['stream', 'persistent stream']);
}

public function getType(){
return ($this->stream ?? false) ? get_resource_type($this->stream) : null;
}

public function tell(){
if (!($t = ftell($this->stream)))
die('Could not resolve stream pointer position');
return $t;
}

public function setTimeout($s, $m = 0){
$this->options['timeout'] = $s;
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
else
$d .= $r;
return $d;
}

public function write($d){
if (!($w = fwrite($this->stream, $d))) die("Failed to write");
if ($w < strlen($d)) die("Could only write {$w} out of ". strlen($d) ." bytes.");
return $w;
}

public function getCloseStatus(){
return $this->close_status;
}

public function close($status = 1000, $message = 'ttfn'){
if (!$this->isConnected()) return;
$status_str = '';
foreach (str_split(sprintf('%016b', $status), 8) as $binstr)
$status_str .= chr(bindec($binstr));
$this->pushMessage('close', $status_str . $message, true);
$this->is_closing = true;
while (true)
if ($this->pullMessage()->getOpcode() == 'close')
break;
}

protected static function generateKey($l = 16, $k = ''){
for ($i = 0; $i < $l; $i++)
$k .= chr(rand(33, 126));
return base64_encode($k);
}
}