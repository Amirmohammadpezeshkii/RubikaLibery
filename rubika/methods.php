<?php
namespace amirmohamadpezeshki\rubika;
class methods extends connection{

public function __construct($phone){
parent::__construct($phone);
}

public function getChats(){
return connection::run('getChats');
}

public function getServiceInfo($service_guid){
return connection::run('getServiceInfo', compact('service_guid'));
}

public function getMyStickerSets(){
return connection::run('getMyStickerSets');
}

public function getFolders(){
return connection::run('getFolders');
}

public function getChatsUpdates($state = 0){
$state === 0 ? $state = time() - 150 : $state;
return connection::run('getChatsUpdates', compact('state'));
}
public function getChatAds($state = 0){
$state === 0 ? $state = time() - 150 : $state;
return connection::run('getChatAds', compact('state'));
}

public function getUserInfo($user_guid = []){
return connection::run('getUserInfo', compact('user_guid'));
}

public function getMessagesInterval($object_guid, $message_id){
return connection::run('getMessagesInterval', compact('object_guid', 'message_id'));
}

public function getMessagesByID($object_guid, $message_ids){
return connection::run('getMessagesByID', compact('object_guid', 'message_ids'));
}

public function getMessagesUpdates($object_guid, $state = 0){
$state === 0 ? $state = time() - 150 : $state;
return connection::run('getMessagesUpdates', compact('object_guid', 'state'));
}

public function getAvatars($object_guid){
return connection::run('getAvatars', compact('object_guid'));
}

public function sendChatActivity($object_guid, $action) /*Typing , Uploading, Recording*/{
return connection::run('sendChatActivity', ['object_guid' => $object_guid, 'activity' => $action->value]);
}

public function metaData($text, $result = []){
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
$text = preg_replace($p, '$1$2$3$4$5$6$7$8', $text, 1);
}
return ['data' => ['meta_data_parts' => $result], 'text' => trim($text)];
}

public function sendMessage($object_guid, $reply_to_message_id, $text, $metadata = null){
$meta = self::metaData($text);
$json = [
'rnd' => mt_rand(100000, 999999),
'object_guid' => $object_guid,
'reply_to_message_id' => $reply_to_message_id,
'text' => $meta['text']];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return connection::run('sendMessage', $json);
}

public function editMessage($object_guid, $message_id, $text){
$meta = self::metaData($text);
$json = [
'object_guid' => $object_guid,
'message_id' => $message_id,
'text' => $meta['text']];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = null;
return connection::run('editMessage', $json);
}

public function sendRubinoStory($object_guid, $story_id, $profile_id, $is_mute = false){
$json = [
'is_mute' => $is_mute,
'object_guid' => $object_guid,
'rnd' => random_int(-998899, -12312),
'story_id' => $story_id,
'story_profile_id' => $profile_id,
'type' => 'Direct'];
return connection::run('sendRubinoStory', $json);
}

public function sendRubinoPost($object_guid, $post_id, $profile_id, $is_mute = false){
$json = [
'is_mute' => $is_mute,
'object_guid' => $object_guid,
'rnd' => random_int(-998899, -12312),
'post_id' => $post_id,
'post_profile_id' => $profile_id];
return connection::run('sendRubinoPost', $json);
}

public function getMyGifSet(){
return connection::run('getMyGifSet');
}

public function getAvailableReactions(){
return connection::run('getAvailableReactions');
}

public function seenChats($seen_list){
return connection::run('seenChats', compact('seen_list'));
}

public function addToMyGifSet(l$object_guid, $message_id){
return connection::run('addToMyGifSet', compact('object_guid', 'message_id'));
}

public function actionOnMessageReaction($object_guid, $message_id, $action, $reaction_id = 1) /*Add, Delete*/{
$json = [
'action' => $action->value,
'message_id' => $message_id,
'object_guid' => $object_guid];
if ($action == 'Add')
$json['reaction_id'] = $reaction_id;
return connection::run('actionOnMessageReaction', $json);
}

public function getTrendStickerSets(){
return connection::run('getTrendStickerSets');
}

public function actionOnStickerSet($sticker_set_id, $action){
return connection::run('actionOnStickerSet', compact('sticker_set_id', 'action'));
}

