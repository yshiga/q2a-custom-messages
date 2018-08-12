<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
}

require_once CML_DIR.'/model/msg-groups.php';

$userids = qa_post_text('userids');
if(empty($userids)) {
    qa_redirect('messages');
}

$loginuserid = qa_get_logged_in_userid();
$userids[] = $loginuserid;

// グループの存在チェック
$ret = msg_groups::already_existing($userids);
if ($ret) {
    $url = 'groupmsg/'.$ret;
} else {
    $gcount = msg_groups::get_groups_count();

    $group = new msg_groups();

    $group->title = '';
    $group->create();

    foreach ($userids as $userid) {
        $join = $group::GROUP_MSG_JOIN;
        $group->add_user($userid, $join);
        
        if ($userid !== $loginuserid) {
            qa_report_event('g_invite', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(),
            array(
                'groupid' => $group->groupid,
                'userid' => $userid
            ));
        }
    }
    $url = 'groupmsg/'.$group->groupid;
}
qa_redirect($url, null, qa_opt('site_url'));

return null;