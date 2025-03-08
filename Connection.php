<?php
namespace rubi;

class Connection{

public static $opcodes = [
'continuation' => 0,
'text' => 1,
'binary' => 2,
'close'=> 8,
'ping' => 9,
'pong' => 10];
private $stream;
private $read_buffer;
private $options = [];
protected $is_closing = false;
protected $close_status = null;

public function __construct($stream, array $options = []){
$this->stream = $stream;
$this->setOptions($options);
}

public function __destruct(){
if ($this->getType() === 'stream')
fclose($this->stream);
}

public function setOptions($options = []){
$this->options = array_merge($this->options, $options);
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
$continuation = $opcode == 'continuation';
$payload_opcode = $continuation ? $this->read_buffer['opcode'] : $opcode;
if (!$final and !$continuation) {
$this->read_buffer = ['opcode' => $opcode, 'payload' => $payload, 'frames' => 1];
continue;
}
if ($continuation) {
$this->read_buffer['payload'] .= $payload;
$this->read_buffer['frames']++;
}
} while (!$final);
$frames = 1;
if ($continuation)
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
if (!$this->is_closing) {
$this->pushMessage('close', "{$status_bin}Close acknowledged: {$status}", $masked);
} else
$this->is_closing = false;
$this->disconnect();
return [$final, $payload, $opcode, $masked];
default:
return [$final, $payload, $opcode, $masked];
}
}

public function disconnect(){
return fclose($this->stream);
}

public function isConnected(): bool{
return in_array($this->getType(), ['stream', 'persistent stream']);
}

public function getType(){
return get_resource_type($this->stream);
}

public function getName(){
return stream_socket_get_name($this->stream, false);
}

public function getRemoteName(){
return stream_socket_get_name($this->stream, true);
}

public function getMeta(){
return stream_get_meta_data($this->stream);
}

public function tell(){
$tell = ftell($this->stream);
if ($tell === false)
die('Could not resolve stream pointer position');
return $tell;
}

public function eof(){
return feof($this->stream);
}

public function setTimeout($seconds, $microseconds = 0): bool{
return stream_set_timeout($this->stream, $seconds, $microseconds);
}

public function getLine($length, $ending): string{
$line = stream_get_line($this->stream, $length, $ending);
if ($line === false)
die('Could not read from stream');
$read = strlen($line);
return $line;
}

public function gets($length){
$line = fgets($this->stream, $length);
if ($line === false)
die('Could not read from stream');
$read = strlen($line);
return $line;
}

public function read($length){
$data = '';
while (strlen($data) < $length) {
$buffer = fread($this->stream, $length - strlen($data));
if (!$buffer) {
$meta = stream_get_meta_data($this->stream);
if (!empty($meta['timed_out']))
die('Client read timeout');
}
if ($buffer === false) 
die("Broken frame, read {$read} of stated {". strlen($data) . "} bytes.");
if ($buffer === '')
die("Empty read; connection dead?");
$data .= $buffer;
$read = strlen($data);
}
return $data;
}

public function write($data){
$length = strlen($data);
$written = fwrite($this->stream, $data);
if ($written === false)
die("Failed to write {$length} bytes.");
if ($written < strlen($data))
die("Could only write {$written} out of {$length} bytes.");
}

}
