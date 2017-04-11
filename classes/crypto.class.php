<?php

/*
  This file is part of CoDevTT.

  CoDevTT is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  CoDev-Timetracking is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This class is base on a partial copy of mantis files "authentication_api.php" and "crypto_api.php" and utility_api.php.
 * It is used to generate cookie string in order to complete the "cookie_string" column of mantis_user_table in mantis database.
 *
 * @author a608584
 */
class Crypto {

    const MD5 = 1;

    /**
     * Call generate methode according to Mantis version
     * @return type
     */
    public function generate_cookie_string() {
        if (!Tools::isMantisV1_2()) {
            return $this->crypto_generate_uri_safe_nonce(64);
        } else {
            return $this->auth_generate_cookie_string();
        }
    }

    // =============== From authentication_api.php ===============

    /**
     * Generate a random and unique string to use as the identifier for the login
     * cookie.
     * Check Mantis version
     * @return string Random and unique 384bit cookie string of encoded according to the base64 with URI safe alphabet approach described in RFC4648
     * @access public
     */
    public function auth_generate_unique_cookie_string() {
        do {
            $t_cookie_string = $this->generate_cookie_string();
        } while (!$this->auth_is_cookie_string_unique($t_cookie_string));

        return $t_cookie_string;
    }

    /**
     * Return true if the cookie login identifier is unique, false otherwise
     * @param string $p_cookie_string Cookie string.
     * @return boolean indicating whether cookie string is unique
     * @access public
     */
    private function auth_is_cookie_string_unique($p_cookie_string) {
        $query = "SELECT COUNT(*) FROM mantis_user_table WHERE cookie_string='$p_cookie_string'";
        $result = SqlWrapper::getInstance()->sql_query($query);

        if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
        }

