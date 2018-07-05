<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
}

$groupid = qa_request_part(1);
if (empty($groupid)) {
    return include QA_INCLUDE_DIR.'qa-page-not-found.php';
}

$qa_content = qa_content_prepare();
$qa_content['title'] = 'グループメッセージ'; 
$qa_content['custom'] = '<p>groupid: '.$groupid.'</p>';

return $qa_content;