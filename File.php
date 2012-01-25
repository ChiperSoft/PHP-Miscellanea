<?php

class File
{
	// Some properties.
	
	protected $_file;
	protected $lineEnding = "\n";
	protected $mode;
	protected $canRead;
	protected $canWrite;
	protected $csvDelimiter = ',';
	protected $csvEnclosure = '"';
	protected $csvEscape = '\\';
	protected $defaultPermissions = 0777;
	public $pointer;
	
	// Constructor.
	
	public function __construct($filename, $mode = '') {
		$this->_file = ($filename instanceof File) ? $filename->path : $filename;
		$this->mode = $mode;
	}
	
	public function __toString() {
		return $this->_file;
	}
	
	public function __get($name) {
		switch ($name)
		{
			case 'path':
				return $this->_file;
				
			case 'name':
				return basename($this->_file);
				
			case 'filename':
				return pathinfo($this->_file, PATHINFO_FILENAME);

			case 'extension':
				return pathinfo($this->_file, PATHINFO_EXTENSION);
				
			case 'type':
				return filetype($this->_file);

			case 'parent':
				return new File(dirname($this->_file));

			case 'exists':
				return file_exists($this->_file);

			case 'creatable':
				$path = $this->_file;
				while (!file_exists($path)) $path = dirname($path); //if the file doesn't exist, work backwards until we find a folder that does.
				return is_writable($path);
			
			case 'writable':
				return is_writable($this->_file);
				
			case 'readable':
				return is_readable($this->_file);

			case 'is_dir':
				return file_exists($this->_file)?is_dir($this->_file):false;

			case 'is_uploaded':
				return file_exists($this->_file)?is_uploaded_file($filename):false;
				
			case 'is_symlink':
				return is_link($this->_file);
				
			case 'is_hidden':
				if ($this->name[0]==='.') return true;
				return false;

			case 'size':
				return sprintf("%u", filesize($this->_file)); //formatting as string to avoid signed integer overflow
								
			case 'position':
				return ftell($this->pointer);
			
			case 'stat':
				if (!$this->pointer) $this->open();
				return fstat($this->pointer);

			case 'eof':
				if (!$this->pointer) $this->open();
				return feof($this->pointer);
				
			case 'children':
				if (!$this->exists || !$this->is_dir) return false;
				$results = array();
				if ($dh = opendir($this->_file)) {
					while (($file = readdir($dh)) !== false) if ($file != "." && $file != "..") $results[] = new File($this->_file.'/'.$file);
					closedir($dh);
				}
				return $results;
				
			case 'mimetype':
				// The first 4k should be enough for content checking
				$handle   = fopen($this->_file, 'r');
				$contents = fread($handle, 4096);
				fclose($handle);

				$extension = $this->extension;

				// If there are no low ASCII chars and no easily distinguishable tokens, we need to detect by file extension
				if (!preg_match('#[\x00-\x08\x0B\x0C\x0E-\x1F]|%PDF-|<\?php|\%\!PS-Adobe-3|<\?xml|\{\\\\rtf|<\?=|<html|<\!doctype|<rss|\#\![/a-z0-9]+(python|ruby|perl|php)\b#i', $contents)) {
					return self::DetermineMimeTypeByExtension($extension);		
				}

				return self::DetermineMimeTypeByContents($contents, $extension);
				
			case 'time_modified':
				return filemtime($this->_file);

			case 'time_created':
				return filectime($this->_file);

			case 'time_last_accessed':
				return fileatime($this->_file);
				
			case 'is_open_basedir_restricted':
			/**
			 * 	Checks if access to the resource is denied by the open_basedir php setting
			 */
				if (!ini_get('open_basedir')) return false; //no open_basedir is defined, all paths allowed
				
				$open_basedirs = explode(':', ini_get('open_basedir'));
				$found = FALSE;
				foreach ($open_basedirs as $open_basedir) {
					if (strpos($path, $open_basedir) === 0) {
						return false; //path is listed in the setting and is allowed.
					}
				}
				
				return TRUE; //path was not found in the setting, assumed it is restricted.
				
				

			// Nonexistent properties.
			default: return null;
		}
	}
	
