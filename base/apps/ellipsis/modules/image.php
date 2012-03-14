<?php

/**
 * perform useful image manipulation tasks
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 * @subpackage modules
 * @depends ImageMagick|GD
 */
class Image {
    /**
     * convert image extension to image type
     *
     * @param string $image_path
     * @return int
     */
    private static function get_extension_type($image_path){
        $extension = preg_replace('/^.*\.([^\.]+)$/i', '$1', $image_path);
        switch($extension){
            case 'gif':
                return IMAGETYPE_GIF;
            case 'jpg':
            case 'jpeg':
                return IMAGETYPE_JPEG;
            case 'png':
                return IMAGETYPE_PNG;
            case 'bmp':
                return IMAGETYPE_BMP;
            case 'ico':
                return IMAGETYPE_ICO;
            default:
                return -1;
        }
    }

    /**
     * get the image type from a file path or URI
     *
     * @param string $image_source
     * @return int or null
     */
    private static function get_image_type($image_source){
        $type = null;
        if (is_file($image_source)){
            $type = exif_imagetype($image_source);
        } else if (is_uri($image_source)){
            $type = self::get_extension_type($image_source);
        }
        return $type;
    }

    /**
     * load an image source via path or uri
     *
     * @param string $source
     * @param string $as
     * @param integer $quality_percentage
     * @return resource
     */
    private static function load($source, $as = null, $quality_percentage = 100){
        $type = self::get_image_type($source);
        $image = null;
        switch($type){
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($source);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($source);
                imagesavealpha($image, true);
                break;
            default:
                return null;
        }
        if ($as != null){
            switch($as){
                case 'gif':
                    $image = self::togif($image, null);
                    break;
                case 'jpg':
                case 'jpeg':
                    $image = self::tojpg($image, null, $quality_percentage);
                    break;
                case 'png':
                    $image = self::topng($image, null, $quality_percentage);
                    imagesavealpha($image, true);
                    break;
            }
        }
        return $image;
    }

    /**
     * save an image resource to a local file
     *
     * @param resource $image_resource
     * @param string $destination_path or null
     * @param integer $quality_percentage
     * @return boolean or resource
     */
    private static function save($image_resource, $destination_path, $quality_percentage = 100){
        $image_type = self::get_extension_type($destination_path);
        $image_result = false;
        switch($image_type){
            case IMAGETYPE_JPEG:
                $image_result = imagejpeg($image_resource, $destination_path, $quality_percentage);
                break;
            case IMAGETYPE_GIF:
                $image_result = imagegif($image_resource, $destination_path);
                break;
            case IMAGETYPE_PNG:
                $image_result = imagepng($image_resource, $destination_path, ($quality_percentage == 100) ? 1 : round(9 - (9 * ($quality_percentage * .01))));
                break;
        }
        imagedestroy($image_resource);
        return $image_result;
    }

    /**
     * convert any recognized image format to png
     *
     * note: this is used internally to ensure alpha support across all
     * inner methods, and is not intended for usual exportation
     *
     * @param mixed $image
     * @param string $path or null
     * @param integer $quality_percentage
     * @return boolean or resource
     */
    private static function topng($image, $path = null, $quality_percentage = 100){
        // get an image resource
        if (is_string($image) && is_file($image)){
            $image = self::load($image, null, $quality_percentage);
        }

        // return what the developer expects
        if (gettype($image) == 'resource'){
            if ($path == null){
                // convert to png via content buffer
                ob_start();
                imagesavealpha($image, true);
                imagepng($image, $path, 1);
                $contents = ob_get_contents();
                ob_end_clean();
                $image = imagecreatefromstring($contents);
                return $image;
            } else {
                // convet to png via file output
                return imagepng($image, $path, 1);
            }
        }

        // something failed
        return false;
    }

