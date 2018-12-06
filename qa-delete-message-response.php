<?php

class qa_delete_message_response {
    
    function match_request($request) {
        $parts = explode('/', $request);
        
        return $parts[0] == 'delete-message';
    }
    
    function process_request($request) {
        header ( 'Content-Type: application/json' );
        $ret_val = array ();
        $json_object = array ();

        try {
            $userid = qa_get_logged_in_userid();
            $messageid = qa_post_text('messageid');

            http_response_code ( 200 );
            
            $json_object['statuscode'] = '200';
            $json_object['message'] = 'ok';
            $json_object['content'] = 'messageid: '.$messageid;
        } catch (Exception $e) {
            http_response_code ( 500 );
            
            $json_object['statuscode'] = '500';
            $json_object['message'] = 'Internal Server Error';

            $json_object['detail'] = $e->getMessage();
        }
        
        echo json_encode ( $json_object, JSON_PRETTY_PRINT );

    }
    
}
