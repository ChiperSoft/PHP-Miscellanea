<?php 
class Console {
	/**
	 * Sends a message to the server's system console.
	 *
	 * @param string $message Text to send to the console
	 * @param string $source Process name to identify the line as
	 * @param string $level Priority level of the message.  See php reference for syslog for details
	 * @static
	 */
	static function Log ($message, $source='phPit', $level=LOG_WARNING) {
		if (!is_string($message)) $message = json_encode($message);
		openlog($source, LOG_PID | LOG_PERROR, LOG_USER);
		syslog($level, $message);
		closelog();
	}
}