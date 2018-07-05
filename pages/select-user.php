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
if ($loginFlags & QA_USER_FLAGS_NO_MESSAGES) {
    $alluser = qa_path('users', null, qa_opt('site_url'));
    $header_note = qa_lang_sub('custom_messages/header_note_all', $alluser);
    $users = select_interaction_users($loginUserId);
} else {
    $account = qa_path('account', null, qa_opt('site_url'));
    $header_note = qa_lang_sub('custom_messages/header_note', $account);
    $users = select_follow_each_other($loginUserId);
}
$qa_content['list'] = array(
    'note' => $header_note,
    'users' => $users
);
return $qa_content;


function select_follow_each_other($userid)
{
    $sql = '';
    $sql.= 'SELECT u.userid, handle, avatarblobid,';
    $sql.= ' content as location';
    $sql.= ' FROM ^users u';
    $sql.= ' LEFT JOIN (';
    $sql.= '     SELECT userid, content';
    $sql.= '     FROM ^userprofile';
    $sql.= "     WHERE title like 'location'";
    $sql.= ' ) p ON u.userid = p.userid';
    $sql.= ' WHERE u.userid IN (';
    $sql.= '     SELECT entityid';
    $sql.= '     FROM ^userfavorites';
    $sql.= '     WHERE userid = $';
    $sql.= "     AND entitytype = 'U'";
    $sql.= '     AND entityid IN (';
    $sql.= '         SELECT userid';
    $sql.= '         FROM ^userfavorites';
    $sql.= '         WHERE entityid = $';
    $sql.= "         AND entitytype = 'U'";
    $sql.= '))';

    return qa_db_read_all_assoc(qa_db_query_sub($sql, $userid, $userid));

}

function get_interaction_uesrs_sql()
{
    $sql = '';
    $sql.= "SELECT userid";
    $sql.= " FROM ^users";
    $sql.= " WHERE userid IN (";
    // 投稿に回答、または回答にコメントしてくれたユーザー
    $sql.= " SELECT userid";
    $sql.= " FROM ^posts";
    $sql.= " WHERE parentid IN (";
    $sql.= "    SELECT postid";
    $sql.= "    FROM ^posts";
    $sql.= "    WHERE userid = $";
    $sql.= "    AND type IN ('Q', 'A')";
    $sql.= " )";
    $sql.= " AND created > DATE_SUB(NOW(), INTERVAL 30 DAY)";
    // 飼育日誌にコメントしてくれたユーザー
    $sql.= " UNION";
    $sql.= " SELECT userid";
    $sql.= " FROM ^blogs";
    $sql.= " WHERE parentid IN (";
    $sql.= "    SELECT postid";
    $sql.= "    FROM ^blogs";
    $sql.= "    WHERE userid = $";
    $sql.= "    AND type = 'B'";
    $sql.= " )";
    $sql.= " AND created > DATE_SUB(NOW(), INTERVAL 30 DAY)";
    // 回答した質問、コメントした回答を投稿したユーザー
    $sql.= " UNION";
    $sql.= " SELECT userid";
    $sql.= " FROM ^posts";
    $sql.= " WHERE postid IN (";
    $sql.= "    SELECT parentid";
    $sql.= "    FROM ^posts";
    $sql.= "    WHERE userid = $";
    $sql.= "    AND type IN ('C', 'A')";
    $sql.= "    AND created > DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $sql.= " )";
    // コメントした飼育日誌を投稿したユーザー
    $sql.= " UNION";
    $sql.= " SELECT userid";
    $sql.= " FROM ^blogs";
    $sql.= " WHERE postid IN (";
    $sql.= "    SELECT parentid";
    $sql.= "    FROM ^blogs";
    $sql.= "    WHERE userid = $";
    $sql.= "    AND type = 'C'";
    $sql.= "    AND created > DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $sql.= " )";
    $sql.= " )";
    $sql.= " AND userid != $";
    $sql.= " ORDER BY created DESC";
    return $sql;
}

function get_recent_send_message_users_sql()
{
    $sql = '';
    $sql.= "SELECT DISTINCT touserid as userid";
    $sql.= " FROM ^messages";
    $sql.= " WHERE type = 'PRIVATE'";
    $sql.= " AND fromuserid = $";
    $sql.= " ORDER BY created DESC";
    $sql.= " LIMIT 5";
    return $sql;
}


function select_interaction_users($userid)
{
    // 回答やコメント、飼育日誌でやり取りしたユーザー
    $sql = get_interaction_uesrs_sql();
    $interaction_users = qa_db_read_all_values(qa_db_query_sub($sql, $userid, $userid, $userid, $userid, $userid));
    // 最近メッセージを送ったユーザー
    $sql2 = get_recent_send_message_users_sql();
    $send_message_users = qa_db_read_all_values(qa_db_query_sub($sql2, $userid));

    $userids = array_unique(array_merge($interaction_users, $send_message_users));

    // 該当ユーザーのうち「相互フォローしているユーザーとのみメッセージをやり取りする」ユーザーを除外
    $sql = '';
    $sql.= 'SELECT u.userid, handle, avatarblobid,';
    $sql.= ' content as location';
    $sql.= ' FROM ^users u';
    $sql.= ' LEFT JOIN (';
    $sql.= '     SELECT userid, content';
    $sql.= '     FROM ^userprofile';
    $sql.= "     WHERE title like 'location'";
    $sql.= ' ) p ON u.userid = p.userid';
    $sql.= ' WHERE u.userid IN ($)';
    $sql.= ' AND u.flags & #';

    return qa_db_read_all_assoc(qa_db_query_sub($sql, $userids, QA_USER_FLAGS_NO_MESSAGES));
}