    /**
     * apply an alpha transparency to an image
     *
     * note: destination image will always be a PNG
     *
     * @param string $source_path
     * @param string $destination_path or null
     * @param integer $opacity_percentage
     * @param integer $quality_percentage
     * @return boolean or resource
     */
    public static function alpha($source_path, $destination_path, $opacity_percentage, $quality_percentage = 100){
        // get source dimensions
        list($source_width, $source_height, $source_type) = getimagesize($source_path);

        // load source image
        $source_image = self::load($source_path);

        // record result
        $result = false;
        
        if ($source_image != null){
            // convert to decimal
            $opacity_percentage /= 100;

            // turn alphablending off
            imagealphablending($source_image, false);

            // allow the transparency layer to be saved
            imagesavealpha($source_image, true);

            // find the most opaque pixel in the image
            $minimum_alpha = 127;
            for ($x = 0; $x < $source_width; $x++){
                for ($y = 0; $y < $source_height; $y++){
                    $alpha = (imagecolorat($source_image, $x, $y) >> 24) & 0xFF;
                    if ($alpha < $minimum_alpha){
                        $minimum_alpha = $alpha;
                    }
                }
            }

            // modify each image pixel
            for ($x = 0; $x < $source_width; $x++){
                for ($y = 0; $y < $source_height; $y++){
                    // get current alpha value
                    $color_xy = imagecolorat($source_image, $x, $y);
                    $alpha = ($color_xy >> 24) & 0xFF;

                    // calculate new alpha
                    if ($minimum_alpha !== 127){
                        $alpha = 127 + 127 * $opacity_percentage * ($alpha - 127) / (127 - $minimum_alpha);
                    } else {
                        $alpha += 127 * $opacity;
                    }

                    // get the color index with new alpha
                    $alpha_color_xy = imagecolorallocatealpha($source_image, ($color_xy >> 16) & 0xFF, ($color_xy >> 8) & 0xFF, $color_xy & 0xFF, $alpha);

                    // set pixel with the new color and opacity
                    if (!imagesetpixel($source_image, $x, $y, $alpha_color_xy)){
                        return false;
                    }
                }
            }

            // this method only supports PNG images
            $result = imagepng($source_image, $destination_path, ($quality_percentage == 100) ? 1 : round(9 - (9 * ($quality_percentage * .01))));
            imagedestroy($source_image);
        }

        return $result;
    }

    /**
     * compress an image
     *
     * @param string $source_path
     * @param string $destination_path or null
     * @param integer $quality_percentage
     * @return boolean or resource
     */
    public static function compress($source_path, $destination_path, $quality_percentage = 100){
        //copy($source_path, $destination_path);
        // load source image
        $source_image = self::load($source_path, 'png');
        //imagesavealpha($source_image, true);
        //return imagepng($source_image, $destination_path, 9 * ($quality_percentage * .01));
        //echo "$quality_percentage = " . (9 - (9 * ($quality_percentage * .01))) . "<br/>\n";

        // save compressed image
        return self::save($source_image, $destination_path, $quality_percentage);
    }

    /**
     * crop out a portion of an image
     *
     * @param string $source_path
     * @param string $destination_path or null
     * @param integer $x
     * @param integer $y
     * @param integer $width
     * @param integer $height
     * @param integer $quality_percentage
     * @return boolean or resource
     */
    public static function crop($source_path, $destination_path, $x, $y, $width, $height, $quality_percentage = 100){
        // get source dimensions
        list($source_width, $source_height, $source_type) = getimagesize($source_path);

        // create the cropped image
        $crop_image = self::transparent(null, $width, $height);

        // load source image
        $source_image = self::load($source_path);

        if ($source_image != null){
            // copy the cropped portion of the source image onto the cropped image
            imagecopy($crop_image, $source_image, 0, 0, $x, $y, $source_width, $source_height);

            // save the cropped image
            return self::save($crop_image, $destination_path, $quality_percentage);
        }

        // something failed
        return false;
    }