	public function setLineEnding($lineEnding) {
		// Check the line ending.
		if (!in_array($lineEnding, array("\n", "\r\n")))
			throw new FileException('Line ending must be either \\n or \\r\\n.');

		// Keep.		
		$this->lineEnding = $lineEnding;
	}
	
	public function descendentOf($path) {
		$path = realpath(($path instanceof File) ? $path->path : $filename);
		
		return (strpos($this->path, $path) === 0);
	}
	
	public function verifyParent() {
		if ($this->exists) return true;
		if (file_exists(dirname($this->_file))) return true;
		if ($this->creatable) {
			//if the file doesn't exist and the parent folder doesn't exist, test if the file can be created
			//if it can, create the parent folder structure
			return mkdir(dirname($this->_file) , $this->defaultPermissions, true );
		}
		return false;
	}
	
	public function get() {
		if (!$this->exists) throw new FileNotFoundException('File not found: ' . $this->_file);
		return file_get_contents($this->_file);
	}
	
	public function put($data, $append=false) {
		if ($this->pointer) $this->close();
		return $this->verifyParent()?file_put_contents($this->_file, $data, $append?FILE_APPEND:0):false;
	}
	
	public function append($data) {
		return $this->put($data, true);
	}
		
	public function copy($new) {
		if (!$this->exists) throw new FileNotFoundException('File not found: ' . $this->_file);

		if (is_string($new)) $new = new File($new);
		if (!$new->verifyParent()) return false;
		
		if (copy($this->_file, $new)) {
			return new File($new);
		} else return false;
	}

	public function move($new) {
		if (!$this->exists) throw new FileNotFoundException('File not found: ' . $this->_file);
		if ($this->pointer) $this->close();
		
		if (is_string($new)) $new = new File($new);
		if (!$new->verifyParent()) return false;
		
		if (rename($this->_file, $new)) {
			$this->_file = $new;
			return true;
		} else return false;
	}
	
	public function moveUploaded($new) {
		if (!$this->exists) throw new FileNotFoundException('File not found: ' . $this->_file);
		if ($this->pointer) $this->close();

		if (is_string($new)) $new = new File($new);
		if (!$new->verifyParent()) return false;
		
		if (move_uploaded_file($this->_file, $new)) {
			$this->_file = $new;
			return true;
		} else return false;
	}
	
	public function rename($new) {// alias to move() 
		$this->move($new);
	}
	
