<?php
class qa_html_theme_layer extends qa_html_theme_base {
  public function main() {
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'messages') {
      // $template = file_get_contents(CML_DIR . '/messages-template.html');
      // $this->output($template);
      $messages = array();
      // print_r($this->content['message_list']['messages']);
      foreach ($this->content['message_list']['messages'] as $message) {
        $temp = array();
        $temp['content'] = $message['content'];
        $temp['avatarblobid'] = $message['raw']['fromavatarblobid'];
        $temp['messageurl'] = '/message/'.$message['raw']['fromhandle'];
        $temp['handle'] = $message['raw']['fromhandle'];
        $messages[] = $temp;
      }
      $path = CML_DIR . '/messages-template.html';
      include $path;
    } else if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'message') {
      $template = file_get_contents(CML_DIR . '/message-template.html');
      $this->output($template);
    } else {
      qa_html_theme_base::main();
    }
  }
  public function nav($navtype, $level=null)
	{
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'messages') {
      if ($navtype === 'sub') {
        unset($this->content['navigation']['sub']);
      }
    }
    qa_html_theme_base::nav($navtype, $level);
  }
}
