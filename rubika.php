<?php
namespace rubi;

class rubika{

private static $c = [
'app_version' => '4.4.29',
'lang_code' => 'fa',
'package' => 'web.rubika.ir',
'platform' => 'Web'];
public $servers, $d;

public function __construct($phone){
getDCs:
$this->servers = (file_exists(__DIR__ .'/servers')) ? json_decode(file_get_contents(__DIR__ .'/servers'), true) : self::getDCs();
file_put_contents(__DIR__ .'/servers', json_encode($this->servers, 448));
if((filectime(__DIR__ .'/servers') + (6 * 60 * 60)) < time())
unlink(__DIR__ .'/servers');
$this->d = file_exists(encryption::secret($phone)) ? json_decode(encryption::openssl(false, file_get_contents(encryption::secret($phone)), encryption::secret($phone)), true) : [];
$this->d['auth'] ??= encryption::hash();
$this->d['key'] ??= encryption::crKeys();
if (($this->run('getMySessions')['status_det'] ?? '') == 'NOT_REGISTERED')
self::login($phone);
}

function login($phone){
if(!empty($this->d['code']['hash']) and $this->d['code']['time'] + 60 >= time()){
inputCode:
if(http_response_code() and empty($_POST['code'])){
echo '<form action="" method="POST"><input type="number" name="code" placeholder="enter code:"></input></form>';
}else if(empty($_POST['code']))
$_POST['code'] = readLine('enter code: ');
if(!empty($_POST['code'])){
$sign = $this->signIn($phone, $this->d['code']['hash'], $_POST['code'], $this->d['key'][0]);
if(isset($sign['data']['auth'])){
$this->d['self']['guide'] = $sign['data']['user']['user_guid'];
openssl_private_decrypt(base64_decode($sign['data']['auth']), $this->d['auth'], openssl_pkey_get_private($this->d['key'][1]), OPENSSL_PKCS1_OAEP_PADDING);
if(($reg = $this->registerDevice())['status_det'] ?? '' == 'OK'){
echo 'logined !' . PHP_EOL;
}else
die( json_encode($reg += ['type' => 'registerDevice']) );
}else
die( json_encode($sign += ['type' => 'sginIn']) );
}
}else if($sendCode = $this->sendCode($phone) and !empty($sendCode['data']['phone_code_hash'])){
[$this->d['code']['hash'], $this->d['code']['time']] = [$sendCode['data']['phone_code_hash'], time()];
goto inputCode;
}else
echo json_encode($sendCode += ['type' => 'sendCode'], 448);
file_put_contents(encryption::secret($phone), encryption::openssl(true, json_encode($this->d, 448), encryption::secret($phone)));
}

public static function req($u, $d = []){
curl_setopt($ch = curl_init($u), CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'Connection: keep-alive',
'Origin: https://web.rubika.ir',
'Referer: https://web.rubika.ir/',
'Sec-Ch-Ua-Platform: Windows',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36', 
((count($d) > 0) ? 'Content-Type: application/json' : '')]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($d));
$result = curl_exec($ch);
//curl_close($ch);
return json_decode($result, true);
}

public function run($m, $i = [], $t = false){
$d = [
'api_version' => '6',
(($t) ? 'tmp_session' : 'auth') => ((!$t) ? encryption::setAuth($this->d['auth']) : $this->d['auth']),
'data_enc' => ($s = encryption::openssl(true, json_encode([
'method' => $m,
'input' => $i,
'client' => self::$c], 448), encryption::secret($this->d['auth'])))];
if(!$t) $d['sign'] = encryption::sign($s, $this->d['key'][1]);
foreach (($this->servers['API'] ?? []) as $url)
if (isset(($r = self::req($url, $d))['data_enc']))
return json_decode(encryption::openssl(false, $r['data_enc'], encryption::secret($this->d['auth'])), true);
return json_decode($r, true);
}

