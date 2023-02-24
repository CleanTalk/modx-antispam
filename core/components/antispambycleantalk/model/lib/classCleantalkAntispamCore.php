<?php


abstract class classCleantalkAntispamCore
{

    private $api_key = '';

    public function __construct( $api_key )
    {
        $this->api_key = $api_key;
    }

    public function get_api_key()
    {
        return $this->api_key;
    }

    public function set_cookies()
    {

        if( headers_sent() ) {
            return;
        }

        $cookie_test_value = array(
            'cookies_names' => array(),
            'check_value' => $this->get_api_key(),
        );

        // Submit time
        $ct_timestamp = time();
        setcookie('apbct_timestamp', $ct_timestamp, 0, '/');
        $cookie_test_value['cookies_names'][] = 'apbct_timestamp';
        $cookie_test_value['check_value'] .= $ct_timestamp;

        //Previous referer
        if(!empty($_SERVER['HTTP_REFERER'])){
            setcookie('apbct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
            $cookie_test_value['cookies_names'][] = 'apbct_prev_referer';
            $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
        }

        // Cookies test
        $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
        setcookie('apbct_cookies_test', json_encode($cookie_test_value), 0, '/');

    }

    protected function cookies_test() {

        $ct_cookies_test = null;

        if( isset($_COOKIE['apbct_cookies_test']) ) {

            $cookie_test = json_decode(stripslashes($_COOKIE['apbct_cookies_test']), true);

            $check_srting = $this->get_api_key();
            foreach( $cookie_test['cookies_names'] as $cookie_name ) {
                $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
            } unset( $cokie_name );

            if( $cookie_test['check_value'] == md5($check_srting) ) {
                $ct_cookies_test = 1;
            } else {
                $ct_cookies_test = 0;
            }
        } else {
            $ct_cookies_test = null;
        }

        return $ct_cookies_test;

    }

    abstract public function ccf_spam_test( $post );

    abstract protected function post_exclusions_check( $post );

    abstract protected function url_exclusions_check();

    abstract protected function get_sender_info( $post );

    protected function get_fields_any( $arr, $message=array(), $email = null, $nickname = array('nick' => '', 'first' => '', 'last' => ''), $subject = null, $contact = true, $prev_name = '' )
    {
        //Skip request if fields exists
        $skip_params = array(
            'ipn_track_id', 	// PayPal IPN #
            'txn_type', 		// PayPal transaction type
            'payment_status', 	// PayPal payment status
            'ccbill_ipn', 		// CCBill IPN
            'ct_checkjs', 		// skip ct_checkjs field
            'api_mode',         // DigiStore-API
            'loadLastCommentId' // Plugin: WP Discuz. ticket_id=5571
        );

        // Fields to replace with ****
        $obfuscate_params = array(
            'password',
            'pass',
            'pwd',
            'pswd'
        );

        // Skip feilds with these strings and known service fields
        $skip_fields_with_strings = array(
            // Common
            'ct_checkjs', //Do not send ct_checkjs
            'nonce', //nonce for strings such as 'rsvp_nonce_name'
            'security',
            // 'action',
            'http_referer',
            'referer-page',
            'timestamp',
            'captcha',
            // Formidable Form
            'form_key',
            'submit_entry',
            // Custom Contact Forms
            'form_id',
            'ccf_form',
            'form_page',
            // Qu Forms
            'iphorm_uid',
            'form_url',
            'post_id',
            'iphorm_ajax',
            'iphorm_id',
            // Fast SecureContact Froms
            'fs_postonce_1',
            'fscf_submitted',
            'mailto_id',
            'si_contact_action',
            // Ninja Forms
            'formData_id',
            'formData_settings',
            'formData_fields_\d+_id',
            'formData_fields_\d+_files.*',
            // E_signature
            'recipient_signature',
            'output_\d+_\w{0,2}',
            // Contact Form by Web-Settler protection
            '_formId',
            '_returnLink',
            // Social login and more
            '_save',
            '_facebook',
            '_social',
            'user_login-',
            // Contact Form 7
            '_wpcf7',
            'ebd_settings',
            'ebd_downloads_',
            'ecole_origine',
        );

        // Reset $message if we have a sign-up data
        $skip_message_post = array(
            'edd_action', // Easy Digital Downloads
        );

        foreach($skip_params as $value){
            if(@array_key_exists($value,$_GET)||@array_key_exists($value,$_POST))
                $contact = false;
        } unset($value);

        if(count($arr)){

            foreach($arr as $key => $value){

                if(gettype($value) == 'string'){

                    $tmp = strpos($value, '\\') !== false ? stripslashes($value) : $value;
                    $decoded_json_value = json_decode($tmp, true);

                    // Decoding JSON
                    if($decoded_json_value !== null){
                        $value = $decoded_json_value;

                        // Ajax Contact Forms. Get data from such strings:
                        // acfw30_name %% Blocked~acfw30_email %% s@cleantalk.org
                        // acfw30_textarea %% msg
                    }elseif(preg_match('/^\S+\s%%\s\S+.+$/', $value)){
                        $value = explode('~', $value);
                        foreach ($value as &$val){
                            $tmp = explode(' %% ', $val);
                            $val = array($tmp[0] => $tmp[1]);
                        }
                    }
                }

                if(!is_array($value) && !is_object($value)){

                    if (in_array($key, $skip_params, true) && $key != 0 && $key != '' || preg_match("/^ct_checkjs/", $key))
                        $contact = false;

                    if($value === '')
                        continue;

                    // Skipping fields names with strings from (array)skip_fields_with_strings
                    foreach($skip_fields_with_strings as $needle){
                        if (preg_match("/".$needle."/", $prev_name.$key) == 1){
                            continue(2);
                        }
                    }unset($needle);

                    // Obfuscating params
                    foreach($obfuscate_params as $needle){
                        if (strpos($key, $needle) !== false){
                            $value = $this->obfuscate_param($value);
                            continue(2);
                        }
                    }unset($needle);

                    // Removes whitespaces
                    $value = urldecode( trim( $value ) ); // Fully cleaned message
                    $value_for_email = trim( $value );

                    if (strpos($value_for_email, ' ') !== false) {
                        preg_match("/^\S+/", $value_for_email, $left);
                        preg_match("/@\S+$/", $value_for_email, $right);
                        $value_for_email = $left[0] . $right[0];
                    }

                    // Email
                    if ( ! $email && preg_match( "/^\S+@\S+\.\S+$/", $value_for_email ) ) {
                        $email = $value_for_email;

                        // Names
                    }elseif (preg_match("/name/i", $key)){

                        preg_match("/((name.?)?(your|first|for)(.?name)?)/", $key, $match_forename);
                        preg_match("/((name.?)?(last|family|second|sur)(.?name)?)/", $key, $match_surname);
                        preg_match("/(name.?)?(nick|user)(.?name)?/", $key, $match_nickname);

                        if(count($match_forename) > 1)
                            $nickname['first'] = $value;
                        elseif(count($match_surname) > 1)
                            $nickname['last'] = $value;
                        elseif(count($match_nickname) > 1)
                            $nickname['nick'] = $value;
                        else
                            $nickname[$prev_name.$key] = $value;

                        // Subject
                    }elseif ($subject === null && preg_match("/subject/i", $key)){
                        $subject = $value;

                        // Message
                    }else{
                        $message[$prev_name.$key] = $value;
                    }

                }elseif(!is_object($value)){

                    $prev_name_original = $prev_name;
                    $prev_name = ($prev_name === '' ? $key.'_' : $prev_name.$key.'_');

                    $temp = $this->get_fields_any($value, $message, $email, $nickname, $subject, $contact, $prev_name);

                    $message 	= $temp['message'];
                    $email 		= ($temp['email'] 		? $temp['email'] : null);
                    $nickname 	= ($temp['nickname'] 	? $temp['nickname'] : null);
                    $subject 	= ($temp['subject'] 	? $temp['subject'] : null);
                    if($contact === true)
                        $contact = ($temp['contact'] === false ? false : true);
                    $prev_name 	= $prev_name_original;
                }
            } unset($key, $value);
        }

        foreach ($skip_message_post as $v) {
            if (isset($_POST[$v])) {
                $message = null;
                break;
            }
        } unset($v);

        //If top iteration, returns compiled name field. Example: "Nickname Firtsname Lastname".
        if($prev_name === ''){
            if(!empty($nickname)){
                $nickname_str = '';
                foreach($nickname as $value){
                    $nickname_str .= ($value ? $value." " : "");
                }unset($value);
            }
            $nickname = $nickname_str;
        }

        $return_param = array(
            'email' 	=> $email,
            'nickname' 	=> $nickname,
            'subject' 	=> $subject,
            'contact' 	=> $contact,
            'message' 	=> $message
        );
        return $return_param;
    }

    protected function obfuscate_param($value = null)
    {
        if ($value && (!is_object($value) || !is_array($value))) {
            $length = strlen($value);
            $value = str_repeat('*', $length);
        }

        return $value;
    }

    /*
	 * Gets possible IPs
	 *
	 * Checks for HTTP headers HTTP_X_FORWARDED_FOR and HTTP_X_REAL_IP and filters it for IPv6 or IPv4
	 * returns array()
	 */
    protected  function get_possible_ips()
    {
        $result_ips = array(
            'X-Forwarded-For' => null,
            'X-Forwarded-For-Last' => null,
            'X-Real-Ip' => null,
        );

        $headers = $this->apache_request_headers();

        // X-Forwarded-For
        if(array_key_exists( 'X-Forwarded-For', $headers )){
            $ips = explode(",", trim($headers['X-Forwarded-For']));
            // First
            $ip = trim($ips[0]);
            $ip =        filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
            $ip = !$ip ? filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) : $ip;
            $result_ips['X-Forwarded-For'] = !$ip ? '' : $ip;
            // Last
            if(count($ips) > 1){
                $ip = trim($ips[count($ips)-1]);
                $ip =        filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
                $ip = !$ip ? filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) : $ip;
                $result_ips['X-Forwarded-For-Last'] = !$ip ? '' : $ip;
            }
        }