public function deleteMessages($object_guid, $message_ids, $type = 'Global')/*Local, Global*/{
return connection::run('deleteMessages', compact('object_guid', 'message_ids', 'type'));
}

public function getMySessions(){
return connection::run('getMySessions');
}

public function getInfoByUsername($username){
return connection::run('getObjectByUsername', compact('username'));
}


public function getMessages($object_guid, $sort = 'FromMax', $min_id = null){ /* FromMin, FromMax */
$json = ['object_guid' => $object_guid,
'sort' => $sort->value];
empty($min_id) ? null : $json['min_id'] = $min_id;
return connection::run('getMessages', $json);
}

public function getContacts(){
return connection::run('getContacts');
}

public function getContactsUpdates(){
$state === 0 ? $state = time() - 150 : $state;
return connection::run('getContactsUpdates', compact('state'));
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
return connection::run('updateProfile', $json);
}

public function terminateSession($session_key){
return connection::run('terminateSession', compact('session_key'));
}

public function getBlockedUsers(){
return connection::run('getBlockedUsers');
}

public function requestDeleteAccount(){
return connection::run('requestDeleteAccount');
}

public function getPrivacySetting(){
return connection::run('getPrivacySetting');
}

public function getGroupInfo($group_guid){
return connection::run('getGroupInfo', compact('group_guid'));
}

public function getGroupOnlineCount($group_guid){
return connection::run('getGroupOnlineCount', compact('group_guid'));
}

public function getAbsObjects($objects_guids){
return connection::run('getAbsObjects', compact('objects_guids'));
}

public function getListMessagesByID(array $object_guid, array $message_ids){
return connection::run('getMessagesByID', compact('objects_guids', 'message_ids'));
}

public function downloadFile($file_inline = null, $object_guid = null, $message_id = null, $name = null, $progress = null){
$name = is_null($name) ? random_int(0, 100) : $name;
if (empty($file_inline)){
$message_info = self::getMessagesByID($object_guid, [$message_id]);
if (!isset($message_info['messages'][0]['file_inline'])) 
return 'file not found | please check param [object_guid | message_id]';
$message_info['messages'][0]['file_inline'];
} else
$message_info = $file_inline;
if (!isset($message_info['mime'], $message_info['access_hash_rec'], $message_info['size'])) 
return 'file (info) not found';
$MB_fileSize = $message_info['size'] / (1024 * 1024);
$partSize = 128 * (1024 * 2);
for ($part = 0; $part < ceil($message_info['size'] / $partSize); $part++) {
if (is_callable($progress)) $progress('file Size (byte : '. $message_info['size'] .') , (MB : '. $MB_fileSize .') | total Part : '. ceil($message_info['size'] / $partSize) .' | upload part '. $part);
$start_index = $part * $partSize;
$last_index = min(($start_index + $partSize - 1), $message_info['size'] - 1);
$data = self::requestDownloadFile($message_info['access_hash_rec'], $message_info['file_id'], $message_info['dc_id'], $last_index, $start_index, 'bytes='. $start_index .'-'. $last_index);
file_put_contents($name .'.'. $message_info['mime'], $data, FILE_APPEND);
}
return $name .'.'. $message_info['mime'];
}

private function requestDownloadFile($hash, $file_id, $dc_id, $last_index, $start_index, $range) {
$url = 'https://messenger$dc_id.iranlms.ir/GetFile.ashx';
curl_setopt($ch = curl_init('https://messenger'. $dc_id .'.iranlms.ir/GetFile.ashx'), CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'Access-Hash-Rec: '. $hash,
'Auth: '. encryption::setAuth($this->auth),
'Client-App-Name: Main',
'Client-App-Version: 3.8.1',
'Client-Package: app.rbmain.a',
'Client-Platform: Android',
'Connection: Keep-Alive',
'Content-Length: 0',
'Content-Type: application/json',
'dc-id: '. $dc_id,
'file-id: '. $file_id,
'Host: '. parse_url($url, PHP_URL_HOST),
'last-index: '. $last_index,
'range: '. $range,
'start-index: '.$start_index]);
$result = curl_exec($ch);
curl_close($ch);
return $result;
}

public function requestSendFile($file_name, $mime, $size){
return connection::run('requestSendFile', compact('file_name', 'mime', 'size'));
}