    /**
     * flip an image horizontally, vertically or both
     *
     * @param string $source_path
     * @param string $destination_path
     * @param string $direction
     * @param integer $quality_percentage
     * @return boolean
     */
    public static function flip($source_path, $destination_path, $direction, $quality_percentage = 100){
        // get source dimensions
        list($source_width, $source_height, $source_type) = getimagesize($source_path);
        
        // create flip settings
        $flip_x         = 0;
        $flip_y         = 0;
        $flip_width     = $source_width;
        $flip_height    = $source_height;

        // perform flip logic according to direction
        switch($direction){
            case 'horizontal':
                $flip_x         = $source_width - 1;
                $flip_width     = -$source_width;
                break;
            case 'vertical':
                $flip_y         = $source_height - 1;
                $flip_height    = -$source_height;
                break;
            case 'both':
                $flip_x         = $source_width - 1;
                $flip_y         = $source_height - 1;
                $flip_width     = -$source_width;
                $flip_height    = -$source_height;
                break;
            default:
                // unrecognized direction
                return false;
        }

        // load source image
        $source_image = self::load($source_path);

        if ($source_image != null){
            // create the flipped image resource
            $flip_image = imagecreatetruecolor($source_width, $source_height);

            // copy the flipped version of the source image onto the flipped image resource
            imagecopyresampled($flip_image, $source_image, 0, 0, $flip_x, $flip_y, $source_width, $source_height, $flip_width, $flip_height);

            // save the flipped image
            return self::save($flip_image, $destination_path, $quality_percentage);
        }

        // something failed
        return false;
    }

    /**
     * overlay one image on top of another
     *
     * @param string $base_path
     * @param string $overlay_path
     * @param string $destination_path
     * @param integer $x
     * @param integer $y
     * @param integer $quality_percentage
     */
    public static function overlay($base_path, $overlay_path, $destination_path, $x, $y, $quality_percentage = 100){
        // get base dimensions
        list($base_width, $base_height, $base_type) = getimagesize($base_path);

        // get overlay dimensions
        list($overlay_width, $overlay_height, $overlay_type) = getimagesize($overlay_path);

        // create a blank canvas image
        $canvas_image = self::transparent(null, $base_width, $base_height);

        // load base image
        $base_image = self::load($base_path);

        if ($base_image != null){
            // load overlay image
            $overlay_image = self::load($overlay_path);

            if ($overlay_image != null){
                // place base image over canvas image
                imagecopyresampled($canvas_image, $base_image, 0, 0, 0, 0, $base_width, $base_height, $base_width, $base_height);

                // place overlay image over canvas image
                imagecopyresampled($canvas_image, $overlay_image, $x, $y, 0, 0, $overlay_width, $overlay_height, $overlay_width, $overlay_height);

                // save the combined image
                return self::save($canvas_image, $destination_path, $quality_percentage);
            }
        }

        // something failed
        return false;
    }

    /**
     * resize an image to a new width and height
     *
     * @param string $source_path
     * @param string $destination_path
     * @param integer $width
     * @param integer $height
     * @param boolean $maintain_aspect_ratio
     * @param integer $quality_percentage
     * @return boolean
     */
    public static function resize($source_path, $destination_path, $width, $height, $maintain_aspect_ratio = false, $quality_percentage = 100){
        // get source dimensions
        list($source_width, $source_height, $source_type) = getimagesize($source_path);

        // load source image
        $source_image = self::load($source_path);

        if ($source_image != null){
            // compute new dimensions
            if ($maintain_aspect_ratio){
                if ($source_width > $source_height){
                    // landscape or square
                    $destination_width = $width;
                    $destination_height = ($width / $source_width) * $source_height;
                } else {
                    // portrait
                    $destination_width = ($height / $source_height) * $source_width;
                    $destination_height = $height;
                }
            } else {
                $destination_width = $width;
                $destination_height = $height;
            }
    
            // create the resized image resource
            $resized_image = imagecreatetruecolor($destination_width, $destination_height);

            $success = false;
            if ($source_image !== false){
                // copy the resized version of the source image onto the resized image resource
                imagecopyresampled($resized_image, $source_image, 0, 0, 0, 0, $destination_width, $destination_height, $source_width, $source_height);

                // save the resized image
                return self::save($resized_image, $destination_path, $quality_percentage);
            }
        }

        // something failed
        return false;
    }

