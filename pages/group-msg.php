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
require_once CML_DIR.'/model/msg-group-messages.php';

$groupid = qa_request_part(1);
$loginuserid = qa_get_logged_in_userid();
$loginhandle = qa_get_logged_in_handle();

if (empty($groupid)) {
    return include CML_DIR . '/pages/create-group.php';
}

$qa_content = qa_content_prepare();

//    Check we have a handle, we're not using Q2A's single-sign on integration and that we're logged in

if (!strlen($groupid))
    qa_redirect('messages');

$current_group = new msg_groups($groupid);

if (!isset($loginuserid)) {
    $qa_content['error'] = qa_insert_login_links(qa_lang_html('misc/message_must_login'), qa_request());
    return $qa_content;
}

// グループ閲覧権限チェック
if ( !$current_group->allow_browse($loginuserid)) {
    return include QA_INCLUDE_DIR.'qa-page-not-found.php';
}

// グループ内メッセージの取得
$recent = qa_db_select_with_pending(
    msg_group_messages::recent_messages_selectspec($groupid)
);

//    Process sending a message to Group
// check for messages or errors
$state = qa_get_state();
$messagesent = $state == 'message-sent';
if ($state == 'email-error')
    $pageerror = qa_lang_html('main/email_error');

if (qa_post_text('domessage')) {
    
    qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);
    
    $inmessage = strip_spaces($in['content']);
    
    if (isset($pageerror)) {
        // not permitted to post, so quit here
        $qa_content['error'] = $pageerror;
        return $qa_content;
    }

    if ( !qa_check_form_security_code('group-message-'.$groupid, qa_post_text('code')) )
        $pageerror = qa_lang_html('misc/form_security_again');

    else {
        if (empty($inmessage))
            $errors['message'] = qa_lang('misc/message_empty');

        if (empty($errors)) {
            require_once QA_INCLUDE_DIR.'db/messages.php';
            require_once QA_INCLUDE_DIR.'app/emails.php';

            if (qa_opt('show_message_history')) {
                $messageid = msg_group_messages::create_message($groupid, $loginuserid, $inmessage, 'html');
            } else {
                $messageid = null;
            }
            $messagesent = true;

            $join_users = array_diff($current_group->join_users, array($loginuserid));
            qa_report_event('g_message', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), array(
                'groupid' => $groupid,
                'messageid' => $messageid,
                'message' => $inmessage,
                'join_users' => $join_users
            ));

            // show message as part of general history
            if (qa_opt('show_message_history'))
                qa_redirect(qa_request(), array('state' => ($messagesent ? 'message-sent' : 'email-error')));
        }
    }
}


//    Prepare content for theme
$start = qa_get_start();
$pagesize = qa_opt('page_size_pms');

// $hideForm = !empty($pageerror) || $messagesent;
$hideForm = !empty($pageerror);

$qa_content['title'] = qa_lang_html('misc/private_message_title');

$qa_content['error'] = @$pageerror;

$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_qs');
$editor=qa_load_editor(@$in['content'], @$in['format'], $editorname);

$field=qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
// $field['label']=qa_lang_html_sub('misc/message_for_x', qa_get_one_user_html($handle, false));
$field['error']=qa_html(@$errors['content']);

$qa_content['form_message'] = array(
    'tags' => 'method="post" action="'.qa_self_html().'"',

    'style' => 'tall',

    'ok' => $messagesent ? qa_lang_html('misc/message_sent') : null,

    'fields' => array(
        'message' => $field,
    ),

    'buttons' => array(
        'send' => array(
            'tags' => 'onclick="qa_show_waiting_after(this, false); '. (method_exists($editor, 'update_script') ? $editor->update_script('content') : '') . '"',
            'label' => qa_lang_html('main/send_button'),
        ),
    ),

    'hidden' => array(
        'domessage' => '1',
        'code' => qa_get_form_security_code('group-message-'.$groupid),
    ),
);

$qa_content['focusid'] = 'message';

if ($hideForm) {
    unset($qa_content['form_message']['buttons']);

    if (qa_opt('show_message_history'))
        unset($qa_content['form_message']['fields']['message']);
    else {
        unset($qa_content['form_message']['fields']['message']['note']);
        unset($qa_content['form_message']['fields']['message']['label']);
    }
}


//    If relevant, show recent message history

if (qa_opt('show_message_history')) {
    $messagescount = count($recent);

    qa_sort_by($recent, 'created');

    $showmessages = array_slice(array_reverse($recent, true), $start, $pagesize);

    $qa_content['message_list']['chip'] = qa_lang('custom_messages/groupmsg_chip');

    $handles = array();
    foreach($current_group->all_users as $userid) {
        $handles[]= qa_userid_to_handle($userid);
    }
    $handlelinks = array();
    foreach ($handles as $handle) {
        $handlelinks[] = '<a href="'.qa_path_html('user/'.$handle).'">'.qa_html($handle).'</a>';
    }
    $qa_content['message_list']['title'] = qa_lang_html_sub('misc/message_recent_history', implode('、', $handlelinks));

    $qa_content['message_list']['messages'] = array();
    if (count($showmessages)) {
        $options = qa_message_html_defaults();

        foreach ($showmessages as $message) {
            $qa_content['message_list']['messages'][] = msg_group_messages::message_html_fields($message, $options);
        }
    }
    $qa_content['group_user_count'] = count($current_group->all_users);

    // $qa_content['navigation']['sub'] = qa_messages_sub_navigation();

    $qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $messagescount, qa_opt('pages_prev_next'));
}

return $qa_content;


function strip_spaces($content)
{
    
    $pat = '/<p class=""><br><\/p>/i';
    $pat2 = '/<p class="">(&nbsp;\s?)*<\/p>/i';

    $result = preg_replace($pat, "", $content);
    $result2 = preg_replace($pat2, "", $result);
    
    if (empty($result2)) {
        $return_content = "";
    } else {
        $return_content = $content;
    }
    return $return_content;
}

/*
Omit PHP closing tag to help avoid accidental output
*/