        // X-Real-Ip
        if(array_key_exists( 'X-Real-Ip', $headers )){
            $ip = trim($headers['X-Real-Ip']);
            $ip =        filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
            $ip = !$ip ? filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) : $ip;
            $result_ips['X-Real-Ip'] = !$ip ? '' : $ip;
        }
        return ($result_ips) ? $result_ips : null;
    }

    /**
     * Gets sender ip
     * Filters IPv4 or IPv6
     * @return null|int;
     */
    protected function get_ip()
    {
        $ip =        filter_var(trim($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
        $ip = !$ip ? filter_var(trim($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) : $ip;
        return $ip;
    }

    protected function apache_request_headers()
    {
        if ( function_exists('apache_request_headers') ) {
            return apache_request_headers();
        } else {
            $headers = array();
            foreach($_SERVER as $key => $val){
                if(preg_match('/\AHTTP_/', $key)){
                    $server_key = preg_replace('/\AHTTP_/', '', $key);
                    $key_parts = explode('_', $server_key);
                    if(count($key_parts) > 0 and strlen($server_key) > 2){
                        foreach($key_parts as $part_index => $part){
                            $key_parts[$part_index] = function_exists('mb_strtolower') ? mb_strtolower($part) : strtolower($part);
                            $key_parts[$part_index][0] = strtoupper($key_parts[$part_index][0]);
                        }
                        $server_key = implode('-', $key_parts);
                    }
                    $headers[$server_key] = $val;
                }
            }
            return $headers;
        }
    }

}