	public function delete() {
		if (!$this->exists) return true;
		if ($this->pointer) $this->close();
		
		//if it's just a normal file, delete it and end
		if (!$this->is_dir)	return unlink($this->_file);
		
		//resource is a folder, do a recursive delete
		
		$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_file), RecursiveIteratorIterator::CHILD_FIRST);

		for ($dir->rewind(); $dir->valid(); $dir->next()) {
		    if ($dir->isDir()) rmdir($dir->getPathname());
		    else unlink($dir->getPathname());
		}
		
		return rmdir($this->_file);
	}
	
	public function unlink() {// alias to delete() 
		return $this->delete();
	}
		
	public function touch() {
		return touch($this->_file);
	}
	
	public function seek($offset, $whence = SEEK_SET) {
		if (!$this->pointer) $this->open();
		return fseek($this->pointer, $offset, $whence);
	}
	
	public function rewind() {
		if (!$this->pointer) $this->open();
		return rewind($this->pointer);
	}
	
	public function lock($operation) {
		if (!$this->pointer) $this->open();
		return flock($this->pointer, $operation);
	}
	
	public function read($bytes) {
		if (!$this->pointer) $this->open();
		if (!$this->canRead) throw new FileInvalidAccessMode('File is not open for reading: ' . $this->_file);
		return fread($this->pointer, $bytes);
	}
	
	public function readLine() {
		if (!$this->pointer) $this->open();
		if (!$this->canRead) throw new FileInvalidAccessMode('File is not open for reading: ' . $this->_file);
		return fgets($this->pointer);
	}
	
	public function readRow() {
		if (!$this->pointer) $this->open();
		if (!$this->canRead) throw new FileInvalidAccessMode('File is not open for reading: ' . $this->_file);
		return fgetcsv($this->pointer, 0, $this->csvDelimiter, $this->csvEnclosure);
	}
	
	public function countRows() {
		$handle   = fopen($this->_file, 'r');
		$c = 0;
		while ( !feof ($handle) ) { //loop through each line
			fgets($handle);
			$c++;
		}
		fclose($handle);
		return $c;
	}
	
	/**
	 * Returns a human readable file size in B/K/M/G/T
	 * 
	 * @author     Will Bond [wb] <will@flourishlib.com>
	 * @author     Alex Leeds [al] <alex@kingleeds.com>
	 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
	 * @param  integer $decimal_places  The number of decimal places to display
	 * @return string
	 */
	public function getFormattedSize($decimal_places=1) {
		$bytes = $this->size;
		$suffixes  = array('B', 'KB', 'MB', 'GB', 'TB');
		$sizes     = array(1, 1024, 1048576, 1073741824, 1099511627776);
		$suffix    = (!$bytes) ? 0 : floor(log($bytes)/6.9314718);
		return number_format($bytes/$sizes[$suffix], ($suffix == 0) ? 0 : $decimal_places) . ' ' . $suffixes[$suffix];
	}
	
	public function write($data) {
		if (!$this->pointer) $this->open();
		if (!$this->canWrite) throw new FileInvalidAccessMode('File is not open for writing: ' . $this->_file);
		return fwrite($this->pointer, $data);
	}
	
	public function writeLine($data) {
		if (!$this->pointer) $this->open();
		if (!$this->canWrite) throw new FileInvalidAccessMode('File is not open for writing: ' . $this->_file);
		return fwrite($this->pointer, $data . $this->lineEnding);
	}

	public function writeRow($data) {
		if (!$this->pointer) $this->open();
		if (!$this->canWrite) throw new FileInvalidAccessMode('File is not open for writing: ' . $this->_file);
		return fwrite($this->pointer, $data, $this->csvDelimiter, $this->csvEnclosure);
	}
	
	public function flush() {
		if (!$this->pointer) $this->open();
		if (!$this->canWrite) throw new FileInvalidAccessMode('File is not open for writing: ' . $this->_file);
		return fflush($this->pointer);
	}
	
	public function passthru() {
		if (!$this->pointer) $this->open();
		if (!$this->canRead) throw new FileInvalidAccessMode('File is not open for reading: ' . $this->_file);
		return fpassthru($this->pointer);
	}
	
	/**
	 * Prints the contents of the file
	 * 
	 * This method is primarily intended for when PHP is used to control access
	 * to files.
	 * 
	 * Be sure to turn off output buffering and close the session, if open, to
	 * prevent performance issues. 
	 * 
	 * @author Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
	 * @param  boolean $headers   If HTTP headers for the file should be included
	 * @param  mixed   $filename  Present the file as an attachment instead of just outputting type headers - if a string is passed, that will be used for the filename, if `TRUE` is passed, the current filename will be used
	 * @return fFile  The file object, to allow for method chaining
	 */
	public function output($headers, $filename=NULL) {
		if (ob_get_level() > 0) throw new FileInvalidAccessMode('File cannot be output when output buffering is enabled.');
		
		if ($headers) {
			if ($filename !== NULL) {
				if ($filename === TRUE) { $filename = $this->name;	}
				header('Content-Disposition: attachment; filename="' . $filename . '"');		
			}
			header('Cache-Control: ');
			header('Content-Length: ' . $this->size);
			header('Content-Type: ' . $this->mimetype);
			header('Expires: ');
			header('Last-Modified: ' . date('D, d M Y H:i:s', $this->time_modified));
			header('Pragma: ');	
		}
		
		readfile($this->_file);
		
	}
	
	public function truncate($size) {
		if (!$this->pointer) $this->open();
		if (!$this->canWrite) throw new FileInvalidAccessMode('File is not open for writing: ' . $this->_file);
		return ftruncate($this->pointer, $size);
	}
		
	public function open($mode=null) {
		if ($mode) $this->mode = $mode;
		if (!$this->mode) $this->mode = 'r';
		
		// If mode is 'r', check that the file exists.
		if (strpos($this->mode, 'r') !== false && !file_exists($this->_file))
			throw new FileNotFoundException('File not found: ' . $this->_file);
		
		// If mode is 'x', check that the file does not exist.		
		if (strpos($this->mode, 'x') !== false && file_exists($this->_file))
			throw new FileAlreadyExistsException('File already exists: ' . $this->_file);
			
		switch ($this->mode) {
			case 'r':	$this->canRead = true;$this->canWrite = false;break;
			
			case 'w':
			case 'a':
			case 'c':	$this->canRead = false;$this->canWrite = true;break;
			
			case 'r+':
			case 'w+':
			case 'x+':
			case 'c+':	$this->canRead = true;$this->canWrite = true;break;
		}

		if ($this->canWrite) { //file is being opened for writing
			if (!$this->verifyParent()) throw new FileException('Could not create directory structure: '. $this->_file);
		}

		$this->pointer = fopen($this->_file, $this->mode);
        if (!$this->pointer) throw new FileException('Failed to open file: '. $this->_file);
		
	}
		
	public function close() {
		fclose($this->pointer);
		$this->pointer = null;
	}
	
	public function __destruct() {
		@fclose($this->pointer);
	}
	
	
	
	
	
	
	public static function GetTemporary() {
		return new File(tempnam(sys_get_temp_dir(), 'tmp'));
	}
	
	
	public static function GetWebsafeFilename($original) {
		return preg_replace('/[^a-zA-Z0-9\-\_\.]/','', pathinfo($original, PATHINFO_FILENAME) ).'.'.uniqid().'.'.pathinfo($original, PATHINFO_EXTENSION);		
	}
	
	
	/**
	 * Looks for specific bytes in a file to determine the mime type of the file
	 * 
	 * @author     Will Bond [wb] <will@flourishlib.com>
	 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
	 * @param  string $content    The first 4 bytes of the file content to use for byte checking
	 * @param  string $extension  The extension of the filetype, only used for difficult files such as Microsoft office documents
	 * @return string  The mime type of the file
	 */
	static private function DetermineMimeTypeByContents($content, $extension) {
		$length = strlen($content);
		$_0_8   = substr($content, 0, 8);
		$_0_6   = substr($content, 0, 6);
		$_0_5   = substr($content, 0, 5);
		$_0_4   = substr($content, 0, 4);
		$_0_3   = substr($content, 0, 3);
		$_0_2   = substr($content, 0, 2);
		$_8_4   = substr($content, 8, 4);
		
		// Images
		if ($_0_4 == "MM\x00\x2A" || $_0_4 == "II\x2A\x00") {
			return 'image/tiff';	
		}
		
		if ($_0_8 == "\x89PNG\x0D\x0A\x1A\x0A") {
			return 'image/png';	
		}
		
		if ($_0_4 == 'GIF8') {
			return 'image/gif';	
		}
		
		if ($_0_2 == 'BM' && $length > 14 && in_array($content[14], array("\x0C", "\x28", "\x40", "\x80"))) {
			return 'image/x-ms-bmp';	
		}
		
		$normal_jpeg    = $length > 10 && in_array(substr($content, 6, 4), array('JFIF', 'Exif'));
		$photoshop_jpeg = $length > 24 && $_0_4 == "\xFF\xD8\xFF\xED" && substr($content, 20, 4) == '8BIM';
		if ($normal_jpeg || $photoshop_jpeg) {
			return 'image/jpeg';	
		}
		
		if (preg_match('#^[^\n\r]*\%\!PS-Adobe-3#', $content)) {
			return 'application/postscript';			
		}
		
		if ($_0_4 == "\x00\x00\x01\x00") {
			return 'application/vnd.microsoft.icon';	
		}
		
		
		// Audio/Video
		if ($_0_4 == 'MOVI') {
			if (in_array($_4_4, array('moov', 'mdat'))) {
				return 'video/quicktime';
			}	
		}
		
		if ($length > 8 && substr($content, 4, 4) == 'ftyp') {
			
			$_8_3 = substr($content, 8, 3);
			$_8_2 = substr($content, 8, 2);
			
			if (in_array($_8_4, array('isom', 'iso2', 'mp41', 'mp42'))) {
				return 'video/mp4';
			}	
			
			if ($_8_3 == 'M4A') {
				return 'audio/mp4';
			}
			
			if ($_8_3 == 'M4V') {
				return 'video/mp4';
			}
			
			if ($_8_3 == 'M4P' || $_8_3 == 'M4B' || $_8_2 == 'qt') {
				return 'video/quicktime';	
			}
		}
		
		// MP3
		if (($_0_2 & "\xFF\xF6") == "\xFF\xF2") {
			if (($content[2] & "\xF0") != "\xF0" && ($content[2] & "\x0C") != "\x0C") {
				return 'audio/mpeg';
			}	
		}
		if ($_0_3 == 'ID3') {
			return 'audio/mpeg';	
		}
		
		if ($_0_8 == "\x30\x26\xB2\x75\x8E\x66\xCF\x11") {
			if ($content[24] == "\x07") {
				return 'audio/x-ms-wma';
			}
			if ($content[24] == "\x08") {
				return 'video/x-ms-wmv';
			}
			return 'video/x-ms-asf';	
		}
		
		if ($_0_4 == 'RIFF' && $_8_4 == 'AVI ') {
			return 'video/x-msvideo';	
		}
		
		if ($_0_4 == 'RIFF' && $_8_4 == 'WAVE') {
			return 'audio/x-wav';	
		}
		
		if ($_0_4 == 'OggS') {
			$_28_5 = substr($content, 28, 5);
			if ($_28_5 == "\x01\x76\x6F\x72\x62") {
				return 'audio/vorbis';	
			}
			if ($_28_5 == "\x07\x46\x4C\x41\x43") {
				return 'audio/x-flac';	
			}
			// Theora and OGM	
			if ($_28_5 == "\x80\x74\x68\x65\x6F" || $_28_5 == "\x76\x69\x64\x65") {
				return 'video/ogg';		
			}
		}
		
		if ($_0_3 == 'FWS' || $_0_3 == 'CWS') {
			return 'application/x-shockwave-flash';	
		}
		
		if ($_0_3 == 'FLV') {
			return 'video/x-flv';	
		}
		
		
		// Documents
		if ($_0_5 == '%PDF-') {
			return 'application/pdf'; 	
		}
		
		if ($_0_5 == '{\rtf') {
			return 'text/rtf';	
		}
		
		// Office '97-2003 or Office 2007 formats
		if ($_0_8 == "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" || $_0_8 == "PK\x03\x04\x14\x00\x06\x00") {
			if (in_array($extension, array('xlsx', 'xls', 'csv', 'tab'))) {
				return 'application/vnd.ms-excel';	
			}
			if (in_array($extension, array('pptx', 'ppt'))) {	
				return 'application/vnd.ms-powerpoint';
			}
			// We default to word since we need something if the extension isn't recognized
			return 'application/msword';
		}
		
		if ($_0_8 == "\x09\x04\x06\x00\x00\x00\x10\x00") {
			return 'application/vnd.ms-excel';	
		}
		
		if ($_0_6 == "\xDB\xA5\x2D\x00\x00\x00" || $_0_5 == "\x50\x4F\x5E\x51\x60" || $_0_4 == "\xFE\x37\x0\x23" || $_0_3 == "\x94\xA6\x2E") {
			return 'application/msword';	
		}
		
		
		// Archives
		if ($_0_4 == "PK\x03\x04") {
			return 'application/zip';	
		}
		
		if ($length > 257) {
			if (substr($content, 257, 6) == "ustar\x00") {
				return 'application/x-tar';	
			}
			if (substr($content, 257, 8) == "ustar\x40\x40\x00") {
				return 'application/x-tar';	
			}
		}
		
		if ($_0_4 == 'Rar!') {
			return 'application/x-rar-compressed';	
		}
		
		if ($_0_2 == "\x1F\x9D") {
			return 'application/x-compress';	
		}
		
		if ($_0_2 == "\x1F\x8B") {
			return 'application/x-gzip';	
		}
		
		if ($_0_3 == 'BZh') {
			return 'application/x-bzip2';	
		}
		
		if ($_0_4 == "SIT!" || $_0_4 == "SITD" || substr($content, 0, 7) == 'StuffIt') {
			return 'application/x-stuffit';	
		}	
		
		
		// Text files
		if (strpos($content, '<?xml') !== FALSE) {
			if (stripos($content, '<!DOCTYPE') !== FALSE) {
				return 'application/xhtml+xml';
			}
			if (strpos($content, '<svg') !== FALSE) {
				return 'image/svg+xml';
			}
			if (strpos($content, '<rss') !== FALSE) {
				return 'application/rss+xml';
			}
			return 'application/xml';	
		}   
		
		if (strpos($content, '<?php') !== FALSE || strpos($content, '<?=') !== FALSE) {
			return 'application/x-httpd-php';	
		}
		
		if (preg_match('#^\#\![/a-z0-9]+(python|perl|php|ruby)$#mi', $content, $matches)) {
			switch (strtolower($matches[1])) {
				case 'php':
					return 'application/x-httpd-php';
				case 'python':
					return 'application/x-python';
				case 'perl':
					return 'application/x-perl';
				case 'ruby':
					return 'application/x-ruby';
			}	
		}
		
		
		// Default
		return 'application/octet-stream';
	}
	
	
	/**
	 * Uses the extension of the all-text file to determine the mime type
	 * 
	 * @author     Will Bond [wb] <will@flourishlib.com>
	 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
	 * @param  string $extension  The file extension
	 * @return string  The mime type of the file
	 */
	static private function DetermineMimeTypeByExtension($extension) {
		switch ($extension) {
			case 'css':
				return 'text/css';
			
			case 'csv':
				return 'text/csv';
			
			case 'htm':
			case 'html':
			case 'xhtml':
				return 'text/html';
				
			case 'ics':
				return 'text/calendar';
			
			case 'js':
				return 'application/javascript';
			
			case 'php':
			case 'php3':
			case 'php4':
			case 'php5':
			case 'inc':
				return 'application/x-httpd-php';
				
			case 'pl':
			case 'cgi':
				return 'application/x-perl';
			
			case 'py':
				return 'application/x-python';
			
			case 'rb':
			case 'rhtml':
				return 'application/x-ruby';
			
			case 'rss':
				return 'application/rss+xml';
				
			case 'tab':
				return 'text/tab-separated-values';
			
			case 'vcf':
				return 'text/x-vcard';
			
			case 'xml':
				return 'application/xml';
			
			default:
				return 'text/plain';	
		}
	}
}

// Exceptions.

class FileException extends Exception { }
class FileNotFoundException extends FileException { }
class FileAlreadyExistsException extends FileException { }
class FileInvalidAccessMode extends FileException { }
