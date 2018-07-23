<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
}

qa_set_template( 'messages-select-user' );
$qa_content = qa_content_prepare();
$qa_content['title'] = qa_lang_html( 'custom_messages/messages_page_title' );
$loginFlags = qa_get_logged_in_flags();
$header_note = '';
if (!($loginFlags & QA_USER_FLAGS_NO_MESSAGES)) {
    $alluser = qa_path('users', null, qa_opt('site_url'));
    $header_note = qa_lang_sub('custom_messages/header_note_all', $alluser);
    $users = cml_db_client::select_interaction_users($loginUserId);
} else {
    $account = qa_path('account', null, qa_opt('site_url'));
    $header_note = qa_lang_sub('custom_messages/header_note', $account);
    $users = cml_db_client::select_follow_each_other($loginUserId);
}
$qa_content['list'] = array(
    'note' => $header_note,
    'users' => $users
);
return $qa_content;