public function UploadedFile($file, string|null $costomMime = null, $progress = null){
$file_name = pathinfo($file, PATHINFO_BASENAME);
$file_content = @file_get_contents($file, false, stream_context_create(['ssl' => [
'verify_peer' => false,
'verify_peer_name' => false]]));
if (!$file_content)
return 'Failed to retrieve contents';
$parts = ceil(strlen($file_content) / 128 * 1024);
$file_size = strval(strlen($file_content));
$pr = self::requestSendFile($file_name, is_null($costomMime) ? pathinfo($file, PATHINFO_EXTENSION) : $costomMime, $file_size);
for ($part = 1; $part <= $parts; $part++) {
$start = ($part - 1) * 128 * 1024;
$data = substr($file_content, $start, min($part * 128 * 1024, strlen($file_content)) - $start);
$response = $this->UploadFileToServer($pr['upload_url'], $data, strval(strlen($data)), $pr['id'], strval($part), $pr['access_hash_send'], strval($parts));
if (is_callable($progress))
$progress('file size (byte) '. $file_size .' | total part : '. $parts .' | upload part '. $part, json_encode($response));
if (!empty($response['data']))
return ['dc_id' => $pr['dc_id'], 'file_id' => $pr['id'], 'file_name' => $file_name, 'file_size' => $file_size, 'mime' => pathinfo($file, PATHINFO_EXTENSION), 'hash_code' => $response['data']['access_hash_rec']];
else if ($response['status'] == 'ERROR_TRY_AGAIN' || $response['status'] == 'ERROR_GENERIC')
return 'upload error : (ERROR_TRY_AGAIN)';
}
}

public function sendFile($file_path, $object_guid, $reply_to_message_id = null, $captipn = '', callable|null $progress = null){
$upload = $this->UploadedFile($file_path, $progress);
if (!isset($upload['status']) or !$upload['status'] ?? false) 
return 'upload error';
$meta = self::metaData($captipn);
$json = [
'object_guid' => $object_guid,
'rnd' => mt_rand(100000, 999999),
'reply_to_message_id' => $reply_to_message_id,
'text' => $meta['text'],
'file_inline' => [
'dc_id' => $upload['dc_id'],
'file_id' => $upload['file_id'],
'type' => 'File',
'file_name' => $up['file_name'] .'.'. $upload['mime'],
'size' => $upload['file_size'],
'mime' => $upload['mime'],
'access_hash_rec' => $upload['hash_code']]];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return connection::run('sendMessage', $json);
}

public function sendMultyFile($object_guid, array $file_inline, $caption = ''){
$meta = self::metaData($captipn);
$json = [
'object_guid' => $object_guid,
'rnd' => (string) mt_rand(100000, 999999),
'text' => $meta['text'],
'file_inline' => $file_inline];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return connection::run('sendMessage', $json);
}

public function sendLive($object_guid, $title, $device_type = 'Android', $comments_list = ['hello', 'ok']){
return connection::run('sendLive', [
'device_type' => $device_type,
'object_guid' => $object_guid,
'rnd' => random_int(1, 99),
'suggestion_comments' => $comments_list,
'title' => $title,
'thumb_inline' => "\/9j\/4AAQSkZJRgABAQAAAQABAAD\/4gIoSUNDX1BST0ZJTEUAAQEAAAIYAAAAAAIQAABtbnRyUkdC\nIFhZWiAAAAAAAAAAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAA\nAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlk\nZXNjAAAA8AAAAHRyWFlaAAABZAAAABRnWFlaAAABeAAAABRiWFlaAAABjAAAABRyVFJDAAABoAAA\nAChnVFJDAAABoAAAAChiVFJDAAABoAAAACh3dHB0AAAByAAAABRjcHJ0AAAB3AAAADxtbHVjAAAA\nAAAAAAEAAAAMZW5VUwAAAFgAAAAcAHMAUgBHAEIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\nAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFhZWiAA\nAAAAAABvogAAOPUAAAOQWFlaIAAAAAAAAGKZAAC3hQAAGNpYWVogAAAAAAAAJKAAAA+EAAC2z3Bh\ncmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABYWVogAAAAAAAA9tYAAQAAAADT\nLW1sdWMAAAAAAAAAAQAAAAxlblVTAAAAIAAAABwARwBvAG8AZwBsAGUAIABJAG4AYwAuACAAMgAw\nADEANv\/bAEMADQkKCwoIDQsKCw4ODQ8TIBUTEhITJxweFyAuKTEwLiktLDM6Sj4zNkY3LC1AV0FG\nTE5SU1IyPlphWlBgSlFST\/\/bAEMBDg4OExETJhUVJk81LTVPT09PT09PT09PT09PT09PT09PT09P\nT09PT09PT09PT09PT09PT09PT09PT09PT09PT\/\/AABEIADIAJQMBIgACEQEDEQH\/xAAWAAEBAQAA\nAAAAAAAAAAAAAAAAAQf\/xAAUEAEAAAAAAAAAAAAAAAAAAAAA\/8QAFQEBAQAAAAAAAAAAAAAAAAAA\nAAH\/xAAUEQEAAAAAAAAAAAAAAAAAAAAA\/9oADAMBAAIRAxEAPwDMRABUAAAAAAAAABUAAAFARQAA\nFAAf\/9k=\n"]);
}

