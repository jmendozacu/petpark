<?php

//define ('INFO_FORMAT','TEXT');
//define ('INFO_FORMAT','HTML');

/**
 * simple auxiliary class to wrap string by object which enable save other values
 * 
 */
class obj{
    protected $value;
    function  __construct($value, $parameters = array()) {
        $this->value = $value;
        foreach($parameters as $key => $val){
            $this->$key = $val;
        }
    }

    public function __toString(){
        return (string)$this->value;
    }
}

class Zebu_Auxiliary {
    private static $info_format = 'TEXT';//'HTML'; // 'TEXT';//


    private static $timers = array(0=>0);
    private static $timers_log = array();
    private static $is_timers_log = false; 
    ///private static $timer;
    protected static $output;
    protected static $buffer = '';
    protected static $is_buffer_used = false;
    
    public static function set_timers_log($is_timers_log = true){
      self::$is_timers_log = $is_timers_log;
    } 

   /* public static function get_times($name = 0){
      if (!isset(self::$timers_log[$name])) return -1;
      
      return self::$timers_log[$name];
    }*/

    public static function print_timers_info(){
      foreach(self::$timers_log as $name => $times){
        //self::info_variable($times);
        self::info_message('Time for '.$name.': '.self::get_total_time($name).' s, avg = '.(self::get_total_time($name)/count($times)).' s, done: '.count($times).' times',1);
      }
    }

    public static function get_times($name = 0){
      if (!isset(self::$timers_log[$name])) return false;
      
      return self::$timers_log[$name];
    }
    
    public static function get_total_time($name = 0){
      if (!isset(self::$timers_log[$name])) return -1;
      
      return array_sum(self::$timers_log[$name]);
    }

    /**
     * Vsechny nasledujici vypisy budou automaticky do souboru misto na klasicky vystup
     *
     * @param <string> $filename
     */
    public static function set_output_redirect($filename = null){
        self::$output = $filename;
    }

    /**
     * Vsechny nasledujici vypisy budou automaticky do bufferu misto na klasicky vystup
     */
    public static function set_output_redirect_to_buffer($is_buffer_used = true){
        self::$is_buffer_used = $is_buffer_used;
    }

    public static function get_buffer(){
        return self::$buffer;
    }

    public static function clear_buffer(){
        return self::$buffer = '';
    }

    public static function set_HTML_info($is_html = true) {
        self::$info_format = ($is_html)?'HTML':'TEXT';
    }

    public static function is_HTML(){
        return (self::$info_format == 'HTML');
    }
    
    public static function start_info_timer($name){
        self::info_message($name.' starting...');
        self::start_timer($name);
    }

    public static function stop_info_timer($name){
        self::info_message($name.' complete ['.self::stop_timer($name).' s]');
    }





        
    private static function log_time($name, $time_dif){
      if (!isset(self::$timers_log[$name]))
          self::$timers_log[$name] = array();
      self::$timers_log[$name][] = $time_dif;
    }
        
    public static function start_timer($name=0) {
        self::$timers[$name] = microtime(true); //time();
    }

    public static function stop_timer($name=0) {
        if (!isset(self::$timers[$name])) return -1;
        //echo microtime().' - '.self::$timers[$name].'<br/>';
        $timeDif = microtime(true) - self::$timers[$name]; //time() - self::$timers[$name];
        if (self::$is_timers_log)
            self::log_time($name,$timeDif);
        return round($timeDif,3);
    }

    public static function info_message_pre($message, $relevance=0,$filename=null) {
        self::info_message('<pre>'.$message.'</pre>', $relevance, $filename);
    }

    public static function info_variable($message, $relevance=0,$filename=null) {
        self::info_message_pre(print_r($message,true), $relevance, $filename);
    }

    public static function info_variable_specialchars($message, $relevance=0,$filename=null) {
        self::info_message_pre(htmlspecialchars(print_r($message,true)), $relevance, $filename);
    }

    public static function info_message_specialchars($message, $relevance=0,$filename=null) {
        self::info_message(htmlspecialchars($message), $relevance, $filename);
    }

    public static function info_message($message, $relevance=0,$filename=null) {
        if (!isset($relevance) || $relevance<0) $relevance = 0;
        
        $mem = sprintf(' [%10.2f MB ]',round((memory_get_usage() / (1024*1024)),2));
            ////' ['.round((memory_get_usage() / (1024*1024)),2).' MB ]';

        if (self::$info_format=='HTML') {
            $message_colors = array(
                0 => '#EEFFEE',
                1 => '#EEF8AA',
                2 => '#EEDDAA',
            );

            //$NL = html_entity_decode('&#xa;');
            //$NL = '<br/ >';

            if ($relevance>=count($message_colors)) $relevance = count($message_colors)-1;

            $color = $message_colors[$relevance];
            //echo '['.date("H:m:s").']&nbsp;&nbsp;&nbsp;'.$message.$NL;
            $output = '<div style="background-color: '.$color.'; padding:2px; margin-bottom:1px; font-family:verdana;font-size:8pt;border-width:1px;border-style:solid;border-right-color:#448844;border-bottom-color:#448844;border-left-color:#AACCAA;border-top-color:#AACCAA;">'.
                '['.date("H:i:s").']'.$mem.'&nbsp;&nbsp;&nbsp;'.$message.
                '</div>';
        }
        else {
            $NL = html_entity_decode('&#xa;');
            $SPACE = ' ';
            $MAX_RELEVANCE = 3;
            //$SPACE = '&nbsp;';
            //$NL = '<br/ >';
            if ($relevance>$MAX_RELEVANCE) $relevance = $MAX_RELEVANCE;
            $output =  '['.date("Y-m-d H:i:s").']'.$mem.

                str_repeat($SPACE, $MAX_RELEVANCE-$relevance).
                str_repeat('!', $relevance).
                $SPACE.
                $message.$NL;
        }

        if (isset($filename)) {
            file_put_contents($filename, $output, FILE_APPEND);
        } else {
            if (isset(self::$output)){
                file_put_contents(self::$output, $output, FILE_APPEND);
            }
            else {
                if (self::$is_buffer_used){
                    self::$buffer .= $output;
                }else{
                    echo $output;
                    /*if ($_SERVER['HTTP_HOST'] != '127.0.0.1')
                        ob_flush();*/
                    flush();
                }
            }
            

        }
    }

    public static function decode($string) {
    //[~]nbsp;    [#~p~#]
        $string = str_replace('[#~', '&lt;', $string);
        $string = str_replace('~#]', '&gt;', $string);
        $string = str_replace('[~]', '&', $string);
        return $string;
    }

    public static function quote($string) {
        return '"'.addslashes(trim($string)).'"';
    }
    
        /**
     * @deprecated
     */ 
    public static function startTimer($name=0) {
      return self::start_timer($name);
    }
    
    /**
     * @deprecated
     */         
    public static function stopTimer($name=0) {
      return self::stop_timer($name);
    }
}

Zebu_Auxiliary::set_HTML_info(isset($_REQUEST['html']));

?>