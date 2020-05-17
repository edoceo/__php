<?php
/**
 * More functions for Randomness
 */

function __random_hash($hash='sha256', $size=256)
{
	return __base64_encode_url(hash($hash, openssl_random_pseudo_bytes($size), true));
}
