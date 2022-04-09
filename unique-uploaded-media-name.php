<?php

/**
 * Plugin Name: Unique Uploaded Media Name
 * Plugin URI:  https://github.com/aifdn/unique-uploaded-media-name
 * Description: Unique uploaded media names by adding some extra random string
 * Author: Sazzad Hossain Sharkar
 * Author URI: https://github.com/shsbd
 * Version: 1.0.1
 * License: GPLv2 or later
 */

class UniqueUploadedMediaName {
	public static
	function randomString(
		$type = 'no_zero', $length = 8
	) {
		switch($type) {
			case 'all_string':
				$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alpha':
				$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'capital':
				$pool = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alphabet':
				$pool = 'abcdefghkmnprstuvwyz';
				break;
			case 'hexadecimal':
				$pool = '0123456789abcdef';
				break;
			case 'numeric':
				$pool = '0123456789';
				break;
			case 'no_zero':
				$pool = '123456789';
				break;
			case 'distinct':
				$pool = '2345679acdefhjklmnprstuvwxyz';
				break;
			default:
				$pool = (string) $type;
				break;
		}
		$crypto_rand_secure =
			function($min, $max) {
				$range = $max - $min;
				if($range < 0) {
					return $min;
				} // not so random...
				$log    = log($range, 2);
				$bytes  = (int) ($log / 8) + 1; // length in bytes
				$bits   = (int) $log + 1; // length in bits
				$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
				do {
					$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
					$rnd = $rnd &= $filter; // discard irrelevant bits
				} while($rnd >= $range);

				return $min + $rnd;
			};
		$token              = '';
		$max                = strlen($pool);
		for($i = 0; $i < $length; $i ++) {
			$token .= $pool[$crypto_rand_secure(0, $max)];
		}

		return $token;
	}

	public static
	function stringTen() {
		$nozero   = self::randomString('numeric', 6);
		$alphabet = self::randomString('all_string', 8);

		return $nozero . '-' . $alphabet;
	}
}

function unique_uploaded_media_name($filename) {
	$sanitized_filename = remove_accents($filename); // Convert to ASCII

	// Standard replacements
	$invalid = [
		'  '  => '-',
		' '   => '-',
		'%20' => '-',
		'_'   => '-',
		'-'   => '-',
	];

	$sanitized_filename = str_replace(array_keys($invalid), array_values($invalid), $sanitized_filename);

	// Comment the following line to make Japanese/Korean/Chinese friendly. Or, this line would substitute every character except alphabet.
	//$sanitized_filename = preg_replace('/[^A-Za-z0-9-\. ]/', '', $sanitized_filename);
	$sanitized_filename = preg_replace('/\.(?=.*\.)/', '', $sanitized_filename);
	$sanitized_filename = preg_replace('/-+/', '-', $sanitized_filename);
	$sanitized_filename = str_replace('-.', '.', $sanitized_filename);
	$sanitized_filename = strtolower($sanitized_filename);

	$info = pathinfo($filename);
	$ext  = empty($info['extension']) ? '' : '.' . $info['extension'];
	$name = wp_basename($sanitized_filename, $ext);
	
	// these extensions are picked according to https://wordpress.com/support/accepted-filetypes
	$media_extensions = ['jpg', 'jpeg', 'png', 'ico', 'gif', 'webp', 'svg', 'mp3', 'm4a', 'ogg', 'wav', 'mp4', 'm4v', 'mov', 'wmv', 'avi', 'mpg', 'ogv', '3gp', '3g2', 'vtt'];
		
	// if file extension matches $media_extensions then rename
	foreach ($media_extensions as &$value) {
        if (strtolower($info['extension']) == $value) {
			// rename file name with arbitrary alphabet and numbers
            return UniqueUploadedMediaName::stringTen() . $ext;

			// add arbitrary alphabet and numbers after file name.
			// return $name . '-' . UniqueUploadedMediaName::stringTen() . $ext;
        }
    }

	return $name . $ext;
	
}

add_filter('sanitize_file_name', 'unique_uploaded_media_name', 10);

/*
* WARNING
* If the file is renamed twice
* e.g. from 'kevin.jgp' to 'kevin-661081-wncnfli3-401971-g6zA1rvO.jpg'
* if there is Offload Media plugin installed
* then it is due to line 2074 in `amazon-s3-and-cloudfront/classes/amazon-s3-and-cloudfront.php`:

* // sanitize the file name before we begin processing
* $filename = sanitize_file_name( $filename );

* just comment this line.
*/
