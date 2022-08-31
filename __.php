<?php
/**
 * __php main
 */


/*
 * Wrapper for htmlentities
 */
function __h($x) : string
{
	return htmlentities($x, ENT_QUOTES, 'UTF-8', true);
}


/**
 * Wrapper for base64 encode, url-safe
 */
function __base64_encode_url($x) : string
{
	return rtrim(strtr(base64_encode($x), '+/', '-_'), '=');
}

function __base64_decode_url($x) : string
{
	return base64_decode(str_pad(strtr($x, '-_', '+/'), (strlen($x) % 4), '=', STR_PAD_RIGHT));
}

/**
 * Wrapper to simplify short-lived crypted data
 */
function __encrypt($d, $k=null)
{
	$d = openssl_encrypt($d, 'AES-256-ECB', $k, true);
	return __base64_encode_url($d);
}

function __decrypt($d, $k=null)
{
	$d = __base64_decode_url($d);
	return trim(openssl_decrypt($d, 'AES-256-ECB', $k, true));
}


/*
 * Wrapper for cURL to set some defaults
 */
function __curl_init($url, $opt=null)
{
	$req = curl_init($url);

	curl_setopt($req, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

	// Booleans
	curl_setopt($req, CURLOPT_AUTOREFERER, true);
	curl_setopt($req, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($req, CURLOPT_COOKIESESSION, false);
	curl_setopt($req, CURLOPT_CRLF, false);
	curl_setopt($req, CURLOPT_FAILONERROR, false);
	curl_setopt($req, CURLOPT_FILETIME, true);
	curl_setopt($req, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($req, CURLOPT_FORBID_REUSE, false);
	curl_setopt($req, CURLOPT_FRESH_CONNECT, false);
	curl_setopt($req, CURLOPT_HEADER, false);
	curl_setopt($req, CURLOPT_NETRC, false);
	curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($req, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($req, CURLINFO_HEADER_OUT,true);

	// curl_setopt($req, CURLOPT_BUFFERSIZE, 16384);
	curl_setopt($req, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($req, CURLOPT_MAXREDIRS, 0);
	// curl_setopt($req, CURLOPT_SSL_VERIFYHOST, 0);
	// curl_setopt($req, CURLOPT_SSLVERSION, 3); // 2, 3 or GnuTLS
	curl_setopt($req, CURLOPT_TIMEOUT, 60);

	if (defined('__PHP_USERAGENT')) {
		curl_setopt($req, CURLOPT_USERAGENT, __PHP_USERAGENT);
	}

	if (!empty($opt) && is_array($opt)) {
		curl_setopt($req, $opt);
	}

	return $req;
}

/**
	Wrapper for Date Formating
	@param $f Date Format in either date() or strftime format (don't mix!)
	@param $d Date/Time
	@param $tz Time Zone
*/
function __date($f, $d=null, $tz=null)
{
	$r = $d;

	if (empty($d) && empty($tz)) {
		return '-';
	}

	if (!empty($r)) {
		// Match UNIX Timestamp (may be negative)
		if (preg_match('/^\-?\d+$/', $r)) {
			$r = '@' . $r;
		}
	}

	if (!empty($tz)) {
		if (is_string($tz)) {
			$tz = new DateTimeZone($tz);
		}
	}

	try {

		$dt = new DateTime($r); //, $tz);

		if (!empty($tz)) {
			$dt->setTimezone($tz);
		}

	} catch (\Exception $e) {
		return $r;
	}

	// A strftime Type Format
	if (strpos($f, '%') === false) {
		$r = $dt->format($f);
	} else {
		$tz0 = date_default_timezone_get();
		if ($tz) {
			date_default_timezone_set($tz);
		}
		$r = strftime($f, $dt->getTimestamp());
		date_default_timezone_set($tz0);
	}

	return $r;

}


/**
 * WapperJSON decode to array type
 */
function __json_decode(string $x)
{
	return json_decode($x, true, 512, JSON_INVALID_UTF8_IGNORE | JSON_OBJECT_AS_ARRAY);
}


/*
 * Wapper for json_encode
 */
function __json_encode($x, int $f=0)
{
	return json_encode($x, JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | $f);
}


/**
 * Wrapper for parse_str to not leak vars
 */
function __parse_str(string $x)
{
	$r = array();
	parse_str($x, $r);
	return $r;
}


/**
	Sort a Keyed Array, Recursively
	@return bool
*/
function __ksort_r(&$array)
{
	foreach ($array as &$value) {
		if (is_array($value)) {
			// If isArray and all Values are String then sort by Value
			//$idx = array_keys($value) === range(0, count($value) - 1);
			//if ($idx) {
			__ksort_r($value);
		}
	}

	return ksort($array);
}


/**
	Array Diff by Key and Value - Recursive
	@param $a0 Old Data Array
	@param $a1 New Data Array
	@return Array of Keys with Old and New values
*/
function __array_diff_keyval_r($a0, $a1)
{
	$ret = array();

	if (empty($a0)) {
		$a0 = array();
	}

	if (empty($a1)) {
		$a1 = array();
	}

	$key_a = array_keys($a0);
	$key_b = array_keys($a1);
	$key_list = array_merge($key_a, $key_b);

	foreach ($key_list as $key) {

		$v0 = $a0[$key];
		$v1 = $a1[$key];

		if ($v0 != $v1) {

			if (is_array($v0) && is_array($v1)) {
				$x = __array_diff_keyval_r($v0, $v1);
				$ret[$key] = $x;
			} else {
				$ret[$key] = array(
					'old' => $v0,
					'new' => $v1,
				);
			}
		}
	}

	return $ret;
}


/**
	Makes a temp file that automatically cleans up
	@param
*/
function __tempnam($pre=null)
{
	$pre = trim($pre);
	if (empty($pre)) {
		$pre = 'app-tmp';
	}

	$tfn = tempnam(sys_get_temp_dir(), $pre);

	$tfc = function() use ($tfn) {
		unlink($tfn);
	};
	register_shutdown_function($tfc);

	return $tfn;
}


/**
 * Makes a slug from Text
 */
function __text_stub($x)
{
	$x = strtolower($x);
	$x = preg_replace('/[^\w\-]+/', '-', $x);
	$x = preg_replace('/\-+/', '-', $x);
	$x = trim($x, '-');
	return $x;
}


/**
	Exit with Text Content
*/
function __exit_html($html, $code=200)
{
	while (ob_get_level()) ob_end_clean();

	__exit_code($code);

	header('content-type: text/html');

	echo $html;

	exit(0);
}

function __exit_json($data, $code=200)
{
	while (ob_get_level()) ob_end_clean();

	__exit_code($code);

	if (!is_string($data)) {
		$data = __json_encode($data);
	}

	header('cache-control: no-cache');
	header('content-type: application/json', true);

	echo $data;

	exit(0);
}


/**
	Exit with Text Content
*/
function __exit_text($text, $code=200)
{
	while (ob_get_level()) ob_end_clean();

	__exit_code($code);

	if (!is_string($text)) {
		$text = json_encode($text, JSON_INVALID_UTF8_IGNORE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	header('cache-control: no-cache');
	header('content-type: text/plain');

	echo $text;

	exit(0);
}

/**
	Sets the HTTP Header Code
*/
function __exit_code($code)
{
	$map_code = array(
		200 => 'OK',
		201 => 'Created',
		204 => 'No Content',
		304 => 'Not Modified',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		409 => 'Conflict',
		410 => 'Gone',
		500 => 'Server Error',
		503 => 'Unavailable',
		504 => 'Gateway Timeout',
	);
	$text = $map_code[$code];
	$head = trim(sprintf('HTTP/1.1 %d %s', $code, $text));

	header($head, true, $code);
	// header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK', true, 200);
	// header($_SERVER['SERVER_PROTOCOL'] . ' 403 Not Authorized', true, 403);
}

/**
	Determine which MIME type the request wants
*/
function __mime_type_want()
{
	$want_list = explode(',', $_SERVER['HTTP_ACCEPT']);
	return trim($want_list[0]);
}
