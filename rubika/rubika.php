<?php
namespace rubi;
class rubika extends connection{
public $phone;

public function __construct($phone){
$this->phone = $phone;
parent::__construct($phone);
}

function __call($method, $args) {
foreach([new methods($this->phone)] as $object)
if(is_callable([$object, $method]))
return call_user_func_array([$object, $method], $args);
}
}