        while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $count = $row->count;
        }

        if ($count > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * --- For anterior version of 1_3 Mantis version ---
     * Generate a string to use as the identifier for the login cookie
     * It is not guaranteed to be unique and should be checked
     * The string returned should be 64 characters in length
     * @return string 64 character cookie string
     * @access public
     */
    public function auth_generate_cookie_string() {
        $t_val = mt_rand(0, mt_getrandmax()) + mt_rand(0, mt_getrandmax());
        $t_val = md5($t_val) . md5(time());
        return $t_val;
    }

    /**
     * Encrypt and return the plain password given, as appropriate for the current
     *  global login method.
     *
     * When generating a new password, no salt should be passed in.
     * When encrypting a password to compare to a stored password, the stored
     *  password should be passed in as salt.  If the authentication method is CRYPT then
     *  crypt() will extract the appropriate portion of the stored password as its salt
     * 
     * For now, only MD5 encryption is available
     *
     * @param string $p_password Password.
     * @param string $p_salt     Salt, defaults to null.
     * @param string $p_method   Logon method, defaults to null (use configuration login method).
     * @return string processed password, maximum DB_FIELD_SIZE_PASSWORD chars in length
     * @access public
     */
    public function auth_process_plain_password($p_password, $p_salt = null, $p_method = null) {
        $t_login_method = MD5; // config_get('login_method');
        if ($p_method !== null) {
            $t_login_method = $p_method;
        }

        switch ($t_login_method) {
            case CRYPT:

                # a null salt is the same as no salt, which causes a salt to be generated
                # otherwise, use the salt given
                $t_processed_password = crypt($p_password, $p_salt);
                break;
            case MD5:
                $t_processed_password = md5($p_password);
                break;
            case BASIC_AUTH:
            case PLAIN:
            default:
                $t_processed_password = $p_password;
                break;
        }

        # cut this off to DB_FIELD_SIZE_PASSWORD characters which the largest possible string in the database
        return $this->utf8_substr($t_processed_password, 0, 64);
    }

    // =============== From crypto_api.php ===============

    /**
     * --- For version 1_3 of Mantis ---
     * Generate a nonce encoded using the base64 with URI safe alphabet approach
     * described in RFC4648. Note that the minimum length is rounded up to the next
     * number with a factor of 4 so that padding is never added to the end of the
     * base64 output. This means the '=' padding character is never present in the
     * output. Due to the reduced character set of base64 encoding, the actual
     * amount of entropy produced by this function for a given output string length
     * is 3/4 (0.75) that of raw unencoded output produced with the
     * crypto_generate_strong_random_string( $p_bytes ) function.
     * @param integer $p_minimum_length Minimum number of characters required for the nonce.
     * @return string Nonce encoded according to the base64 with URI safe alphabet approach described in RFC4648
     */
    public function crypto_generate_uri_safe_nonce($p_minimum_length) {
        $t_length_mod4 = $p_minimum_length % 4;
        $t_adjusted_length = $p_minimum_length + 4 - ($t_length_mod4 ? $t_length_mod4 : 4);
        $t_raw_bytes_required = ( $t_adjusted_length / 4 ) * 3;
        if (!$this->is_windows_server()) {
            $t_random_bytes = $this->crypto_generate_strong_random_string($t_raw_bytes_required);
        } else {
            # It's currently not possible to generate strong random numbers
            # with PHP on Windows so we have to resort to using PHP's
            # built-in insecure PRNG.
            $t_random_bytes = $this->crypto_generate_random_string($t_raw_bytes_required, false);
        }
        $t_base64_encoded = base64_encode($t_random_bytes);
        # Note: no need to translate trailing = padding characters because our
        # length rounding ensures that padding is never required.
        $t_random_nonce = strtr($t_base64_encoded, '+/', '-_');
        return $t_random_nonce;
    }

    /**
     * Generate a strong random string (raw binary output) for cryptographic
     * purposes such as nonces, IVs, default passwords, etc. If a strong source
     * of randomness is not available, this function will fail and produce an
     * error. Strong randomness is different from weak randomness in that a strong
     * randomness generator doesn't produce predictable output and has much higher
     * entropy. Where randomness is being used for cryptographic purposes, a strong
     * source of randomness should always be used.
     * @param integer $p_bytes Number of bytes of strong randomness required.
     * @return string Raw binary string containing the requested number of bytes of random output
     */
    private function crypto_generate_strong_random_string($p_bytes) {
        $t_random_string = $this->crypto_generate_random_string($p_bytes, true);
        if ($t_random_string === null) {
            trigger_error(ERROR_CRYPTO_CAN_NOT_GENERATE_STRONG_RANDOMNESS, ERROR);
        }
        return $t_random_string;
    }

    /**
     * Generate a random string (raw binary output) for cryptographic purposes such
     * as nonces, IVs, default passwords, etc. This function will attempt to
     * generate strong randomness but can optionally be used to generate weaker
     * randomness if less security is needed or a strong source of randomness isn't
     * available. The use of weak randomness for cryptographic purposes is strongly
     * discouraged because it contains low entropy and is predictable.
     *
     * @param integer $p_bytes                    Number of bytes of randomness required.
     * @param boolean $p_require_strong_generator Whether or not a weak source of randomness can be used by this function.
     * @return string|null Raw binary string containing the requested number of bytes of random output or null if the output couldn't be created
     */
    private function crypto_generate_random_string($p_bytes, $p_require_strong_generator = true) {
        # First we attempt to use the secure PRNG provided by OpenSSL in PHP
        if (function_exists('openssl_random_pseudo_bytes')) {
            $t_random_bytes = openssl_random_pseudo_bytes($p_bytes, $t_strong);
            if ($t_random_bytes !== false) {
                if ($p_require_strong_generator && $t_strong === true) {
                    $t_random_string = $t_random_bytes;
                } else if (!$p_require_strong_generator) {
                    $t_random_string = $t_random_bytes;
                }
            }
        }

        # Attempt to use mcrypt_create_iv - this is built into newer versions of php on windows
        # if the mcrypt extension is enabled on Linux, it takes random data from /dev/urandom
        if (!isset($t_random_string)) {
            if (function_exists('mcrypt_create_iv') && ( version_compare(PHP_VERSION, '5.3.7') >= 0 || !is_windows_server() )
            ) {
                $t_random_bytes = mcrypt_create_iv($p_bytes, MCRYPT_DEV_URANDOM);
                if ($t_random_bytes !== false && strlen($t_random_bytes) === $p_bytes) {
                    $t_random_string = $t_random_bytes;
                }
            }
        }

        # Next we try to use the /dev/urandom PRNG provided on Linux systems. This
        # is nowhere near as secure as /dev/random but it is still satisfactory for
        # the needs of MantisBT, especially given the fact that we don't want this
        # function to block while waiting for the system to generate more entropy.
        if (!isset($t_random_string)) {
            if (!is_windows_server()) {
                $t_urandom_fp = @fopen('/dev/urandom', 'rb');
                if ($t_urandom_fp !== false) {
                    $t_random_bytes = @fread($t_urandom_fp, $p_bytes);
                    if ($t_random_bytes !== false) {
                        $t_random_string = $t_random_bytes;
                    }
                    @fclose($t_urandom_fp);
                }
            }
        }

        # At this point we've run out of possibilities for generating randomness
        # from a strong source. Unless weak output is specifically allowed by the
        # $p_require_strong_generator argument, we should return null as we've
        # failed to generate randomness to a satisfactory security level.
        if (!isset($t_random_string) && $p_require_strong_generator) {
            return null;
        }

        # As a last resort we have to fall back to using the insecure Mersenne
        # Twister pseudo random number generator provided in PHP. This DOES NOT
        # produce cryptographically secure randomness and thus the output of the
        # PRNG is easily guessable. In an attempt to make it harder to guess the
        # internal state of the PRNG, we salt the PRNG output with a known secret
        # and hash it.
        if (!isset($t_random_string)) {
            $t_secret_key = 'prng' . config_get_global('crypto_master_salt');
            $t_random_bytes = '';
            for ($i = 0; $i < $p_bytes; $i += 64) {
                $t_random_segment = '';
                for ($j = 0; $j < 64; $j++) {
                    $t_random_segment .= base_convert(mt_rand(), 10, 36);
                }
                $t_random_segment .= $i;
                $t_random_segment .= $t_secret_key;
                $t_random_bytes .= hash('whirlpool', $t_random_segment, true);
            }
            $t_random_string = substr($t_random_bytes, 0, $p_bytes);
            if ($t_random_string === false) {
                return null; # Unexpected error
            }
        }

        return $t_random_string;
    }

    // =============== From utility_api.php ===============

    /**
     * return true or false if the host operating system is windows
     * @return boolean
     * @access public
     */
    function is_windows_server() {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            return (PHP_WINDOWS_VERSION_MAJOR > 0);
        }
        return ('WIN' == substr(PHP_OS, 0, 3) );
    }

    /**
     * Implementation substr() function for UTF-8 encoding string.
     *
     * @param	string  $str
     * @param	int	 $offset
     * @param	int	 $length
     * @return   string
     * @link	 http://www.w3.org/International/questions/qa-forms-utf-8.html
     *
     * @license  http://creativecommons.org/licenses/by-sa/3.0/
     * @author   Nasibullin Rinat <nasibullin at starlink ru>
     * @charset  ANSI
     * @version  1.0.5
     */
    function utf8_substr($str, $offset, $length = null) {
        if (function_exists('mb_substr'))
            return mb_substr($str, $offset, $length, 'utf-8');#(PHP 4 >= 4.0.6, PHP 5)
        if (function_exists('iconv_substr'))
            return iconv_substr($str, $offset, $length, 'utf-8');#(PHP 5)
        if (!function_exists('utf8_str_split'))
            include_once 'utf8_str_split.php';
        if (!is_array($a = utf8_str_split($str)))
            return false;
        if ($length !== null)
            $a = array_slice($a, $offset, $length);
        else
            $a = array_slice($a, $offset);
        return implode('', $a);
    }

}
