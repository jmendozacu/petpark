<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PHPClass
 *
 * @author Ondřej Kohut
 */
class Zebu_Tools_ErrorCollector {
    static $messages = array();//'';
    //static $message_count = 0;
    static $from_email = 'test1@zebu.cz';
    static $from_name = 'Error Collector';

    static $to_email = 'ondrej.kohut@zebu.cz';
    static $to_name = '';

    static $user = 'info@epublish.cz';
    static $password = 'UntJE2GbnXzwS8rm';

    static $subject = 'Snakup.cz - error report';

    static $log_file = 'var/log/import.log';
    
    public static function import_messages($messages){
        foreach($messages as $msg)
           self::$messages[] = $msg; 
    }

    public static function get_messages(){
        return self::$messages;
    }

    public static function add_message($message)
    {
       // self::$message_count++;
        //$NL = html_entity_decode('&#xa;');
        //self::$error_buffer .= '['.date("H:i:s").'] '.$message + $NL;
        self::$messages[] = '  ['.date("H:i:s").'] '.$message;
    }

    
    public static function print_error_messages()//$is_html = false)
    {
        //$NL = ($is_html)?'<br />':html_entity_decode('&#xa;');
        $NL = (Zebu_Auxiliary::is_HTML())?'<br />':html_entity_decode('&#xa;');
        Zebu_Auxiliary::info_message('E R R O R  message count: ' . count(self::$messages).$NL.//);//self::$message_count . $NL;
        //Zebu_Auxiliary::info_message(
            join($NL, self::$messages));// self::$error_buffer;
    }


    public static function log_error_messages($label = '', $filename = null)
    {
        if (!isset($filename))
            $filename = self::$log_file;
        //$NL = '\n';
        $NL = html_entity_decode('&#xa;');
        $data = $label.': ['.date('Y-m-d, H:i:s').']'. $NL .'Error messages: './/$NL.
            //' - Error messages: ' . count(self::$messages) . $NL .
            count(self::$messages) . $NL .
            join($NL, self::$messages)
            .$NL.$NL;
        
        
        file_put_contents($filename, $data, FILE_APPEND);
        
    }

    public static function get_error_messages_count(){
        return count(self::$messages);
    }

  /*  public static function log_and_send_error_messages_if_exist(){
        if (count(self::$messages)>0){
            self::log_error_messages();
            self::send_error_messages();
        }
    }*/

    public static function log_and_send_error_messages($label = '', $log_and_send_also_no_error = false){
        if (!$log_and_send_also_no_error && self::get_error_messages_count()==0)
            return;
            
        self::log_error_messages($label);
        self::send_error_messages($label);
    }

    public static function send_error_messages($label = '')
    {

         $title =  (!empty($label))
            ? $label.': ' : '';

         $subject =  (!empty($label))
            ? ' ['.$label.']' : '';
/*
        $config = array('auth' => 'login',
                        'username' => self::$user, //'test1@zebu.cz',
                        'password' => self::$password, //'testicek',
                        'port' => 25);

        $transport = new Zend_Mail_Transport_Smtp('smtp.zebu.org', $config);

*/
        $NL = '<br />';
        $mail = new Zend_Mail();

        //$mail->setBodyText(
        $mail->setBodyHtml( //date("j.n.Y, H:i:s")
            '<h3>'.$title.'Error report ['.date('Y-m-d, H:i:s').']</h3>'.
            'Error message count: ' . count(self::$messages) . '<br />' .
            join($NL, self::$messages));//self::$error_buffer);
        $mail->setFrom(self::$from_email, self::$from_name);
        $mail->addTo(self::$to_email, self::$to_name);
        $mail->setSubject(self::$subject.$subject);
        $mail->send();//$transport);
    }
}
?>
