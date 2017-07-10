<?php

/**
 * 
 *
 * @author Luděk
 */
class Zebu_Tools_StringTools {

  private static $encoding = 'UTF-8';

  static function encoding($encoding = null) { return is_null($encoding) ? self::$encoding : self::$encoding = $encoding; }

  
  static function downcase($str)   { return mb_strtolower($str, self::$encoding); }
  static function upcase($str)     { return mb_strtoupper($str, self::$encoding); }
  static function length($str)     { return mb_strlen($str, self::$encoding); }
  static function capitalize($str) { if (self::length($str) > 0) return self::upcase(self::substr($str, 0, 1)).self::substr($str, 1); else return ''; }

  static function is_included($str, $search_str, $offset = 0) { return mb_strpos($str, $search_str, $offset, self::$encoding) !== false; }
  static function index($str, $search_str, $offset = 0)       { return mb_strpos($str, $search_str, $offset, self::$encoding); }
  static function rindex($str, $search_str, $offset = 0)      { return mb_strrpos($str, $search_str, $offset, self::$encoding); }

  
  static function substr($str, $start, $length = null) {
    if (is_null($length))
      return mb_substr($str, $start, self::length($str) - 1, self::$encoding);
    else
      return mb_substr($str, $start, $length, self::$encoding);

  }

