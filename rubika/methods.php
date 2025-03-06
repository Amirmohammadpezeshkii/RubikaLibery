<?php
namespace rubi;
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
return connection::run('sendMessage', $json);
}

public function editMessage($object_guid, $message_id, $text){
$meta = self::metaData($text);
$json = [
'object_guid' => $object_guid,
'message_id' => $message_id,
'text' => $meta['text']];
if(count(($meta['data']['meta_data_parts'] ?? [])) > 0)
$json['metadata'] = $meta['data'];
return connection::run('editMessage', $json);
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
'sort' => $sort];
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

public function searchMemberGroup($group_guid, $search_text){
return connection::run("getGroupAllMembers", compact('group_guid', 'search_text'));
}

public function getGroupMessageReadParticipants($group_guid, int $message_id){
return connection::run("getGroupMessageReadParticipants", compact('group_guid', 'message_id'));
}

public function forwardMessages($from, $to, $message_ids){
return connection::run("forwardMessages", ["from_object_guid" => $from, "to_object_guid" => $to, "message_ids" => $message_ids, "rnd" => (string) random_int(12332, 987889)]);
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

public static function livePlayer($path, $stream_Url, $stream_Key, $rotation = false){
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
$api = connection::sendFileToAPI($path);
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
return connection::run('sendMessage', $json);
}

public function sendDocument($object_guid, $reply_to_message_id, $path, $caption = null){
$api = connection::sendFileToAPI($path);
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
return connection::run('sendMessage', $json);
}


}
