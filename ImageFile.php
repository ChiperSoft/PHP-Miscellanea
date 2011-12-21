<?php 

class ImageFile extends File {
	protected $_image;
	protected $_modifications;

	public $JPEGQuality=80;
	
	static $convert_path;
	
	public function __construct($filename, $mode = '') {
		parent::__construct($filename, $mode);
		$this->_image = array(
			'valid'		=>	null,
			'width'		=>	null,
			'height'	=>	null,
			'mime'		=>	null,
			'format'	=>	null,
			'loaded'	=>	false
		);
		
		if (!self::$convert_path && self::$convert_path!==false) 
			if (!self::FindImageMagick()) throw new Exception("ImageMagick could not be found.");
	}
	
	
	public function __get($name) {
		switch ($name)
		{
			case 'valid':
				if (!$this->_image['loaded']) $this->loadImageInfo();
				return $this->_image['valid'];
				
			case 'mimetype':
				if ($this->_image['valid']) return $this->_image['mime'];
				else return $this->_image['mime'] = parent::__get('mimetype');
				
			case 'width':
			case 'height':
				if (empty($this->_modifications)) {
					if (!$this->_image['loaded']) $this->loadImageInfo();
					return $this->_image[$name];
				} else {
					return $this->_modifications[count($this->_modifications)-1][$name];
				}
			
			case 'format':
				if (!$this->_image['loaded']) $this->loadImageInfo();
				if ($this->_image['valid'])	return $this->_image['format'];
				else {
					$e = $this->extension;
					return ($e=='jpeg'?'jpg':$e);
				}
				
			case 'animatedgif':
				if (!$this->exists) return false;
				if ($this->format != 'gif') return false;
				return preg_match('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $this->get());
				
			// Nonexistent properties.
			default: return parent::__get($name);
		}
	}
	
	
	/**
	 * Changes the JPEG quality value for saving contents
	 *
	 * @param integer $quality Value between 1 and 100 (default is 80)
	 * @return ImageFile	The image object, to allow for method chaining
	 **/
	public function setQuality($quality) {
		$this->JPEGQuality = (int)$quality;
		return $this;
	}
	
	
	/**
	 * Resets the modifications list to default state
	 *
	 * @return ImageFile	The image object, to allow for method chaining
	 **/
	public function reset()
	{
		$this->_modifications = array();
		return $this;
	}
	
	
	/**
	 * Crops the image by the exact pixel dimensions specified
	 * 
	 * The crop does not occur until ::saveChanges() is called.
	 * 
	 * @param  numeric        $new_width    The width in pixels to crop the image to
	 * @param  numeric        $new_height   The height in pixels to crop the image to
	 * @param  numeric|string $crop_from_x  The number of pixels from the left of the image to start the crop from, or a horizontal position of `'left'`, `'center'` or `'right'`
	 * @param  numeric|string $crop_from_y  The number of pixels from the top of the image to start the crop from, or a vertical position of `'top'`, `'center'` or `'bottom'`
	 * @return ImageFile  The image object, to allow for method chaining
	 */
	public function crop($new_width, $new_height, $crop_from_x='center', $crop_from_y='center') {
		$orig_width  = $this->width;
		$orig_height = $this->height;
		
		if (is_string($crop_from_x) && !is_numeric($crop_from_x)) {
			switch (strtolower($crop_from_x)) {
				case 'center':
					$crop_from_x = floor(max($orig_width-$new_width, 0)/2);
					break;
				case 'right':
					$crop_from_x = max($orig_width-$new_width, 0);
					break;
				case 'left':
				default:
					$crop_from_x = 0;
					break;
			}
		}
		
		if (is_string($crop_from_y) && !is_numeric($crop_from_y)) {
			switch (strtolower($crop_from_y)) {
				case 'center':
					$crop_from_y = floor(max($orig_height-$new_height, 0)/2);
					break;
				case 'bottom':
					$crop_from_y = max($orig_height-$new_height, 0);
					break;
				case 'top':
				default:
					$crop_from_y = 0;
					break;
			}
		}
		
		// Make sure the user input is valid
		if (!is_numeric($crop_from_x) || $crop_from_x < 0 || $crop_from_x > $orig_width - 1) {
			throw new Exception(
				'The crop-from x specified, %s, is not a number, is less than zero, or would result in a zero-width image',
				$crop_from_x
			);
		}
		if (!is_numeric($crop_from_y) || $crop_from_y < 0 || $crop_from_y > $orig_height - 1) {
			throw new Exception(
				'The crop-from y specified, %s, is not a number, is less than zero, or would result in a zero-height image',
				$crop_from_y
			);
		}
		
		if (!is_numeric($new_width) || $new_width <= 0 || $crop_from_x + $new_width > $orig_width) {
			throw new Exception(
				'The new width specified, %1$s, is not a number, is less than or equal to zero, or is larger than can be cropped with the specified crop-from x of %2$s',
				$new_width,
				$crop_from_x
			);
		}
		if (!is_numeric($new_height) || $new_height <= 0 || $crop_from_y + $new_height > $orig_height) {
			throw new Exception(
				'The new height specified, %1$s, is not a number, is less than or equal to zero, or is larger than can be cropped with the specified crop-from y of %2$s',
				$new_height,
				$crop_from_y
			);
		}
		
		// If nothing changed, don't even record the modification
		if ($orig_width == $new_width && $orig_height == $new_height) {
			return $this;
		}
		
		// Record what we are supposed to do
		$this->_modifications[] = array(
			'operation'  => 'crop',
			'start_x'    => $crop_from_x,
			'start_y'    => $crop_from_y,
			'width'      => $new_width,
			'height'     => $new_height,
			'old_width'  => $orig_width,
			'old_height' => $orig_height
		);
		
		return $this;
	}
	
	/**
	 * Crops the biggest area possible from the center of the image that matches the ratio provided
	 * 
	 * The crop does not occur until ::saveChanges() is called.
	 * 
	 * @param  numeric $ratio_width          The width ratio to crop the image to
	 * @param  numeric $ratio_height         The height ratio to crop the image to
	 * @param  string  $horizontal_position  A horizontal position of `'left'`, `'center'` or `'right'`
	 * @param  string  $vertical_position    A vertical position of `'top'`, `'center'` or `'bottom'`
	 * @return ImageFile  The image object, to allow for method chaining
	 */
	public function cropToRatio($ratio_width, $ratio_height, $horizontal_position='center', $vertical_position='center') {

		// Make sure the user input is valid
		if ((!is_numeric($ratio_width) && $ratio_width !== NULL) || $ratio_width < 0) {
			throw new Exception(
				'The ratio width specified, %s, is not a number or is less than or equal to zero',
				$ratio_width
			);
		}
		if ((!is_numeric($ratio_height) && $ratio_height !== NULL) || $ratio_height < 0) {
			throw new Exception(
				'The ratio height specified, %s, is not a number or is less than or equal to zero',
				$ratio_height
			);
		}	
		
		// Make sure 
		$valid_horizontal_positions = array('left', 'center', 'right');
		if (!in_array(strtolower($horizontal_position), $valid_horizontal_positions)) {
			$horizontal_position = 'center';
		}
		
		$valid_vertical_positions = array('top', 'center', 'bottom');
		if (!in_array(strtolower($vertical_position), $valid_vertical_positions)) {
			$vertical_position = 'center';
		}
		
		$orig_width  = $this->width;
		$orig_height = $this->height;
		
		$orig_ratio = $orig_width / $orig_height;
		$new_ratio  = $ratio_width / $ratio_height;
			
		if ($orig_ratio > $new_ratio) {
			$new_height = $orig_height;
			$new_width  = round($new_ratio * $new_height);
		} else {
			$new_width  = $orig_width;
			$new_height = round($new_width / $new_ratio);
		}
			
		return $this->crop($new_width, $new_height, $horizontal_position, $vertical_position);
	}
	
	/**
	 * Converts the image to grayscale
	 * 
	 * Desaturation does not occur until ::saveChanges() is called.
	 * 
	 * @return fImage  The image object, to allow for method chaining
	 */
	public function desaturate() {
		// Record what we are supposed to do
		$this->_modifications[] = array(
			'operation'  => 'desaturate',
			'width'      => $this->width,
			'height'     => $this->height,
			'old_width'  => $this->width,
			'old_height' => $this->height
		);
		
		return $this;
	}
	
	/**
	 * Sets the image to be resized proportionally to a specific size canvas
	 * 
	 * Will only size down an image. This method uses resampling to ensure the
	 * resized image is smooth in appearance. Resizing does not occur until
	 * ::saveChanges() is called.
	 * 
	 * @param  integer $canvas_width    The width of the canvas to fit the image on, `0` for no constraint
	 * @param  integer $canvas_height   The height of the canvas to fit the image on, `0` for no constraint
	 * @param  boolean $allow_upsizing  If the image is smaller than the desired canvas, the image will be increased in size
	 * @param  boolean $fill_canvas  	Size the image to fill the entire canvas dimensions.
	 * @return fImage  The image object, to allow for method chaining
	 */
	public function resize($canvas_width, $canvas_height, $allow_upsizing=FALSE, $fill_canvas=FALSE) {
		// Make sure the user input is valid
		if ((!is_numeric($canvas_width) && $canvas_width !== NULL) || $canvas_width < 0) {
			throw new Exception(
				'The canvas width specified, %s, is not an integer or is less than zero',
				$canvas_width
			);
		}
		if ((!is_numeric($canvas_height) && $canvas_height !== NULL) || $canvas_height < 0) {
			throw new Exception(
				'The canvas height specified, %s is not an integer or is less than zero',
				$canvas_height
			);
		}
		if ($canvas_width == 0 && $canvas_height == 0) {
			throw new Exception(
				'The canvas width and canvas height are both zero, so no resizing will occur'
			);
		}
		
		$orig_width  = $this->width;
		$orig_height = $this->height;
		
		if ($canvas_width == 0) {
			$new_height = $canvas_height;
			$new_width  = round(($new_height/$orig_height) * $orig_width);
		
		} elseif ($canvas_height == 0) {
			$new_width  = $canvas_width;
			$new_height = round(($new_width/$orig_width) * $orig_height);
		
		} else {
			$orig_ratio   = $orig_width/$orig_height;
			$canvas_ratio = $canvas_width/$canvas_height;
			
			if ($canvas_ratio > $orig_ratio xor $fill_canvas) {
				$new_height = $canvas_height;
				$new_width  = round($orig_ratio * $new_height);
			} else {
				$new_width  = $canvas_width;
				$new_height = round($new_width / $orig_ratio);
			}
		}
		
		// If the size did not change, don't even record the modification
		$same_size   = $orig_width == $new_width || $orig_height == $new_height;
		$wont_change = ($orig_width < $new_width || $orig_height < $new_height) && !$allow_upsizing;
		if ($same_size || $wont_change) {
			return $this;
		}
		
		// Record what we are supposed to do
		$this->_modifications[] = array(
			'operation'  => 'resize',
			'width'      => $new_width,
			'height'     => $new_height,
			'old_width'  => $orig_width,
			'old_height' => $orig_height
		);
		if ($fill_canvas) {
			$this->crop($canvas_width, $canvas_height);
		}
		
		return $this;
	}
	
	
	/**
	 * Sets the image to be rotated
	 * 
	 * Rotation does not occur until ::saveChanges() is called.
	 * 
	 * @param  integer|string $degrees   The number of degrees to rotate - 90, 180, or 270 – or 'left'|'right'|'half'
	 */
	public function rotate($degrees) {

		switch ($degrees) {
			case 'left': 	$degrees = 270;break;
			case 'half':	$degrees = 180;break;
			case 'right':	$degrees = 090;break;
			case 90:
			case 180:
			case 270:
				break;
			default:
				throw new Exception('Invalid rotation amount specified.');
		}

		
		$orig_width  = $this->width;
		$orig_height = $this->height;
		
		if ($degrees == 180) {
			$new_width  = $this->width;
			$new_height = $this->height;
		} else {
			$new_width  = $this->height;
			$new_height = $this->width;
		}
		
		// Record what we are supposed to do
		$this->_modifications[] = array(
			'operation'  => 'rotate',
			'degrees'    => $degrees,
			'width'      => $new_width,
			'height'     => $new_height,
			'old_width'  => $orig_width,
			'old_height' => $orig_height
		);
		
		return $this;
	}
	
	
	
	private function buildCommand($output_file) {
		$new_format = $output_file->extension;
		$new_format = ($e=='jpeg'?'jpg':$new_format);

		$input_file = $this->path;
		if ($this->format != 'gif') $input_file .= '[0]';
		
		$command = array();
		$command[] = escapeshellarg(self::$convert_path);
		$command[] = escapeshellarg($input_file);
		if ($this->animatedgif) 	$command[] = '-coalesce';
		if ($this->format=='tif') 	$command[] = '-depth 8';
		
		$colorspace = 'RGB';
		foreach ($this->_modifications as $mod) {
			switch ($mod['operation']) {
				case 'resize':
					$con = ($mod['old_width'] < $mod['width'] || $mod['old_height'] < $mod['height'])?'<':'';
					$command[] = "-resize \"{$mod['width']}x{$mod['height']}{$con}\"";
					break;
				case 'crop':
					$command[] = "-crop \"{$mod['width']}x{$mod['height']}+{$mod['start_x']}+{$mod['start_y']}\"";
					$command[] = "-repage \"{$mod['width']}x{$mod['height']}+0+0\"";
					break;
				case 'desaturate':
					$colorspace = 'GRAY';
					break;
				case 'rotate':
					$command[] = "-rotate {$mod['degrees']}";
					break;
			}			
		}
		
		$command[] = "-colorspace {$colorspace}";
		
		if ($new_format=='jpg') {
			$command[] = "-compress JPEG -quality {$this->JPEGQuality}";
		}
		
		$command[] = escapeshellarg("{$new_format}:{$output_file->path}");
		
		return implode(' ',$command);
	}


	public function saveChanges($output_file=null) {
		$output_file = new File($output_file?$output_file:$this->path);
		
		$command = $this->buildCommand($output_file);
		
		//echo $command;
		
		exec($command);
		
		return $output_file->exists?$output_file:false;
	}



	
	
	
	/**
	 * Loads data pertinent to the file's contents and caches for access
	 *
	 * @return boolean True if the file is an image and the data was loaded successfully
	 **/
	protected function loadImageInfo() {
		if (!$this->exists) {
			$this->_image['valid'] = false;
			return false;
		}
		
		$data = @getimagesize($this->_file);
		
		if (!$data) {
			$this->_image['valid'] = false;
			return false;
		}
		
		$types = array(IMAGETYPE_GIF     => 'gif',
					   IMAGETYPE_JPEG    => 'jpg',
					   IMAGETYPE_PNG     => 'png',
					   IMAGETYPE_TIFF_II => 'tif',
					   IMAGETYPE_TIFF_MM => 'tif');
		
		$this->_image = array(
			'valid'		=>	true,
			'width'		=>	$data[0],
			'height'	=>	$data[1],
			'mime'		=>	$data['mime'],
			'format'	=>	isset($types[$data[2]])?$types[$data[2]]:false,
			'loaded'	=>	true
		);
	}
	
	
	
	
	
	
	
	
	
	/**
	 * Scans through the filesystem trying to locate the convert binary
	 *
	 * @return boolean True if convert was found
	 **/
	protected static function FindImageMagick() {
		//if a convert path has been defined, always use that.
		if (defined('CONVERT_PATH')) {
			self::$convert_path == CONVERT_PATH;
			return;
		}
		
		$locations = array(
			'/usr/bin/',
			'/usr/local/bin/',
			'/opt/local/bin/',
			'/opt/bin/',
			'/opt/csw/bin/'
		);
		
		foreach ($locations as $loc) {
			$lf = new File($loc);
			if ($lf->is_open_basedir_restricted) {
				exec("{$loc}convert -version", $output);
				if ($output) {
					self::$convert_path = "{$loc}convert";
					return true;
				}
			} elseif (is_executable("{$loc}convert")) {
				self::$convert_path = "{$loc}convert";
				return true;
			}			
		}
		
		
		//wasn't found in the usual places.  Try doing a which search.
		$uname = php_uname('s');
		if ((stripos($uname, 'linux') !== FALSE) || (stripos($uname, 'freebsd') !== FALSE) || (stripos($uname, 'aix') !== FALSE)) {
			$nix_search = 'whereis -b convert';
			exec($nix_search, $nix_output);
			$nix_output = trim(str_replace('convert:', '', join("\n", $nix_output)));
			
			if (!$nix_output) {
				return self::$convert_path = false;
			}
		
			self::$convert_path = preg_replace('#^(.*)convert$#i', '\1', $nix_output);
			return true;
			
		} elseif ((stripos($uname, 'darwin') !== FALSE) || (stripos($uname, 'netbsd') !== FALSE) || (stripos($uname, 'openbsd') !== FALSE)) {
			$osx_search = 'whereis convert';
			exec($osx_search, $osx_output);
			$osx_output = trim(join("\n", $osx_output));
			
			if (!$osx_output) {
				return self::$convert_path = false;
			}
		
			if (preg_match('#^(.*)convert#i', $osx_output, $matches)) {
				self::$convert_path = $matches[1];
				return true;
			}
		}
		
		return self::$convert_path = false;
		
	}
	
}