public function sendImage($object_guid, $file_path, $reply_to_message_id, $costomMime = null, $caption = ""){
$up = $this->UploadedFile($file_path, $costomMime);
if (is_string($up['status']))
return $up;
$imageInfo = getimagesize($file_path);
if ($imageInfo !== false)
[$width, $height] = $imageInfo;
$meta = self::metaData($captipn);
$json =[
"object_guid" => $object_guid,
"rnd" => (string) mt_rand(100000, 999999),
'reply_to_message_id' => $reply_to_message_id,
'text' => $meta['text'],
"file_inline" => [
"dc_id" => $up["dc_id"],
"file_id" => $up["file_id"],
"type" => "Image",
"file_name" => $up["file_name"] . $up["mime"],
"size" => $up["file_size"],
"mime" => $up["mime"],
"thumb_inline" => self::getImageThumbInline($file_path),
"width" => $width,
"height" => $height,
"access_hash_rec" => $up["hash_code"]]];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return connection::run("sendMessage", $json);
}

private static function getImageThumbInline($file_path){
$image = imagecreatefromstring(file_get_contents($file_path));
if (!$image)
return "\/9j\/4AAQSkZJRgABAQAAAQABAAD\/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb\/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICQkKDA8MCgsOCwkJDRENDg8QEBEQCgwSExIQEw8QEBD\/2wBDAQMDAwQDBAgEBAgQCwkLEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBD\/wAARCAAoACgDASIAAhEBAxEB\/8QAGwAAAgIDAQAAAAAAAAAAAAAAAAgFBwQGCQL\/xAAuEAABAwMBBgQHAQEAAAAAAAABAgMEAAURBgcIEiExQRNRcZEUM1JhgaHBIjL\/xAAYAQADAQEAAAAAAAAAAAAAAAADBAUBAv\/EACkRAAECBQMBCQEAAAAAAAAAAAIAAQMEBhEhBRIx0RMiI0FCcYGhseH\/2gAMAwEAAhEDEQA\/AHvbtcjjbDbaC3k+ISSCPLHao7VGi7rqG1TrNG1IqEma1woSlhKuDHU4PM\/c1LWzTTDTofuWo7ncXEKCm1PvJSEEdCEoCU559cVsL0eDJaDTrilAfS4U59eHGakFr8Jnw6IOjkPK557WNmM\/Q12NtdvrE2akcb3CccJPMDOevmO1VRdX7mw5wy1KBHb7V0g1VsU2a3lmSgWdTU+chYRKHG6Glnn4hBOPfrVAzNzu\/TJMj4vVUBthIUWVJSVKUewIxhPuaqy1USu3xDt7\/wASsXQIpP3BulHk3laEcCRjzPnRV6ah3W7pZJSUzrpbloKgFK8ZzhSD0JUEY9qKqBrkvEFiA8JR9Eji9nBWvF3hbgMeKxHUcYyFkGs9G8RKCPksA9j4hxSJo2iSXUBxuSFpPMKSrIP5rw5tCuZ+W9gfcmop0rInnb9v1VcKjmmwT3+G6J6Xt4acQQlTA\/f9qIkbwF3SVKTMCgfqAIFI+\/ry9rSUic4M+S8VDytTXR4EGU4rI55dUc\/usGlZIfSy7epJjyTrXnbhIuSC3cI8aS35OJyB6EcxRSNOagu6UFtM5xKT1SlZx7UU0NPysNrA2EIqhmne9\/xU1atR3O0f5hyVBpXPw1c0H8H+VsEXXrxJ+OiBYzkFlXCf3RRR4cY24dJlDEuWWazryG6eB1h9oZ5KJCgPXvRI1YkJJhKKsH\/pRGB6DrRRTIxSflAKGLPhYZ1lKSVKeZZKe2MjFFFFd7yWdmK\/\/9k=";
$width = imagesx($image);
$height = imagesy($image);
if ($height > $width)
[$newHeight, $newWidth] = [40, round($newHeight * $width / $height)];
else
[$newWidth, $newHeight] = [40, round($newWidth * $height / $width)];
$thumb = imagecreatetruecolor($newWidth, $newHeight);
imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
ob_start();
imagepng($thumb);
$changedImage = ob_get_contents();
ob_end_clean();
imagedestroy($image);
imagedestroy($thumb);
return base64_encode($changedImage);
}

