<?php
namespace rubi;

class Client{

private static $opcodes = [
'continuation' => 0,
'text' => 1,
'binary' => 2,
'close'=> 8,
'ping' => 9,
'pong' => 10];
protected static $default_options = [
'context' => null,
'filter'=> ['text', 'binary'],
'fragment_size' => 4096,
'headers' => null,
'logger'=> null,
'origin'=> null, // @deprecated
'persistent'=> false,
'return_obj'=> false,
'timeout' => 5];
private $socket_uri;
private $connection;
private $options = [];
private $last_opcode = null;

public function __construct($uri, $options = []){
preg_match('#([A-z]+)://(.*):([0-9]+)/?(.*)?#', $uri, $m);
$this->socket_uri = ['scheme' => ($m[1] == 'wss') ? 'ssl' : 'tcp', 'authority' => $m[2], 'port' => $m[3], 'path' => (empty($m[4])) ? '/' : '/'. $m[4]];
$this->options = array_merge(self::$default_options,$options);
}

public function setTimeout($timeout){
$this->options['timeout'] = $timeout;
if (!$this->isConnected()) return;
$this->connection->setTimeout($timeout);
$this->connection->setOptions($this->options);
}

public function setFragmentSize($fragment_size){
$this->options['fragment_size'] = $fragment_size;
$this->connection->setOptions($this->options);
return $this;
}

public function getFragmentSize(){
return $this->options['fragment_size'];
}

public function text($payload){
$this->send($payload);
}

public function binary($payload){
$this->send($payload, 'binary');
}

public function ping($payload = ''){
$this->send($payload, 'ping');
}

public function pong($payload = ''){
$this->send($payload, 'pong');
}

public function send($payload, $opcode = 'text', bool $masked = true): void{
if (!$this->isConnected())
$this->connect();
if (!in_array($opcode, array_keys(self::$opcodes)))
die("Bad opcode '{$opcode}'.Try 'text' or 'binary'.");
$this->connection->pushMessage($opcode, $payload, $masked);
}

public function close($status = 1000, $message = 'ttfn'){
if (!$this->isConnected())
return;
$this->connection->close($status, $message);
}

public function disconnect(){
if ($this->isConnected())
$this->connection->disconnect();
}

public function receive(){
if (!$this->isConnected())
$this->connect();
while (true) {
$message = $this->connection->pullMessage();
$opcode = $message[0][2];
if (in_array($opcode, $this->options['filter'])) {
[$this->last_opcode, $return] = [$opcode, $this->options['return_obj'] ? $message : $message[0][1]];
break;
} elseif ($opcode == 'close') {
[$this->last_opcode, $return] = [null, $this->options['return_obj'] ? $message : null];
break;
}
}
return $return;
}

public function getLastOpcode(){
return $this->last_opcode;
}

public function getCloseStatus(){
return $this->connection ? $this->connection->getCloseStatus() : null;
}

public function isConnected(){
return $this->connection and $this->connection->isConnected();
}

public function getName(){
return $this->isConnected() ? $this->connection->getName() : null;
}

public function getRemoteName(){
return $this->isConnected() ? $this->connection->getRemoteName() : null;
}

public function getPier(){
return $this->getRemoteName();
}


protected function connect(): void{
$this->connection = null;
if (isset($this->options['context'])) {
if (@get_resource_type($this->options['context']) === 'stream-context') {
$context = $this->options['context'];
} else
die("Stream context in \$options['context'] isn't a valid context.");
} else
$context = stream_context_create();
$persistent = $this->options['persistent'] === true;
$flags = STREAM_CLIENT_CONNECT;
$flags = $persistent ? $flags | STREAM_CLIENT_PERSISTENT : $flags;
$socket = null;
try {
$socket = stream_socket_client($this->socket_uri['scheme'] .'://'. $this->socket_uri['authority'] .':'. $this->socket_uri['port'], $errno, $errstr, $this->options['timeout'], $flags, $context);
if (!$socket)
die('No socket');
} catch (ErrorException $e) {
die("Could not open socket to \"{$host_uri->getAuthority()}\": {$e->getMessage()} ({$e->getCode()}).");
}
$this->connection = new Connection($socket, $this->options);
if (!$this->isConnected()) {
die("Invalid stream on \"{$host_uri->getAuthority()}\".");
}

if (!$persistent or $this->connection->tell() == 0) {
$this->connection->setTimeout($this->options['timeout']);
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
$header = "GET ". $this->socket_uri['path'] ." HTTP/1.1\r\n" . implode("\r\n", array_map(function ($key, $value) {
return "$key: $value";
}, array_keys($headers), $headers)) . "\r\n\r\n";
$this->connection->write($header);
$response = '';
try {
do {
$buffer = $this->connection->gets(1024);
$response .= $buffer;
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

protected static function generateKey($length = 16, $key = ''){
for ($i = 0; $i < $length; $i++)
$key .= chr(rand(33, 126));
return base64_encode($key);
}
}