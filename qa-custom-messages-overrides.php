<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

function qa_page_routing()
{
  $routing = qa_page_routing_base();
  if (qa_opt('site_theme') === CML_TARGET_THEME_NAME) {
    $routing['messages'] = CML_RELATIVE_PATH . 'pages/messages.php';
  }
  return $routing;
}