public function getLinkFromAppUrl($app_url){
return connection::run("getLinkFromAppUrl", compact('app_url'));
}

public function getChannelInfo($channel_guid){
return connection::run("getChannelInfo", compact('channel_guid'));
}

public function createGroupVoiceChat($chat_guid){
return connection::run("createGroupVoiceChat", compact('chat_guid'));
}

public function getGroupVoiceChatParticipants($chat_guid, $voice_chat_id){
return connection::run("getGroupVoiceChatParticipants", compact('chat_guid', 'voice_chat_id'));
}

public function setGroupVoiceChatSetting($chat_guid, $voice_chat_id, $title){
return connection::run("setGroupVoiceChatSetting", ["chat_guid" => $chat_guid, "voice_chat_id" => $voice_chat_id, "title" => $title, "updated_parameters" => ["title"]]);
}

public function setGroupVoiceChatSettingMute($chat_guid, $voice_chat_id, $mute = false){
return connection::run("setGroupVoiceChatSetting", ["chat_guid" => $chat_guid, "voice_chat_id" => $voice_chat_id, "join_muted" => $mute, "updated_parameters" => ["join_muted"]]);
}

public function discardGroupVoiceChat($chat_guid, $voice_chat_id){
return connection::run("discardGroupVoiceChat", compact('chat_guid', 'voice_chat_id'));
}

public function getGroupAllMembers($group_guid){
return connection::run("getGroupAllMembers", compact('group_guid'));
}

public function getGroupDefaultAccess($group_guid){
return connection::run("getGroupDefaultAccess", compact('group_guid'));
}

public function getGroupAdminMembers($group_guid){
return connection::run("getGroupAdminMembers", compact('group_guid'));
}

public function getGroupLink($group_guid){
return connection::run("getGroupLink", compact('group_guid'));
}

public function setGroupLink($group_guid){
return connection::run("setGroupLink", compact('group_guid'));
}

public function setAllReaction($group_guid){
return connection::run("editGroupInfo", ["group_guid" => $group_guid, "chat_reaction_setting" => ["reaction_type" => "All"], "updated_parameters" => ["chat_reaction_setting"]]);
}

public function getBannedGroupMembers($group_guid){
return connection::run("getBannedGroupMembers", compact('group_guid'));
}

public function leaveGroup($group_guid){
return connection::run("leaveGroup", compact('group_guid'));
}

public function joinGroupByLink($hash_link){
return connection::run("joinGroup", compact('hash_link'));
}

public function searchGlobalObjects($search_text){
return connection::run("searchGlobalObjects", compact('search_text'));
}

public function addAddressBook($phone, $first_name, $last_name = ""){
if (substr($phone, 0, 1) == 0) 
$phone = "+98" . substr($phone, 1);
else if (substr($phone, 0, 2) == "98")
$phono = "+" . $phone;
return connection::run("addAddressBook", compact('phone', 'first_name', 'last_name'));
}

public function getContactsLastOnline($user_guids){
return connection::run("getContactsLastOnline", compact('user_guids'));
}

