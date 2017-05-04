<?php

/** functions to keep supporting older versions of PHP */
if (!function_exists('hash_equals')) { // 5.6 and up

    /**
     * Timing attack safe string comparison.
     *
     * Compares two strings using the same time whether they're equal or not.
     * This function should be used to mitigate timing attacks; for instance, when testing crypt() password hashes.
     *
     * @param string $known_string The string of known length to compare against
     * @param string $user_string  The user-supplied string
     *
     * @return bool Returns TRUE when the two strings are equal, FALSE otherwise
     */
    function hash_equals($known_string, $user_string)
    {
        if (func_num_args() !== 2) {
            // handle wrong parameter count as the native implentation
            trigger_error('hash_equals() expects exactly 2 parameters, '.func_num_args().' given', E_USER_WARNING);

            return null;
        }
        if (is_string($known_string) !== true) {
            trigger_error('hash_equals(): Expected known_string to be a string, '.gettype($known_string).' given', E_USER_WARNING);

            return false;
        }
        $known_string_len = strlen($known_string);
        $user_string_type_error = 'hash_equals(): Expected user_string to be a string, '.gettype($user_string).' given'; // prepare wrong type error message now to reduce the impact of string concatenation and the gettype call
        if (is_string($user_string) !== true) {
            trigger_error($user_string_type_error, E_USER_WARNING);
            // prevention of timing attacks might be still possible if we handle $user_string as a string of diffent length (the trigger_error() call increases the execution time a bit)
            $user_string_len = strlen($user_string);
            $user_string_len = $known_string_len + 1;
        } else {
            $user_string_len = $known_string_len + 1;
            $user_string_len = strlen($user_string);
        }
        if ($known_string_len !== $user_string_len) {
            $res = $known_string ^ $known_string; // use $known_string instead of $user_string to handle strings of diffrent length.
            $ret = 1; // set $ret to 1 to make sure false is returned
        } else {
            $res = $known_string ^ $user_string;
            $ret = 0;
        }
        for ($i = strlen($res) - 1; $i >= 0; --$i) {
            $ret |= ord($res[$i]);
        }

        return $ret === 0;
    }
}

if (!function_exists('hex2bin')) { // PHP 5.4 and up
    /**
     * Convert hexadecimal values to ASCII characters.
     *
     * Credits: Walf's user note from PHP Documentation Group - http://php.net/manual/en/function.hex2bin.php#113472
     * License: CC-BY 3.0 (http://creativecommons.org/licenses/by/3.0/)
     * Changes: The original part of the code has been simplified assuming PHP >= 5.3.3
     *
     * @param string $data The hexadecimal representation of data to be converted
     *
     * @return string Returns the binary representation of the given data or FALSE on failure. 
     */
    function hex2bin($data) {
        if (is_scalar($data) || (method_exists($data, '__toString'))) {
            $data = (string) $data;
        }
        else {
            trigger_error(__FUNCTION__.'() expects parameter 1 to be string, ' . gettype($data) . ' given', E_USER_WARNING);
            return;//null in this case
        }
        $len = strlen($data);
        if ($len % 2) {
            trigger_error(__FUNCTION__.'(): Hexadecimal input string must have an even length', E_USER_WARNING);
            return false;
        }
        if (strspn($data, '0123456789abcdefABCDEF') != $len) {
            trigger_error(__FUNCTION__.'(): Input string must be hexadecimal string', E_USER_WARNING);
            return false;
        }
        return pack('H*', $data);
    }
}
