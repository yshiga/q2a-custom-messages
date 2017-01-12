<?php

  if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
  }

  require_once QA_INCLUDE_DIR.'db/selects.php';
  require_once QA_INCLUDE_DIR.'app/users.php';
  require_once QA_INCLUDE_DIR.'app/format.php';
  require_once QA_INCLUDE_DIR.'app/limits.php';
  require_once CML_DIR.'/cml-db-client.php';

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

  if (!qa_opt('allow_private_messages') || !qa_opt('show_message_history')) {
    return include QA_INCLUDE_DIR.'qa-page-not-found.php';
  }

//  Find the messages for this user

  $start = qa_get_start();
  $pagesize = qa_opt('page_size_pms');

  // get number of messages then actual messages for this page
  $userMessages = cml_db_client::get_user_messages($loginUserId);
  $count = count($userMessages);
  // TODO メッセージを$pagesizeに収める処理
  // array_slice ( array $array , int $offset [, int $length = NULL [, bool $preserve_keys = false ]] )
  $userMessages = array_slice($userMessages, $start, $pagesize);
//  Prepare content for theme

  $qa_content = qa_content_prepare();
  $qa_content['title'] = qa_lang_html( 'Private Messages' );
  $qa_content['script_rel'][] = 'qa-content/qa-user.js?'.QA_VERSION;

  $qa_content['message_list'] = array(
    'tags' => 'id="privatemessages"',
    'messages' => array(),
  );


  foreach ($userMessages as $message) {
    $msgFormat = array();
    if ($loginUserId === $message['tohandle']) {
      $replyHandle = $message['fromhandle'];
      $replyBlobid = $message['fromavatarblobid'];
      $replyLocation = $message['fromlocation'];
    } else {
      $replyHandle = $message['tohandle'];
      $replyBlobid = $message['toavatarblobid'];
      $replyLocation = $message['tolocation'];
    }
    $msgFormat['avatarblobid'] = $replyBlobid;
    $msgFormat['handle'] = $replyHandle;
    $msgFormat['location'] = $replyLocation;
    $create_date = new DateTime($message['created']);
    $msgFormat['create_date'] = $create_date->format('Y年m月d日');
    $msgFormat['content'] = $message['content'];
    $msgFormat['messageurl'] = qa_path_html('message/'.$replyHandle);    

    $qa_content['message_list']['messages'][] = $msgFormat;
  }

  $qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

  return $qa_content;
