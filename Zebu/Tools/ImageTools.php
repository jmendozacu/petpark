<?php

/**
 * Image manipulator tools.
 * 
 * @todo: funkce na zjistovani mime, overovani, zda mime je podporovano
 *        urcovat parametrem, zda se ma obrazek vytvorit na danou velikost, popripade barva pozadi, popripade jak centrovat
 *        ImagesController, udelat cely solution 
 *
 * @author Luděk Benedík
 */
class Zebu_Tools_ImageTools {

  private static $supported_image_types = array( 'gif', 'jpeg', 'png' );

  static function supported_image_types() { return self::$supported_image_types; }


  private static $supported_image_mime_types = array( 'image/gif', 'image/jpeg', 'image/png' );

  static function supported_image_mime_types() { return self::$supported_image_mime_types; }


  /**
   * Convert image mime type to gd image method name.
   *
   * @var array Image types
   */
  private static $image_types = array(
    'image/gif'  => 'gif',
    'image/jpeg' => 'jpeg',
    'image/png'  => 'png'
  );


  /**
   * Create image with new dimensions.
   *
   * throw Exception
   *
   * @param string $source_file_path
   * @param string $destination_file_path
   * @param int    $max_width
   * @param int    $max_height
   */
  static function resize_and_save_image($source_file_path, $destination_file_path, $max_width, $max_height) {
    $source_image_info = getimagesize($source_file_path);

    if (!isset(self::$image_types[$source_image_info['mime']]))
      throw new Exception('Unsurported image type! (' . $source_image_info['mime'] . ')');

		if ($source_image_info[0] > $max_width || $source_image_info[1] > $max_height) {
      $image_method_name = 'imagecreatefrom' . self::$image_types[$source_image_info['mime']];
      $source_image = $image_method_name($source_file_path);

      $new_dimensions = self::get_resized_dimensions($source_image_info[0], $source_image_info[1], $max_width, $max_height);
      $new_image      = imagecreatetruecolor($new_dimensions['width'], $new_dimensions['height']);
      
      imageantialias($new_image, true);
      imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_dimensions['width'], $new_dimensions['height'], $source_image_info[0], $source_image_info[1]);

      $image_method_name = 'image' . self::$image_types[$source_image_info['mime']];
      $image_method_name($new_image, $destination_file_path);
    }
    else
      copy($source_file_path, $destination_file_path);
  }

  /**
   * Create image with new dimensions according scale ratio.
   *
   * throw Exception
   *
   * @param string $source_file_path
   * @param string $destination_file_path
   * @param double $ratio <0,1> decrease; <1,...> increase
   */
  static function resize_by_ratio_and_save_image($source_file_path, $destination_file_path, $ratio) {
    $source_image_info = getimagesize($source_file_path);

    if (!isset(self::$image_types[$source_image_info['mime']]))
      throw new Exception('Unsurported image type! (' . $source_image_info['mime'] . ')');

    $image_method_name = 'imagecreatefrom' . self::$image_types[$source_image_info['mime']];
    $source_image = $image_method_name($source_file_path);

    $new_dimensions = self::get_resized_dimensions($source_image_info[0], $source_image_info[1], $ratio*$source_image_info[0], $ratio*$source_image_info[1]);
    $new_image      = imagecreatetruecolor($new_dimensions['width'], $new_dimensions['height']);
    
    imageantialias($new_image, true);
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_dimensions['width'], $new_dimensions['height'], $source_image_info[0], $source_image_info[1]);

    $image_method_name = 'image' . self::$image_types[$source_image_info['mime']];
    $image_method_name($new_image, $destination_file_path);
  }

  /**
   * Create image with new dimensions. If one of parameter ($width, $height) is null
   * then it is calculated to keep ratio    
   *
   * throw Exception
   *
   * @param string $source_file_path
   * @param string $destination_file_path
   * @param int    $width
   * @param int    $height
   */
  static function resize_with_ratio_keeping_and_save_image($source_file_path, $destination_file_path, $width, $height) {
    $source_image_info = getimagesize($source_file_path);

    if (!isset(self::$image_types[$source_image_info['mime']]))
      throw new Exception('Unsurported image type! (' . $source_image_info['mime'] . ')');

    if (!isset($width) && !isset($height)) throw new Exception('No image domension set!');
    
    if (!isset($width)) $width = $source_image_info[0] * $height / $source_image_info[1];
    if (!isset($height)) $height = $source_image_info[1] * $width / $source_image_info[0];

    $image_method_name = 'imagecreatefrom' . self::$image_types[$source_image_info['mime']];
    $source_image = $image_method_name($source_file_path);

    $new_dimensions = self::get_resized_dimensions($source_image_info[0], $source_image_info[1], $width, $height);
    $new_image      = imagecreatetruecolor($new_dimensions['width'], $new_dimensions['height']);
    
    imageantialias($new_image, true);
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_dimensions['width'], $new_dimensions['height'], $source_image_info[0], $source_image_info[1]);

    $image_method_name = 'image' . self::$image_types[$source_image_info['mime']];
    $image_method_name($new_image, $destination_file_path);

  }

  /**
   * Udalat promenou/metodu ktera povoli / zakaze resize do plusu
   *
   * Get resized image dimesions.
   *
   * @param int $width      Actual width
   * @param int $height     Actual height
   * @param int $max_width  Max new width
   * @param int $max_height Max new height
   * 
   * @return array New dimensions
   */
  static function get_resized_dimensions($width, $height, $max_width, $max_height) {
    $new_width;
    $new_height;

    if ($width > $height) {
      $new_width  = $max_width;
      $new_height = round(($max_width * $height) / $width);

      if ($new_height > $max_height) {
        $new_height = $max_height;
        $new_width  = round(($max_height * $new_width) / $new_height);
      }
     }
     else{
      $new_height = $max_height;
      $new_width  = round(($max_height * $width) / $height);

      if ($new_width > $max_width) {
        $new_width  = $max_width;
        $new_height = round(($max_width * $new_height) / $new_width);
      }
    }

    return array( 'width' => $new_width, 'height' => $new_height );
  }

  /**
   * Convert image. Destination type is recognized by extention.
   *
   * throw Exception
   *
   * @param <type> $source_file_path
   * @param <type> $destination_file_path
   */
  public function convert_according_ext($source_file_path, $destination_file_path){
    //$ext = preg_match('~\.[^.]*$~', $filename_out , $matches);
    $ext = strrchr($destination_file_path, '.');
    if (!$ext) throw new Exception('No extention to recognize the type! (filename:' . $destination_file_path . ')');
    $ext = substr($ext, 1);

    if ($ext == 'jpg')
        $ext = 'jpeg';

    $image_types = array_flip(self::$image_types);
    if (!isset($image_types[$ext]))
        throw new Exception('Unsupported image type! (extention ' . $ext . ')');

    self::convert_to($source_file_path, $destination_file_path, $image_types[$ext]);
  }

  /**
   * Convert image to $destination_type type.
   *
   * throw Exception
   *
   * @param <string> $source_file_path
   * @param <string> $destination_file_path
   * @param <string> $destination_type
   */
  public function convert_to($source_file_path, $destination_file_path, $destination_type)
  {

      if (!isset(self::$image_types[$destination_type])){
                throw new Exception('Unsupported image type! (' . $destination_type . ')');
      }

      $source_image_info = getimagesize($source_file_path);

      if ($source_image_info['mime'] == 'image/bmp'){
          $source_image = self::imagecreatefrombmp($source_file_path);
      }
      else{
          if (!isset(self::$image_types[$source_image_info['mime']])){
                throw new Exception('Unsupported image type! (' . $source_image_info['mime'] . ')');
           }else{
                $image_method_name = 'imagecreatefrom' . self::$image_types[$source_image_info['mime']];
                $source_image = $image_method_name($source_file_path);
           }
      }

      $image_method_name = 'image' . self::$image_types[$destination_type];
      $image_method_name($source_image, $destination_file_path);

  }

  /**
   * Create image from bmp
   *
   * @param <string> $filename
   * @return <resource>
   */
  public function imagecreatefrombmp( $filename )
  {
    $file = fopen( $filename, "rb" );
    $read = fread( $file, 10 );
    while( !feof( $file ) && $read != "" )
    {
        $read .= fread( $file, 1024 );
    }
    $temp = unpack( "H*", $read );
    $hex = $temp[1];
    $header = substr( $hex, 0, 104 );
    $body = str_split( substr( $hex, 108 ), 6 );
    if( substr( $header, 0, 4 ) == "424d" )
    {
        $header = substr( $header, 4 );
        // Remove some stuff?
        $header = substr( $header, 32 );
        // Get the width
        $width = hexdec( substr( $header, 0, 2 ) );
        // Remove some stuff?
        $header = substr( $header, 8 );
        // Get the height
        $height = hexdec( substr( $header, 0, 2 ) );
        unset( $header );
    }
    $x = 0;
    $y = 1;
    $image = imagecreatetruecolor( $width, $height );
    foreach( $body as $rgb )
    {
        $r = hexdec( substr( $rgb, 4, 2 ) );
        $g = hexdec( substr( $rgb, 2, 2 ) );
        $b = hexdec( substr( $rgb, 0, 2 ) );
        $color = imagecolorallocate( $image, $r, $g, $b );
        imagesetpixel( $image, $x, $height-$y, $color );
        $x++;
        if( $x >= $width )
        {
            $x = 0;
            $y++;
        }
    }
    return $image;
  }

}

?>