public function addGroup($member_guids){
return connection::run("addGroup", compact('member_guids'));
}

public function addGroupMembers($group_guid, $member_guids){
return connection::run("addGroupMembers", compact('group_guid', 'member_guids'));
}

public function logout(){
return connection::run("logout");
}

public function setGroupAdmin($group_guid, $member_guid, $action = 'SetAdmin', $access_list = []){
if ($access_list == [])
$access_list = ["ChangeInfo", "PinMessages", "DeleteGlobalAllMessages", "BanMember", "SetAdmin", "SetMemberAccess", "SetJoinLink"];
return connection::run("setGroupAdmin", compact('group_guid', 'member_guid', 'action', 'access_list'));
}

public function setGroupUnAdmin($group_guid, $member_guid, $action = 'UnsetAdmin'){
return connection::run("setGroupAdmin", compact('group_guid', 'member_guid', 'action'));
}

public function banGroupMember($group_guid, $member_guid, $action = 'Set'){
return connection::run("banGroupMember", compact('group_guid', 'member_guid', 'action'));
}

public function unBanGroupMember($group_guid, $member_guid, $action = 'Unset'){
return connection::run("banGroupMember", compact('group_guid', 'member_guid', 'action'));
}

public function searchMemberGroup($group_guid, $search_text)
{
return connection::run("getGroupAllMembers", compact('group_guid', 'search_text'));
}

public function getGroupMessageReadParticipants($group_guid, int $message_id){
return connection::run("getGroupMessageReadParticipants", compact('group_guid', 'message_id'));
}

public function forwardMessages($from, $to, $message_ids){
return connection::run("forwardMessages", ["from_object_guid" => $from, "to_object_guid" => $to, "message_ids" => $message_ids, "rnd" => (string) random_int(12332, 987889)]);
}

public function sendGifByInfo($object_guid, $file_id, int $dc_id, $hash, $caption = "", int $size = 0, int $time = 0, int $height = 0, int $width = 0, $file_name = "SanfBot", $thumb = '')
{
$thumb = !empty($thumb) ? $thumb : "/9j/4AAQSkZJRgABAQAAAQABAAD/4gJASUNDX1BST0ZJTEUAAQEAAAIwAAAAAAIQAABtbnRyUkdC\nIFhZWiAAAAAAAAAAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAA\nAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlk\nZXNjAAAA8AAAAHRyWFlaAAABZAAAABRnWFlaAAABeAAAABRiWFlaAAABjAAAABRyVFJDAAABoAAA\nAChnVFJDAAABoAAAAChiVFJDAAABoAAAACh3dHB0AAAByAAAABRjcHJ0AAAB3AAAAFRtbHVjAAAA\nAAAAAAEAAAAMZW5VUwAAAFgAAAAcAHMAUgBHAEIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\nAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFhZWiAA\nAAAAAABvogAAOPUAAAOQWFlaIAAAAAAAAGKZAAC3hQAAGNpYWVogAAAAAAAAJKAAAA+EAAC2z3Bh\ncmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABYWVogAAAAAAAA9tYAAQAAAADT\nLW1sdWMAAAAAAAAAAQAAAAxlblVTAAAAOAAAABwARwBvAG8AZwBsAGUAIABJAG4AYwAuACAAMgAw\nADEANgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP/bAEMAHhQWGhYTHhoYGiEfHiMsSjAsKSksW0FE\nNkprXnFvaV5oZnaFqpB2fqGAZmiUypahsLW/wL9zjtHgz7neqru/t//bAEMBHyEhLCcsVzAwV7d6\naHq3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t//AABEI\nABkALQMBIgACEQEDEQH/xAAZAAADAQEBAAAAAAAAAAAAAAABAgMEAAX/xAAqEAABAwMCBAUFAAAA\nAAAAAAABAAIDBBESITETM0FhMjRxgbEFIkJRwf/EABYBAQEBAAAAAAAAAAAAAAAAAAIBA//EABYR\nAQEBAAAAAAAAAAAAAAAAAAABEf/aAAwDAQACEQMRAD8ARmLtTv0T00ZlqsRrYdEkj6YtvGyUW11I\nK6lla0OwuHOJvfsFKsUbIRI+RmVidPRMa3NjRJfEmxtfdSjabZNAN910URdMXHTHcftA9kDhD72n\n8Sle7EgAbIvkHEflle5+UOJTWGbJSezgP4tGbqenZGS6okLmDoNEagRyeUGo0sCtEfMClFyR6FS0\npGZ0lRE7F0RsQMi3X4Wim+pNhuySKRrSfFbqq0Xl2+/yVV3IYjKWBI6nnyLbO0vcFefJTgvNpQR2\nCrF4pvdZxuUtZ1//2Q==\n";
$meta = self::metaData($captipn);
$json = [
'object_guid' => $object_guid,
'rnd' => random_int(12321, 998877),
'text' => $meta['text'],
'file_inline' => [
'file_id' => $file_id,
'mime' => 'mp4',
'dc_id' => $dc_id,
'access_hash_rec' => $hash,
'file_name' => "$file_name.mp4",
'thumb_inline' => $thumb,
'width' => $width ? $width : 480,
'height' => $height ? $height : 272,
'time' => $time ? $time : 8000,
'size' => $size ? $size : 883078,
'type' => 'Gif']];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return connection::run("sendMessage", $json);
}

