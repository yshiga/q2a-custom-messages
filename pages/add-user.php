<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
}

require_once CML_DIR.'/model/msg-groups.php';

$userids = qa_post_text('userids');
$groupid = qa_request_part(1);

var_export($userids);
var_export($groupid);

if(empty($userids) || empty($groupid)) {
    $url = 'groupmsg/'.$groupid;
    qa_redirect($url, null, qa_opt('site_url'));
}

$loginuserid = qa_get_logged_in_userid();

// グループ生成
$group = new msg_groups($groupid);
$url = 'groupmsg/'.$group->groupid;

// グループにユーザーを追加
foreach ($userids as $userid) {
    $join = $group::GROUP_MSG_JOIN;
    $group->add_user($userid, $join);
    
    qa_report_event('g_invite', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(),
    array(
        'groupid' => $group->groupid,
        'userid' => $userid
    ));
}
$url = 'groupmsg/'.$groupid;
qa_redirect($url, null, qa_opt('site_url'));

return null;