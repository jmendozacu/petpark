<?php

// http://www.php.net/manual/en/context.http.php
// udelat dve moznosti

class Zebu_Tools_RequestTools {

  private static $error;

  static function is_error()  { return empty(self::$error) ? false : true; }
  static function get_error() { return self::$error; }


  private static $timeout = 30;

  static function timeout($seconds = null) { return is_null($seconds) ? self::$timeout : self::$timeout = $seconds; }
  
  
  // v nastaveni timeoutu
  // timeout($seconds = null, $type = self::SECONDS)
  const SECONDS = 1;
  const MINUTES = 2;
  const HOURS   = 3;
  
  
  ////////////////////////////////////////
  // request
  ////////////////////////////////////////

  static function get($url) {
    return self::request($url);
  }
  
  
  static function post($url, $params) {
    if (is_array($params))
      $params = http_build_query($array);

    $curl_options = array(
      CURLOPT_POST       => true,
      CURLOPT_POSTFIELDS => $params
    );

    return self::request($url, $curl_options);
  }


  static function request($url, $curl_options = array()) {
    $curl_options[CURLOPT_URL]            = $url;
    $curl_options[CURLOPT_RETURNTRANSFER] = true;
    $curl_options[CURLOPT_TIMEOUT]        = self::$timeout;
    
    $ch = curl_init();
    curl_setopt_array($ch, $curl_options);
    $response = curl_exec($ch);
    
    if (intval(curl_errno($ch)) != 0)
      self::$error = curl_error($ch);
    
    curl_close($ch);

    return $response;
  }

}

?>
