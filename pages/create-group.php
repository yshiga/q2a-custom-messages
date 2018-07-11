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

$userids[] = qa_get_logged_in_userid();

// グループの存在チェック
$ret = msg_groups::already_existing($userids);
if ($ret) {
    $url = 'groupmsg/'.$ret;
} else {
    $gcount = msg_groups::get_groups_count();

    $group = new msg_groups();

    $group->title = 'グループチャット '.++$gcount;
    $group->create();

    foreach ($userids as $userid) {
        if ($userid === qa_get_logged_in_userid()) {
            $join = $group::GROUP_MSG_JOIN;
        } else {
            $join = $group::GROUP_MSG_INVITE;
        }
        $group->add_user($userid, $join);
    }
    $url = 'groupmsg/'.$group->groupid;
}
qa_redirect($url, null, qa_opt('site_url'));

return null;