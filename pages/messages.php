<?php

  if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
  }

  require_once QA_INCLUDE_DIR.'db/selects.php';
  require_once QA_INCLUDE_DIR.'app/users.php';
  require_once QA_INCLUDE_DIR.'app/format.php';
  require_once QA_INCLUDE_DIR.'app/limits.php';
  require_once CML_DIR.'cml-db-client.php';

  $loginUserId = qa_get_logged_in_userid();
  $loginUserHandle = qa_get_logged_in_handle();


//  Check which box we're showing (inbox/sent), we're not using Q2A's single-sign on integration and that we're logged in

  $req = qa_request_part(1);
  if ($req !== null) {
    return include QA_INCLUDE_DIR.'qa-page-not-found.php';
  }
    

  if (QA_FINAL_EXTERNAL_USERS)
    qa_fatal_error('User accounts are handled by external code');

  if (!isset($loginUserId)) {
    $qa_content = qa_content_prepare();
    $qa_content['error'] = qa_insert_login_links(qa_lang_html('misc/message_must_login'), qa_request());
    return $qa_content;
  }

  if (!qa_opt('allow_private_messages') || !qa_opt('show_message_history'))
    return include QA_INCLUDE_DIR.'qa-page-not-found.php';


//  Find the messages for this user

  $start = qa_get_start();
  $pagesize = qa_opt('page_size_pms');

  // get number of messages then actual messages for this page
  // $pmSpecCount = qa_db_selectspec_count( $func('private', $loginUserId, true) );
  // $pmSpec = $func('private', $loginUserId, true, $start, $pagesize);
  
  // TODO メッセージ取得処理
  $userMessages = get_user_messages($loguinUserid);
  // list($numMessages, $userMessages) = qa_db_select_with_pending($pmSpecCount, $pmSpec);
  // TODO メッセージ数取得
  $count = count($userMessages);
  // TODO メッセージを$pagesizeに収める処理
  // array_slice ( array $array , int $offset [, int $length = NULL [, bool $preserve_keys = false ]] )
//  Prepare content for theme

  $qa_content = qa_content_prepare();
  $qa_content['title'] = qa_lang_html( 'Private Messages' );
  $qa_content['script_rel'][] = 'qa-content/qa-user.js?'.QA_VERSION;

  $qa_content['message_list'] = array(
    'tags' => 'id="privatemessages"',
    'messages' => array(),
  );

  // $htmlDefaults = qa_message_html_defaults();
  // if ($showOutbox)
  //   $htmlDefaults['towhomview'] = true;

  foreach ($userMessages as $message) {
    $msgFormat = qa_message_html_fields($message, $htmlDefaults);
    $replyHandle = $showOutbox ? $message['tohandle'] : $message['fromhandle'];

    $qa_content['message_list']['messages'][] = $msgFormat;
  }

  $qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

  $qa_content['navigation']['sub'] = qa_messages_sub_navigation($showOutbox ? 'outbox' : 'inbox');

  return $qa_content;
