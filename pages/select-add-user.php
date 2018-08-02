<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
}

require_once CML_DIR.'/model/msg-groups.php';

qa_set_template( 'messages-select-add-user' );
$qa_content = qa_content_prepare();
$qa_content['title'] = qa_lang_html( 'custom_messages/messages_page_title' );
$loginFlags = qa_get_logged_in_flags();
$header_note = qa_lang_html('custom_messages/select_add_users');

$groupid = qa_get('groupid');
$current_group = new msg_groups($groupid);
$loginUserId = qa_get_logged_in_userid();
$users = cml_db_client::select_recent_message_users($loginUserId, $current_group->all_users);
$qa_content['list'] = array(
    'note' => $header_note,
    'users' => $users
);
$qa_content['groupid'] = $groupid;
$qa_content['group_user_count'] = count($current_group->all_users);
return $qa_content;