public function downloadFile($dc_id, $access_hash_rec, $file_id, $mime, $chunk_size = 500 * 1024, $return = '', $start_index = 0){
while(empty($m[1]) or $start_index <= $m[1]){
curl_setopt($ch = curl_init($u = $this->servers['storage'][$dc_id] .'/GetFile.ashx'), CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'auth: '. encryption::setAuth($this->d['auth']),
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
$sendFile = $this->requestSendFile(basename($path), filesize($path), pathinfo($path)['extension']);
$file = fopen($path, 'rb');
for ($part = 1; $part <= ceil(filesize($path) / $chunk_size); $part++) {
$data = fread($file, $chunk_size);
curl_setopt($ch = curl_init($sendFile['data']['upload_url']), CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'auth: '. ($this->d['auth']),
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
while($t--)
if (($DCs = self::req('https://getdcmess.iranlms.ir'))['status_det'] ?? '' == 'OK')
return $DCs['data'];
else
sleep(mt_rand(3, 6));
}

public function onUpdate(callable $callback){
foreach (($this->servers['socket'] ?? []) as $socket)
($client = new socket($socket, ['timeout' => 60]))->send(json_encode([
'api_version' => '6',
'auth' => $this->d['auth'],
'data' => json_encode(['version' => 2]),
'method' => 'handShake',
'client' => self::$c]));
echo 'connected '. $socket . PHP_EOL;
while ($time ??= time() + 60 and $time >= time()) {
if(($time ?? 0) <= time() and $time = time() +3)
$client->send('{}');
$message = json_decode($client->receive(), true);
$callback((isset($message['data_enc'])) ? json_decode(encryption::openssl(false, $message['data_enc'], encryption::secret($this->d['auth'])), true) : $message ?? []);
}
$client->close();
}

public function sendCode($phone_number, $send_type = 'SMS'){
return $this->run('sendCode', compact('phone_number', 'send_type'), true);
}

public function registerDevice($token_type = 'Web', $token = '', $app_version = 'WB_4.4.29', $lang_code = 'fa', $system_version = 'Windows 10', $device_model = 'Chrome 4', $is_multi_account = false){
$device_hash = md5(time());
return $this->run('registerDevice', compact('token_type', 'token', 'app_version', 'lang_code', 'system_version', 'device_model', 'device_hash', 'is_multi_account'));
}

public function signIn($phone_number, $phone_code_hash, $phone_code, $public_key){
return $this->run('signIn', compact('phone_number', 'phone_code_hash', 'phone_code', 'public_key'), true);
}

public function getChats(){
return self::run('getChats');
}

public function getServiceInfo($service_guid){
return self::run('getServiceInfo', compact('service_guid'));
}

public function getMyStickerSets(){
return self::run('getMyStickerSets');
}

public function getFolders(){
return self::run('getFolders');
}

public function getChatsUpdates($state = 0){
$state === 0 ? $state = time() - 150 : $state;
return self::run('getChatsUpdates', compact('state'));
}
public function getChatAds($state = 0){
$state === 0 ? $state = time() - 150 : $state;
return self::run('getChatAds', compact('state'));
}

public function getUserInfo($user_guid = []){
return self::run('getUserInfo', compact('user_guid'));
}

public function getMessagesInterval($object_guid, $middle_message_id){
return self::run('getMessagesInterval', compact('object_guid', 'middle_message_id'));
}

public function getMessagesByID($object_guid, $message_ids){
return self::run('getMessagesByID', compact('object_guid', 'message_ids'));
}

public function getMessagesUpdates($object_guid, $state = 0){
$state === 0 ? $state = time() - 150 : $state;
return self::run('getMessagesUpdates', compact('object_guid', 'state'));
}

public function getAvatars($object_guid){
return self::run('getAvatars', compact('object_guid'));
}

public function sendChatActivity($object_guid, $activity) /*Typing , Uploading, Recording*/{
return self::run('sendChatActivity', compact('object_guid', 'activity'));
}

public static function metaData($text, $result = []){
$p ='/```(.*?)```|\*\*(.*?)\*\*|`(.*?)`|__(.*?)__|--(.*?)--|~~(.*?)~~|\|\|(.*?)\|\||\[(.*?)\]\(\s*(https?:\/\/\S+|g0|u0|c0|[^\s]+)\s*\)/us';
while(preg_match($p, $text, $m)){
if(str_contains($m[0], '```'))
$result[] = ['type' => 'Pre', 'lang' => explode('\n', trim($m[0], '`'))[0], 'from_index' => mb_strpos($text, '`'), 'length' => mb_strlen($m[0]) -6];
else if(str_contains($m[0], '**'))
$result[] = ['type' => 'Bold', 'from_index' => mb_strpos($text, '**'), 'length' => mb_strlen($m[0]) -4];
else if(str_contains($m[0], '`'))
$result[] = ['type' => 'Mono', 'from_index' => mb_strpos($text, '`'), 'length' => mb_strlen($m[0]) -2];
else if(str_contains($m[0], '__'))
$result[] = ['type' => 'Italic', 'from_index' => mb_strpos($text, '__'), 'length' => mb_strlen($m[0]) -4];
else if(str_contains($m[0], '--'))
$result[] = ['type' => 'Underline', 'from_index' => mb_strpos($text, '--'), 'length' => mb_strlen($m[0]) -4];
else if(str_contains($m[0], '~~'))
$result[] = ['type' => 'Strike', 'from_index' => mb_strpos($text, '~~'), 'length' => mb_strlen($m[0]) -4];
else if(str_contains($m[0], '||'))
$result[] = ['type' => 'Spoiler', 'from_index' => mb_strpos($text, '||'), 'length' => mb_strlen($m[0]) -4];
else if(!empty($m[9]) and self::object_type($m[9]))
$result[] = ['type' => 'MentionText', 'mention_text_object_guid' => $m[9], 'mention_text_object_type' => self::object_type($m[9]), 'from_index' => mb_strpos($text, '['), 'length' => mb_strlen($m[8])];
else if(!empty($m[9]))
$result[] = ['type' => 'Link', 'link' => ['type' => 'hyperlink', 'hyperlink_data' => ['url' => $m[9]]], 'from_index' => mb_strpos($text, '['), 'length' => mb_strlen($m[8])];
$text = preg_replace($p, "$1$2$3$4$5$6$7$8", $text, 1);
}
return ['data' => ['meta_data_parts' => $result], 'text' => trim($text)];
}

public static function object_type($object_guid){
if(str_contains($object_guid, 'u'))
return 'User';
else if(str_contains($object_guid, 'c'))
return 'Channel';
else if(str_contains($object_guid, 'g'))
return 'Group';
else
false;
}

public function sendMessage($object_guid, $reply_to_message_id, $text){
$meta = self::metaData($text);
$json = [
'rnd' => mt_rand(100000, 999999),
'object_guid' => $object_guid,
'reply_to_message_id' => $reply_to_message_id,
'text' => $meta['text']];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return self::run('sendMessage', $json);
}

public function editMessage($object_guid, $message_id, $text){
$meta = self::metaData($text);
$json = [
'object_guid' => $object_guid,
'message_id' => $message_id,
'text' => $meta['text']];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return self::run('editMessage', $json);
}

public function getMyGifSet(){
return self::run('getMyGifSet');
}

public function getAvailableReactions(){
return self::run('getAvailableReactions');
}

public function seenChats($seen_list){
return self::run('seenChats', compact('seen_list'));
}

public function addToMyGifSet(l$object_guid, $message_id){
return self::run('addToMyGifSet', compact('object_guid', 'message_id'));
}

public function actionOnMessageReaction($object_guid, $message_id, $action, $reaction_id = 1) /*Add, Delete*/{
$json = [
'action' => $action->value,
'message_id' => $message_id,
'object_guid' => $object_guid];
if ($action == 'Add')
$json['reaction_id'] = $reaction_id;
return self::run('actionOnMessageReaction', $json);
}

public function getTrendStickerSets(){
return self::run('getTrendStickerSets');
}

public function actionOnStickerSet($sticker_set_id, $action){
return self::run('actionOnStickerSet', compact('sticker_set_id', 'action'));
}

public function deleteMessages($object_guid, $message_ids, $type = 'Global')/*Local, Global*/{
return self::run('deleteMessages', compact('object_guid', 'message_ids', 'type'));
}

public function getMySessions(){
return self::run('getMySessions');
}

public function getInfoByUsername($username){
return self::run('getObjectByUsername', compact('username'));
}


public function getMessages($object_guid, $sort = 'FromMax', $min_id = null){ /* FromMin, FromMax */
$json = ['object_guid' => $object_guid,
'sort' => $sort];
empty($min_id) ? null : $json['min_id'] = $min_id;
return self::run('getMessages', $json);
}

public function getContacts(){
return self::run('getContacts');
}

public function getContactsUpdates(){
$state === 0 ? $state = time() - 150 : $state;
return self::run('getContactsUpdates', compact('state'));
}

public function updateProfile($first_name = null, $last_name = null, $bio = null, $birth_date = null){
$json = ['updated_parameters' => []];
if (!empty($bio))
[$json['bio'] , $json['updated_parameters'][]] = [$bio, 'bio'];
if (!empty($first_name))
[$json['first_name'] , $json['updated_parameters'][]] = [$first_name, 'first_name'];
if (!is_null($last_name)) 
[$json['last_name'] , $json['updated_parameters'][]] = [$last_name, 'last_name'];
if (!is_null($birth_date))
[$json['birth_date'] , $json['updated_parameters'][]] = [$birth_date, 'birth_date'];
return self::run('updateProfile', $json);
}

public function terminateSession($session_key){
return self::run('terminateSession', compact('session_key'));
}

public function getBlockedUsers(){
return self::run('getBlockedUsers');
}

public function requestDeleteAccount(){
return self::run('requestDeleteAccount');
}

public function getPrivacySetting(){
return self::run('getPrivacySetting');
}

public function getGroupInfo($group_guid){
return self::run('getGroupInfo', compact('group_guid'));
}

public function getGroupOnlineCount($group_guid){
return self::run('getGroupOnlineCount', compact('group_guid'));
}

public function getAbsObjects($objects_guids){
return self::run('getAbsObjects', compact('objects_guids'));
}

public function getListMessagesByID(array $object_guid, array $message_ids){
return self::run('getMessagesByID', compact('objects_guids', 'message_ids'));
}

public function getLinkFromAppUrl($app_url){
return self::run("getLinkFromAppUrl", compact('app_url'));
}

public function getChannelInfo($channel_guid){
return self::run("getChannelInfo", compact('channel_guid'));
}

public function createGroupVoiceChat($chat_guid){
return self::run("createGroupVoiceChat", compact('chat_guid'));
}

public function getGroupVoiceChatParticipants($chat_guid, $voice_chat_id){
return self::run("getGroupVoiceChatParticipants", compact('chat_guid', 'voice_chat_id'));
}

public function setGroupVoiceChatSetting($chat_guid, $voice_chat_id, $title){
return self::run("setGroupVoiceChatSetting", ["chat_guid" => $chat_guid, "voice_chat_id" => $voice_chat_id, "title" => $title, "updated_parameters" => ["title"]]);
}

public function setGroupVoiceChatSettingMute($chat_guid, $voice_chat_id, $mute = false){
return self::run("setGroupVoiceChatSetting", ["chat_guid" => $chat_guid, "voice_chat_id" => $voice_chat_id, "join_muted" => $mute, "updated_parameters" => ["join_muted"]]);
}

public function discardGroupVoiceChat($chat_guid, $voice_chat_id){
return self::run("discardGroupVoiceChat", compact('chat_guid', 'voice_chat_id'));
}

public function getGroupAllMembers($group_guid){
return self::run("getGroupAllMembers", compact('group_guid'));
}

public function getGroupDefaultAccess($group_guid){
return self::run("getGroupDefaultAccess", compact('group_guid'));
}

public function getGroupAdminMembers($group_guid){
return self::run("getGroupAdminMembers", compact('group_guid'));
}

public function getGroupLink($group_guid){
return self::run("getGroupLink", compact('group_guid'));
}

public function setGroupLink($group_guid){
return self::run("setGroupLink", compact('group_guid'));
}

public function setAllReaction($group_guid){
return self::run("editGroupInfo", ["group_guid" => $group_guid, "chat_reaction_setting" => ["reaction_type" => "All"], "updated_parameters" => ["chat_reaction_setting"]]);
}

public function getBannedGroupMembers($group_guid){
return self::run("getBannedGroupMembers", compact('group_guid'));
}

public function leaveGroup($group_guid){
return self::run("leaveGroup", compact('group_guid'));
}

public function joinGroupByLink($hash_link){
return self::run("joinGroup", compact('hash_link'));
}

public function searchGlobalObjects($search_text){
return self::run("searchGlobalObjects", compact('search_text'));
}

public function addAddressBook($phone, $first_name, $last_name = ""){
if (substr($phone, 0, 1) == 0) 
$phone = "+98" . substr($phone, 1);
else if (substr($phone, 0, 2) == "98")
$phono = "+" . $phone;
return self::run("addAddressBook", compact('phone', 'first_name', 'last_name'));
}

public function getContactsLastOnline($user_guids){
return self::run("getContactsLastOnline", compact('user_guids'));
}

public function addGroup($member_guids){
return self::run("addGroup", compact('member_guids'));
}

public function addGroupMembers($group_guid, $member_guids){
return self::run("addGroupMembers", compact('group_guid', 'member_guids'));
}

public function logout(){
return self::run("logout");
}

public function setGroupAdmin($group_guid, $member_guid, $action = 'SetAdmin', $access_list = []){
if ($access_list == [])
$access_list = ["ChangeInfo", "PinMessages", "DeleteGlobalAllMessages", "BanMember", "SetAdmin", "SetMemberAccess", "SetJoinLink"];
return self::run("setGroupAdmin", compact('group_guid', 'member_guid', 'action', 'access_list'));
}

public function setGroupUnAdmin($group_guid, $member_guid, $action = 'UnsetAdmin'){
return self::run("setGroupAdmin", compact('group_guid', 'member_guid', 'action'));
}

public function banGroupMember($group_guid, $member_guid, $action = 'Set'){
return self::run("banGroupMember", compact('group_guid', 'member_guid', 'action'));
}

public function unBanGroupMember($group_guid, $member_guid, $action = 'Unset'){
return self::run("banGroupMember", compact('group_guid', 'member_guid', 'action'));
}

public function searchMemberGroup($group_guid, $search_text){
return self::run("getGroupAllMembers", compact('group_guid', 'search_text'));
}

public function getGroupMessageReadParticipants($group_guid, int $message_id){
return self::run("getGroupMessageReadParticipants", compact('group_guid', 'message_id'));
}

public function forwardMessages($from, $to, $message_ids){
return self::run("forwardMessages", ["from_object_guid" => $from, "to_object_guid" => $to, "message_ids" => $message_ids, "rnd" => (string) random_int(12332, 987889)]);
}

public function getStickersBySetIDs($sticker_set_ids){
return self::run("getStickersBySetIDs", compact('sticker_set_ids'));
}

public function uploadAvatar($thumbnail_file_id, $main_file_id){
return self::run("uploadAvatar", compact('thumbnail_file_id', 'main_file_id'));
}

public function addChannel($title, $channel_type = 'Private', $member_guids = null){ /*Public, Private*/
return self::run("addChannel", compact('title', 'channel_type', 'member_guids'));
}

public function setBlockUser($user_guid, $action){ /*Block, Unblock*/
return self::run("setBlockUser", compact('user_guid', 'action'));
}

public function setPinMessage($object_guid, int $message_id, $action){ /*Pin, Unpin*/
return self::run("setPinMessage", compact('object_guid', 'message_id', 'action'));
}

public function deleteUserChat($user_guid, $last_deleted_message_id = 0){
return self::run("deleteUserChat", compact('user_guid', 'last_deleted_message_id'));
}

public function getPendingObjectOwner($object_guid){
return self::run("getPendingObjectOwner", compact('object_guid'));
}

public function actionOnJoinRequest($object_guid, $user_guid, $object_type = 'Group', $action = 'Accept'){ /*Group, Channel*//*Accept, Reject*/
return self::run("actionOnJoinRequest", compact('object_guid', 'user_guid', 'object_type', 'action'));
}

public function createJoinLink($group_guid, $title, $request_needed = true, int $usage_limit = 0, int $time = 0){
$json = [
"object_guid" => $group_guid,
"title" => $title,
"request_needed" => $request_needed,
"usage_limit" => $usage_limit];
$time === 0 ? null : $json["expire_time"] = $time;
return self::run("createJoinLink", $json);
}

public function getJoinLinks($object_guid){
return self::run("getJoinLinks", compact('object_guid'));
}

public static function livePlayer($path, $stream_Url, $stream_Key, $rotation = false){
$transpose = $rotation === false ?: " -vf transpose=$rotation";
$command = "ffmpeg -re -i {$path} -b:v 1200k -c:v libx264 -preset fast -g 50$transpose -c:a aac -b:a 128k -f flv {$stream_Url}{$stream_Key}";
exec($command);
}

public function checkUserUsername($username){
return self::run("checkUserUsername", compact('username'));
}

public function updateUsername($username){
return self::run("updateUsername", compact('username'));
}

public function getJoinRequests($object_guid){
return self::run("getJoinRequests", compact('object_guid'));
}

public function setProfileSetting($show_my_phone_number = null, $show_my_last_online = null, $show_my_profile_photo = null, $show_my_birth_date = null, $link_forward_message = null, $can_join_chat_by = null){ //Everybody, MyContacts, Nobody}*/
$json = ['updated_parameters' => []];
if (!empty($show_my_birth_date))
[$json['bio'] , $json['updated_parameters'][]] = [$show_my_birth_date, 'show_my_birth_date'];
if (!empty($show_my_birth_date))
[$json['show_my_last_online'] , $json['updated_parameters'][]] = [$show_my_last_online, 'show_my_last_online'];
if (!empty($show_my_profile_photo))
[$json['show_my_profile_photo'] , $json['updated_parameters'][]] = [$show_my_profile_photo, 'show_my_profile_photo'];
if (!empty($show_my_profile_photo))
[$json['link_forward_message'] , $json['updated_parameters'][]] = [$link_forward_message, 'link_forward_message'];
if (!empty($show_my_profile_photo))
[$json['can_join_chat_by'] , $json['updated_parameters'][]] = [$can_join_chat_by, 'can_join_chat_by'];
if (!empty($show_my_profile_photo))
[$json['show_my_phone_number'] , $json['updated_parameters'][]] = [$show_my_phone_number, 'show_my_phone_number'];
return self::run("setSetting", $json);
}

public function channelPreviewByJoinLink($link){
return self::run("channelPreviewByJoinLink", compact('hash_link'));
}

public function groupPreviewByJoinLink($link){
return self::run("groupPreviewByJoinLink", compact('hash_link'));
}

public function getCommonGroups($user_guid){
return self::run("getCommonGroups", compact('user_guid'));
}

public function getTranscription($object_guid, $message_id,$transcription_id = null){
if (is_null($transcription_id)) {
$transcription_id = self::transcribeVoice($object_guid, $message_id);
if (isset($transcription_id["transcription_id"])) 
$transcription_id = $transcription_id["transcription_id"];
elseif (isset($transcription_id["status"]) or $transcription_id["status"] == "NotAllowed" or $transcription_id["status"] == "NotReady") 
return false;
else 
return $transcription_id;
}
return self::run("getTranscription", compact('message_id', 'transcription_id'));
}

public function transcribeVoice($object_guid, $message_id){
return self::run("transcribeVoice", compact('object_guid', 'message_id'));
}

public function is_out($user_guid){
return $user_guid == $this->d['self']['guide'];
}

public static function imageInfo($file_path){
$info = getimagesize($file_path);
$img = (($info[2] == IMAGETYPE_JPEG) ? 'imagecreatefromjpeg' : 'imagecreatefrompng')($file_path);
$thumb_image = imagecreatetruecolor($info[0], $info[1]);
imagecopyresampled($thumb_image, $img, 0, 0, 0, 0, $info[0], $info[1], $info[0], $info[1]);
ob_start();
(($info[2] == IMAGETYPE_JPEG) ? 'imagejpeg' : 'imagepng')($thumb_image);
$thumb = ob_get_contents();
ob_end_clean();
imagedestroy($img);
imagedestroy($thumb_image);
return ['thumb' => $thumb, 'width' => $info[0], 'height' => $info[1]];
}

public function sendPhoto($object_guid, $reply_to_message_id, $path, $caption = null){
$api = self::sendFileToAPI($path);
$img = self::imageInfo($path);
$meta = self::metaData($caption);
$json = [
'object_guid' => $object_guid,
'reply_to_message_id' => $reply_to_message_id,
'text' => $meta['text'],
'rnd' => mt_rand(10000000, 999999999),
'file_inline' => [
'dc_id' => $api['data']['dc_id'],
'file_id' => $api['data']['file_id'],
'file_name' => $api['data']['file_name'],
'size' => $api['data']['size'],
'type' => 'Image',
'mime' => $api['data']['mime'],
'thumb_inline' => base64_encode($img['thumb']),
'width' => $img['width'],
'height' => $img['height'],
'access_hash_rec' => $api['data']['access_hash_rec'] ]];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return self::run('sendMessage', $json);
}

public function sendDocument($object_guid, $reply_to_message_id, $path, $caption = null){
$api = self::sendFileToAPI($path);
$meta = self::metaData($caption);
$json = [
'object_guid' => $object_guid,
'reply_to_message_id' => $reply_to_message_id,
'text' => $meta['text'],
'rnd' => mt_rand(10000000, 999999999),
'file_inline' => [
'dc_id' => $api['data']['dc_id'],
'file_id' => $api['data']['file_id'],
'file_name' => $api['data']['file_name'],
'size' => $api['data']['size'],
'type' => 'File',
'mime' => $api['data']['mime'],
'access_hash_rec' => $api['data']['access_hash_rec'] ]];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return self::run('sendMessage', $json);
}

}
