<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qa_group_message_page
    {

        private $directory;
        private $urltoroot;

        public function load_module( $directory, $urltoroot )
        {
            $this->directory = $directory;
            $this->urltoroot = $urltoroot;
        }

        public function match_request( $request )
        {
            return ( qa_request_part(0) === 'groupmsg');
        }

        public function process_request( $request )
        {
            qa_set_template( 'groupmsg' );

            return require CML_DIR . '/pages/group-msg.php';
        }
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
