<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
}

require_once QA_INCLUDE_DIR.'db/selects.php';
require_once QA_INCLUDE_DIR.'app/users.php';
require_once QA_INCLUDE_DIR.'app/format.php';
require_once QA_INCLUDE_DIR.'app/limits.php';
require_once CML_DIR.'/model/msg-groups.php';

$groupid = qa_request_part(1);
$loginuserid = qa_get_logged_in_userid();
$loginhandle = qa_get_logged_in_handle();

$qa_content = qa_content_prepare();

//    Check we have a handle, we're not using Q2A's single-sign on integration and that we're logged in

if (!strlen($groupid)) {
    return include QA_INCLUDE_DIR.'qa-page-not-found.php';
}

if (!isset($loginuserid)) {
    $qa_content['error'] = qa_insert_login_links(qa_lang_html('main/view_q_must_login'), qa_request());
    return $qa_content;
}

$current_group = new msg_groups($groupid);
// 招待されていない場合はページNOT FOUND
if (!$current_group->is_invited($loginuserid)) {
    return include QA_INCLUDE_DIR.'qa-page-not-found.php';
}
// すでに参加済みの場合グループチャットページに移動
if ($current_group->allow_browse($loginuserid)) {
    $url = 'groupmsg/'.$groupid;
    qa_redirect($url, null, qa_opt('site_url'));
    return null;
}

$state= qa_get_state();
if ($state === 'accept') {
    $html = '<p>参加するを押しました!</p>';
    $current_group->update_user($loginuserid, msg_groups::GROUP_MSG_JOIN);
    $url = 'groupmsg/'.$groupid;
    qa_redirect($url, null, qa_opt('site_url'));
    return null;
} elseif ($state === 'cancel') {
    $html = '<p>参加しないを押しました!</p>';
    $current_group->remove_user($loginuserid);
    $top = qa_opt('site_url');
    $html.= '<a href="'.$top.'">トップページへ</a><br>';
    $message_box = qa_path('messages', null, qa_opt('site_url'));
    $html.= '<a href="'.$message_box.'">メセージボックスへ</a>';
} else {
    $html = '<p>グループチャットに招待されました</p>';
}
$html.= get_msg_invitation_buttons();
$qa_content['custom'] = $html;

return $qa_content;

function get_msg_invitation_buttons()
{
    $html =<<<EOF
<div class="mdl-grid">
    <div class="mdl-cell mdl-cell--6-col">
        <a href="?state=accept" class="mdl-button mdl-button--primary mdl-button--raised mdl-color-text--white mdl-button--large margin--bottom-0 mdl-button__block">
            <i class="material-icons">group_add</i> 参加する </a>
    </div>
    <div class="mdl-cell mdl-cell--6-col">
        <a href="?state=cancel" class="mdl-button mdl-button--accent mdl-button--raised mdl-color-text--white mdl-button--large margin--bottom-0 mdl-button__block">
            <i class="material-icons">cancel</i> 参加しない </a>
    </div>
</div>
EOF;
    return $html;
}

/*
Omit PHP closing tag to help avoid accidental output
*/