public function getStickersBySetIDs($sticker_set_ids){
return connection::run("getStickersBySetIDs", compact('sticker_set_ids'));
}

public function uploadAvatar($thumbnail_file_id, $main_file_id){
return connection::run("uploadAvatar", compact('thumbnail_file_id', 'main_file_id'));
}

public function addChannel($title, $channel_type = 'Private', $member_guids = null){ /*Public, Private*/
return connection::run("addChannel", compact('title', 'channel_type', 'member_guids'));
}

public function setBlockUser($user_guid, $action){ /*Block, Unblock*/
return connection::run("setBlockUser", compact('user_guid', 'action'));
}

public function setPinMessage($object_guid, int $message_id, $action){ /*Pin, Unpin*/
return connection::run("setPinMessage", compact('object_guid', 'message_id', 'action'));
}

public function deleteUserChat($user_guid, $last_deleted_message_id = 0){
return connection::run("deleteUserChat", compact('user_guid', 'last_deleted_message_id'));
}

public function getPendingObjectOwner($object_guid){
return connection::run("getPendingObjectOwner", compact('object_guid'));
}

public function actionOnJoinRequest($object_guid, $user_guid, $object_type = 'Group', $action = 'Accept'){ /*Group, Channel*//*Accept, Reject*/
return connection::run("actionOnJoinRequest", compact('object_guid', 'user_guid', 'object_type', 'action'));
}

public function createJoinLink($group_guid, $title, $request_needed = true, int $usage_limit = 0, int $time = 0){
$json = [
"object_guid" => $group_guid,
"title" => $title,
"request_needed" => $request_needed,
"usage_limit" => $usage_limit];
$time === 0 ? null : $json["expire_time"] = $time;
return connection::run("createJoinLink", $json);
}

public function getJoinLinks($object_guid){
return connection::run("getJoinLinks", compact('object_guid'));
}

public function livePlayer($path, $stream_Url, $stream_Key, $rotation = false){
$transpose = $rotation === false ?: " -vf transpose=$rotation";
$command = "ffmpeg -re -i {$path} -b:v 1200k -c:v libx264 -preset fast -g 50$transpose -c:a aac -b:a 128k -f flv {$stream_Url}{$stream_Key}";
exec($command);
}

public function checkUserUsername($username){
return connection::run("checkUserUsername", compact('username'));
}

public function updateUsername($username){
return connection::run("updateUsername", compact('username'));
}

public function getJoinRequests($object_guid){
return connection::run("getJoinRequests", compact('object_guid'));
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
return connection::run("setSetting", $json);
}

public function channelPreviewByJoinLink($link){
return connection::run("channelPreviewByJoinLink", compact('hash_link'));
}

public function groupPreviewByJoinLink($link){
return connection::run("groupPreviewByJoinLink", compact('hash_link'));
}

public function getCommonGroups($user_guid){
return connection::run("getCommonGroups", compact('user_guid'));
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
return connection::run("getTranscription", compact('message_id', 'transcription_id'));
}

public function transcribeVoice($object_guid, $message_id){
return connection::run("transcribeVoice", compact('object_guid', 'message_id'));
}

}
