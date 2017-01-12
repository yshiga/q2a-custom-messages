<?php
class qa_html_theme_layer extends qa_html_theme_base {
  public function main_parts($content) {
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'messages') {
      // $template = file_get_contents(CML_DIR . '/messages-template.html');
      // $this->output($template);
      $messages = $content['message_list']['messages'];
      $path = CML_DIR . '/messages-template.html';
      include $path;
    // } else if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'message') {
    //   $template = file_get_contents(CML_DIR . '/message-template.html');
    //   $this->output($template);
    } else {
      qa_html_theme_base::main_parts($content);
    }
  }
  
  public function page_title_error() {
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'messages') {
    }else {
      qa_html_theme_base::page_title_error();
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
