<?php

/**
 * perform common image manipulation tasks
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 * @subpackage modules
 * @depends ImageMagick|GD
 */
class Image {

    /**
     * generate a placeholder image
     *
     * @param string $type
     * @param string $width
     * @param string $height
     * @param string $bg_color
     * @param string $text_color
     * @return object
     */
    public static function placeholder($type, $width, $height, $bg_hex = '666666', $text_hex = 'cccccc'){
        // create text
        $text       = "{$width} x {$height}";
        $font_size  = ($width > $height) ? ($height / 10) : ($width / 10);

        // create colors
        $bg_rgb     = hexrgb($bg_hex);
        $text_rgb   = hexrgb($text_hex);

        // create image
        $image      = imagecreatetruecolor($width, $height);
        $bg_color   = imagecolorallocate($image, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2]);
        $text_color = imagecolorallocate($image, $text_rgb[0], $text_rgb[1], $text_rgb[2]);
        imagefill($image, 0, 0, $bg_color);
        imagettftext(
            $image, 
            $font_size, 
            0, 
            ($width / 2) - ($font_size * 2.75),
            ($height / 2) + ($font_size * 0.2),
            $text_color,
            dirname(__FILE__) . '/assets/placeholder.ttf',
            $text
        );

        // return image
        return $image;
    }

    /**
     * resize an image
     *
     * @param string $old_image
     * @param string $new_image
     * @param integer $new_width
     * @param integer $new_height
     * @param boolean $maintain_aspect
     * @param integer $jpeg_quality
     * @return boolean
     */
    public static function resize($old_image, $new_image, $new_width, $new_height, $maintain_aspect = true, $jpeg_quality = 95){
        if (file_exists($old_image)){
            if ($size = getimagesize($old_image)){
                if ($maintain_aspect){
                    if ($size[0] > $size[1]){
                        // landscape|square
                        $final_width = $new_width;
                        $final_height = ($new_width / $size[0]) * $size[1];
                    } else {
                        // portrait
                        $final_width = ($new_height / $size[1]) * $size[0];
                        $final_height = $new_height;
                    }
                } else {
                    $final_width = $new_width;
                    $final_height = $new_height;
                }

                $old_id = false;
                switch($size[2]){
                    case IMAGETYPE_JPEG:
                        $old_id = imagecreatefromjpeg($old_image);
                        break;
                    case IMAGETYPE_GIF:
                        $old_id = imagecreatefromgif($old_image);
                        break;
                    case IMAGETYPE_PNG:
                        $old_id = imagecreatefrompng($old_image);
                        break;
                }
                if ($old_id !== false){
                    if ($new_id = imagecreatetruecolor($final_width, $final_height)){
                        if (imagecopyresampled($new_id, $old_id, 0, 0, 0, 0, $final_width, $final_height, $size[0], $size[1])){
                            $result_id = false;
                            switch($size[2]){
                                case IMAGETYPE_JPEG:
                                    $result_id = imagejpeg($new_id, $new_image, $jpeg_quality);
                                    break;
                                case IMAGETYPE_GIF:
                                    $result_id = imagegif($new_id, $new_image);
                                    break;
                                case IMAGETYPE_PNG:
                                    $result_id = imagepng($new_id, $new_image);
                                    break;
                            }
                            imagedestroy($new_id);
                            imagedestroy($old_id);
                            return $result_id;
                        }
                    }
                }
            }
        }
        return false;
    }
}

