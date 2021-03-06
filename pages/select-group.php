<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
}

qa_set_template( 'messages-select-group' );
$qa_content = qa_content_prepare();
$qa_content['title'] = qa_lang_html( 'custom_messages/messages_page_title' );
$loginFlags = qa_get_logged_in_flags();
$header_note = qa_lang_html('custom_messages/select_group_users');

$loginUserId = qa_get_logged_in_userid();
$users = cml_db_client::select_recent_message_users($loginUserId);
$qa_content['list'] = array(
    'note' => $header_note,
    'users' => $users
);
return $qa_content;