  static function empty_chars_to_one_space($str) { return preg_replace('/ +/', ' ', preg_replace('/\s/', ' ', $str)); }

	
  static function get_seo($s)
	{
		$s = self::AutoCzech($s, 'asc');
		$s = strtolower($s);
		$s = preg_replace('~[^a-z0-9]~', '-', $s);
		$s = preg_replace('~\^~', '-', $s);
		$s = preg_replace('~-{2,}~', '-', $s);

		if (preg_match('~^.*-$~', $s))
			$s = substr($s, 0, strlen($s) - 1);

		if (preg_match('~^-.*$~', $s))
			$s = substr($s, 1, strlen($s));

		return $s;
	}
  
  
	// Funkce na prevod libovolneho ceskeho textu $str na kodovani $code
	// (puvodni kodovani neni nutne znat)
	static function AutoCzech($str, $code)
	{
		// Ceske znaky v ruznych kodovanich (ASCII, Win-1250, ISO 8859-2, UTF-8)
		$autocp['asc'] = array ('A','C','D','E','E','I','N','O','R','S','T','U','U','Y','Z',
		                        'a','c','d','e','e','i','n','o','r','s','t','u','u','y','z');
		$autocp['win'] = array ('Á','Č','Ď','É','Ě','Í','Ň','Ó','Ř','Š','Ť','Ú','Ů','Ý','Ž',
		                        'á','č','ď','é','ě','í','ň','ó','ř','š','ť','ú','ů','ý','ž');
		$autocp['iso'] = array ('Á','Č','Ď','É','Ě','Í','Ň','Ó','Ř','©','«','Ú','Ů','Ý','®',
		                        'á','č','ď','é','ě','í','ň','ó','ř','ą','»','ú','ů','ý','ľ');
		$autocp['utf'] = array("\xc3\x81", "\xC4\x8C", "\xC4\x8E", "\xc3\x89", "\xC4\x9A", "\xc3\x8d", "\xC5\x87", "\xc3\x93", "\xC5\x98", "\xC5\xA0", "\xC5\xA4", "\xC3\x9A", "\xC5\xAE", "\xc3\x9d", "\xc5\xbd",
		                       "\xc3\xa1", "\xC4\x8D", "\xC4\x8F", "\xc3\xa9", "\xC4\x9B", "\xc3\xad", "\xC5\x88", "\xc3\xb3", "\xC5\x99", "\xC5\xA1", "\xC5\xA5", "\xC3\xBA", "\xC5\xAF", "\xc3\xbd", "\xc5\xbe");

		// Vsechny ceske znaky ktere je mozne prevadet
		$autocp['merge'] = array_merge ($autocp['utf'], $autocp['win'], $autocp['iso']);

		// Prevod do UTF nelze primo, takze AutoCzech na ISO a pak FromToCzech ISO->UTF.
		if ($code=='utf') {
			//echo $str.'<br/>';
                        $str = self::AutoCzech($str, 'iso');
			//echo $str.'<br/>';
                        //$str = str_replace($autocp['iso'], $autocp['utf'], $str);
                        $map = array_combine($autocp['iso'], $autocp['utf']);
                        $str = strtr($str, $map);
                        //echo $str.'<hr/>';
                        return $str;
		}
		// ... do vseho osttaniho (ISO, WIN, ASC) muzeme prevezt primo
		else {
			$to = array_merge ($autocp[$code], $autocp[$code], $autocp[$code]);
			return str_replace($autocp['merge'], $to, $str);
		}
	}


/*


  static function count($str, $other_str) {}

  static function delete($str, $other_str) {}

  static function insert($str, $other_str, $index) {
    if ($index > 0) {

    }
    else {
      
    }
  }
*/

// ISO-8859-2 to UTF-8
public static function iso2utf($s)
{
    static $tbl = array("\x80"=>"","\x81"=>"","\x82"=>"","\x83"=>"","\x84"=>"","\x85"=>"","\x86"=>"","\x87"=>"","\x88"=>"","\x89"=>"","\x8a"=>"","\x8b"=>"","\x8c"=>"","\x8d"=>"","\x8e"=>"","\x8f"=>"","\x90"=>"","\x91"=>"","\x92"=>"","\x93"=>"","\x94"=>"","\x95"=>"","\x96"=>"","\x97"=>"","\x98"=>"","\x99"=>"","\x9a"=>"","\x9b"=>"","\x9c"=>"","\x9d"=>"","\x9e"=>"","\x9f"=>"","\xa0"=>"\xc2\xa0","\xa1"=>"\xc4\x84","\xa2"=>"\xcb\x98","\xa3"=>"\xc5\x81","\xa4"=>"\xc2\xa4","\xa5"=>"\xc4\xbd","\xa6"=>"\xc5\x9a","\xa7"=>"\xc2\xa7","\xa8"=>"\xc2\xa8","\xa9"=>"\xc5\xa0","\xaa"=>"\xc5\x9e","\xab"=>"\xc5\xa4","\xac"=>"\xc5\xb9","\xad"=>"\xc2\xad","\xae"=>"\xc5\xbd","\xaf"=>"\xc5\xbb","\xb0"=>"\xc2\xb0","\xb1"=>"\xc4\x85","\xb2"=>"\xcb\x9b","\xb3"=>"\xc5\x82","\xb4"=>"\xc2\xb4","\xb5"=>"\xc4\xbe","\xb6"=>"\xc5\x9b","\xb7"=>"\xcb\x87","\xb8"=>"\xc2\xb8","\xb9"=>"\xc5\xa1","\xba"=>"\xc5\x9f","\xbb"=>"\xc5\xa5","\xbc"=>"\xc5\xba","\xbd"=>"\xcb\x9d","\xbe"=>"\xc5\xbe","\xbf"=>"\xc5\xbc","\xc0"=>"\xc5\x94","\xc1"=>"\xc3\x81","\xc2"=>"\xc3\x82","\xc3"=>"\xc4\x82","\xc4"=>"\xc3\x84","\xc5"=>"\xc4\xb9","\xc6"=>"\xc4\x86","\xc7"=>"\xc3\x87","\xc8"=>"\xc4\x8c","\xc9"=>"\xc3\x89","\xca"=>"\xc4\x98","\xcb"=>"\xc3\x8b","\xcc"=>"\xc4\x9a","\xcd"=>"\xc3\x8d","\xce"=>"\xc3\x8e","\xcf"=>"\xc4\x8e","\xd0"=>"\xc4\x90","\xd1"=>"\xc5\x83","\xd2"=>"\xc5\x87","\xd3"=>"\xc3\x93","\xd4"=>"\xc3\x94","\xd5"=>"\xc5\x90","\xd6"=>"\xc3\x96","\xd7"=>"\xc3\x97","\xd8"=>"\xc5\x98","\xd9"=>"\xc5\xae","\xda"=>"\xc3\x9a","\xdb"=>"\xc5\xb0","\xdc"=>"\xc3\x9c","\xdd"=>"\xc3\x9d","\xde"=>"\xc5\xa2","\xdf"=>"\xc3\x9f","\xe0"=>"\xc5\x95","\xe1"=>"\xc3\xa1","\xe2"=>"\xc3\xa2","\xe3"=>"\xc4\x83","\xe4"=>"\xc3\xa4","\xe5"=>"\xc4\xba","\xe6"=>"\xc4\x87","\xe7"=>"\xc3\xa7","\xe8"=>"\xc4\x8d","\xe9"=>"\xc3\xa9","\xea"=>"\xc4\x99","\xeb"=>"\xc3\xab","\xec"=>"\xc4\x9b","\xed"=>"\xc3\xad","\xee"=>"\xc3\xae","\xef"=>"\xc4\x8f","\xf0"=>"\xc4\x91","\xf1"=>"\xc5\x84","\xf2"=>"\xc5\x88","\xf3"=>"\xc3\xb3","\xf4"=>"\xc3\xb4","\xf5"=>"\xc5\x91","\xf6"=>"\xc3\xb6","\xf7"=>"\xc3\xb7","\xf8"=>"\xc5\x99","\xf9"=>"\xc5\xaf","\xfa"=>"\xc3\xba","\xfb"=>"\xc5\xb1","\xfc"=>"\xc3\xbc","\xfd"=>"\xc3\xbd","\xfe"=>"\xc5\xa3","\xff"=>"\xcb\x99");
    return strtr($s, $tbl);
}

}

?>