    /**
     * rotate an image clockwise by degrees
     *
     * note: background_hex can be set to null (for transparent) if destination is PNG
     * 
     * @param string $source_path
     * @param string $destination_path
     * @param integer $degrees
     * @param string $background_hex
     * @param integer $quality_percentage
     * @return boolean
     */
    public static function rotate($source_path, $destination_path, $degrees, $background_hex = null, $quality_percentage = 100){
        // get source dimensions
        list($source_width, $source_height, $source_type) = getimagesize($source_path);

        // get rotated dimensions
        $temp_image = imagecreatetruecolor($source_width, $source_height);
        $rotated_image = imagerotate($temp_image, $degrees, -1);
        $rotated_width = imagesx($rotated_image);
        $rotated_height = imagesy($rotated_image);
        imagedestroy($temp_image);

        // set scaling factor (to mimic the visual effect of antialiasing)
        $scaling_factor = 2;

        // get scaled source dimensions
        $scaled_source_width = $source_width * $scaling_factor;
        $scaled_source_height = $source_height * $scaling_factor;

        // get scaled rotated dimensions
        $scaled_rotated_width = $rotated_width * $scaling_factor;
        $scaled_rotated_height = $rotated_height * $scaling_factor;

        // convert degrees to clockwise (because anticlockwise is just weird)
        $degrees = 360 - $degrees;

        // load source image
        $source_image = self::load($source_path);

        if ($source_image != null){
            // create a scaled version of the source image
            $scaled_source_image = imagecreatetruecolor($scaled_source_width, $scaled_source_height);
            imagecopyresampled($scaled_source_image, $source_image, 0, 0, 0, 0, $scaled_source_width, $scaled_source_height, $source_width, $source_height);

            // create the rotated image resource with the appropriate background color
            if ($background_hex != null){
                // convert the background to rgb
                list($red, $green, $blue) = hexrgb($background_hex);

                // rotate the image
                $scaled_rotated_image = imagerotate($scaled_source_image, $degrees, imagecolorallocate($scaled_source_image, $red, $green, $blue));

                // restore to original scale
                $rotated_image = imagecreatetruecolor($rotated_width, $rotated_height);
                imagecopyresampled($rotated_image, $scaled_rotated_image, 0, 0, 0, 0, $rotated_width, $rotated_height, imagesx($scaled_rotated_image), imagesy($scaled_rotated_image));
            } else {
                // create a transparent background color
                $transparent_image = imagecreatetruecolor($scaled_source_width, $scaled_source_height);
                $transparent_color = imagecolorallocatealpha($transparent_image, 0, 0, 0, 127);

                // rotate the image
                $scaled_rotated_image = imagerotate($scaled_source_image, $degrees, -1);

                // apply the transparent background color
                imagesavealpha($scaled_rotated_image, true);
                imagecolortransparent($scaled_rotated_image, $transparent_color);

                // restore to the original scale (while maintaining transparency)
                $rotated_image = imagecreatetruecolor($rotated_width, $rotated_height);
                imagecolortransparent($rotated_image, imagecolorallocatealpha($rotated_image, 0, 0, 0, 127));
                imagealphablending($rotated_image, false);
                imagesavealpha($rotated_image, true);
                imagecopyresampled($rotated_image, $scaled_rotated_image, 0, 0, 0, 0, $rotated_width, $rotated_height, imagesx($scaled_rotated_image), imagesy($scaled_rotated_image));

                // cleanup
                imagedestroy($transparent_image);
            }

            // cleanup
            imagedestroy($scaled_source_image);

            // save the rotated image
            return self::save($rotated_image, $destination_path, $quality_percentage);
        }

        // something failed
        return false;
    }

    /**
     * scale an image by percentage
     *
     * @param string $source_path
     * @param string $destination_path
     * @param integer $scale_percentage
     * @param integer $quality_percentage
     * @return boolean
     */
    public static function scale($source_path, $destination_path, $scale_percentage, $quality_percentage = 100){
        // get source dimensions
        list($source_width, $source_height, $source_type) = getimagesize($source_path);

        // computer new dimensions
        $destination_width = round($scale_percentage * ($source_width /= 100));
        $destination_height = round($scale_percentage * ($source_height /= 100));

        // resize image
        return self::resize($source_path, $destination_path, $destination_width, $destination_height, true);
    }

    /**
     * create a transparent image (png)
     *
     * @param string $destination_path or null
     * @param integer $width
     * @param integer $height
     * @return boolean or resource
     */
    public static function transparent($destination_path, $width, $height){
        // create the image resource
        $image = imagecreatetruecolor($width, $height);

        // allow the alpha channel to be saved
        imagesavealpha($image, true);

        // apply a transparent fill
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        if ($destination_path == null){
            return $image;
        } else {
            // return success or failure of image creation
            $result = imagepng($image, $destination_path, 9);
            imagedestroy($image);
            return $result;
        }
    }

    /**
     * layer one or more image effects on top of one other
     *
     * @param string $destination_path
     * @param integer $destination_width
     * @param integer $destination_height
     * @param array $layers
     * @param integer $quality_percentage
     * @return boolean
     */
    public static function layer($destination_path, $destination_width, $destination_height, $layers, $quality_percentage = 100){
        // create the master key to identify this request
        $master_key = md5(serialize(array($destination_path, $destination_width, $destination_height, $layers, $quality_percentage)));

        // create the master image file path
        $master_path = "{$_ENV['WEBSITE_CACHE_ROOT']}/files/{$master_key}.png";

        // create the master image file
        self::transparent($master_path, $destination_width, $destination_height);

        // remember generated layers
        $generated = array();

        foreach($layers as $layer){
            // create the layer key to identify this layer
            $layer_key = md5(serialize(array($master_key, $layer)));

            // create the layer image file path
            $layer_path = "{$_ENV['WEBSITE_CACHE_ROOT']}/files/{$layer_key}.png";

            $temp = "{$_ENV['WEBSITE_CACHE_ROOT']}/files/temp-{$layer_key}";

            // create the layer image file
            self::transparent($layer_path, $destination_width, $destination_height);

            // define source properties for the effects to use
            $source_path = $layer['path'];
            $source_x = $layer['x'] or 0;
            $source_y = $layer['y'] or 0;

            foreach($layer['effects'] as $effect => $properties){
                switch($effect){
                    case 'crop':
                        $properties = array_extend(array('x' => null, 'y' => null, 'width' => null, 'height' => null, 'quality' => 100), $properties);
                        if (!in_array(null, $properties)){
                            if (self::crop($source_path, $layer_path, $properties['x'], $properties['y'], $properties['width'], $properties['height'], $properties['quality'])){
                                $success = true;
                                break;
                            }
                        }
                        return false;
                    case 'flip':
                        $properties = array_extend(array('direction' => null, 'quality' => 100), $properties);
                        if (!in_array(null, $properties)){
                            if (self::flip($source_path, $layer_path, $properties['direction'], $properties['quality'])){
                                $success = true;
                                break;
                            }
                        }
                        return false;
                    case 'alpha':
                        $properties = array_extend(array('opacity' => null, 'quality' => 100), $properties);
                        if (!in_array(null, $properties)){
                            if (self::alpha($source_path, $layer_path, $properties['opacity'], $properties['quality'])){
                                $success = true;
                                break;
                            }
                        }
                        return false;
                    case 'resize':
                        $properties = array_extend(array('width' => null, 'height' => null, 'aspect' => false, 'quality' => 100), $properties);
                        if (!in_array(null, $properties)){
                            if (self::resize($source_path, $layer_path, $properties['width'], $properties['height'], $properties['aspect'], $properties['quality'])){
                                $success = true;
                                break;
                            }
                        }
                        return false;
                    case 'rotate':
                        $properties = array_extend(array('degrees' => null, 'quality' => 100), $properties);
                        if (!in_array(null, $properties)){
                            $properties['background'] = (isset($properties['background']) && $properties['background'] != null) ? preg_replae('/^#/', '', $properties['background']) : null;
                            if (self::rotate($source_path, $layer_path, $properties['degrees'], $properties['background'], $properties['quality'])){
                                $success = true;
                                break;
                            }
                        }
                        return false;
                    case 'scale':
                        $properties = array_extend(array('percentage' => null, 'quality' => 100), $properties);
                        if (!in_array(null, $properties)){
                            if (self::scale($source_path, $layer_path, $properties['percentage'], $properties['quality'])){
                                $success = true;
                                break;
                            }
                        }
                        return false;
                    default:
                        // unknown effect
                        return false;
                }

                // reset the source and overlay the new image result
                $source_path = $layer_path;
                if (!self::overlay($master_path, $layer_path, $layer_path, $source_x, $source_y)){
                    return false;
                }
            }

            $generated[] = $layer_path;
        }

        //print '<pre>' . print_r($generated, true) . '</pre>';

        // copy the master image file to the destination file path
        // @todo: force the destination file type conversion here as well
        if (copy($layer_path, $destination_path)){
            return true;
        }

        // something failed
        return false;
    }
}

