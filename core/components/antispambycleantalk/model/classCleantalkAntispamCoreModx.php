<?php

require_once MODX_CORE_PATH . 'components/antispambycleantalk/model/lib/classCleantalkAntispamCore.php';

class classCleantalkAntispamCoreModx extends classCleantalkAntispamCore
{

    public function __construct( $api_key )
    {
        global $modx;
        parent::__construct ($api_key );
        require_once MODX_CORE_PATH . 'components/antispambycleantalk/model/lib/autoloader.php';
        $modx->regClientScript(MODX_ASSETS_URL . 'components/antispambycleantalk/js/web/apbct_public.js');
    }

    public function ccf_spam_test( $post )
    {
        global $modx;
        
        $msg_data = $this->get_fields_any($post);

        // Data
        $sender_email    = isset($msg_data['email'])    ? $msg_data['email']    : '';
        $sender_nickname = isset($msg_data['nickname']) ? $msg_data['nickname'] : '';
        $subject         = isset($msg_data['subject'])  ? $msg_data['subject']  : '';
        $message         = isset($msg_data['message'])  ? $msg_data['message']  : array();

        // Flags
        $registration    = isset($msg_data['reg'])      ? $msg_data['reg']      : false;
        $skip            = isset($msg_data['skip'])     ? $msg_data['skip']     : false;

        // Skip check if
        if(
            $skip || // Skip flag set by get_fields_any()
            ( ! $sender_email ) || // No email detected and general post data test is disabled
            ( $this->post_exclusions_check( $post ) ) || // Has an exclusions in POST
            ( $this->url_exclusions_check() )
        ) {
            $skip = true;
        }

        // Do check if email is not set
        if( ! $skip ){

            $ct_request = new \Cleantalk\Antispam\CleantalkRequest();

            // Service pararams
            $ct_request->auth_key             = $this->get_api_key();
            $ct_request->agent                = 'modx-12';

            // Message params
            $ct_request->sender_email         = $sender_email;
            $ct_request->sender_nickname      = $sender_nickname;
            $ct_request->message              = json_encode($message);

            // IPs
            $possible_ips = $this->get_possible_ips();
            $ct_request->sender_ip            = $this->get_ip();

            if ($possible_ips) {
                $ct_request->x_forwarded_for      = $possible_ips['X-Forwarded-For'];
                $ct_request->x_forwarded_for_last = $possible_ips['X-Forwarded-For-Last'];
                $ct_request->x_real_ip            = $possible_ips['X-Real-Ip'];
            }

            // Misc params
            $ct_request->js_on                = isset($post['ct_checkjs']) && $post['ct_checkjs'] == date("Y") ? 1 : 0;
            $ct_request->submit_time          = ($this->cookies_test() == 1) ? time() - (int)$_COOKIE['apbct_timestamp'] : null;
            $ct_request->sender_info          = json_encode( $this->get_sender_info( $post ) );
            $ct_request->all_headers          = json_encode( $this->get_sender_info( $post ) );

            // Making a request
            $ct = new \Cleantalk\Antispam\Cleantalk();
            $ct->server_url = 'http://moderate.cleantalk.org';

            if( isset( $post['register-btn'] ) ) {
                $ct_result = $ct->isAllowUser($ct_request);
            } else {
                $ct_result = $ct->isAllowMessage($ct_request);
            }

            if(!empty($ct_result->errno) && !empty($ct_result->errstr)){

            }elseif($ct_result->allow == 1){

            }else{
                if( isset( $post['af_action'] ) ) {
                    // Ajax Form's format returning
                    $modx->placeholders['fi.error.' . key($post)] = $ct_result->comment;
                    return;
                } else {
                    $error_tpl = file_get_contents(MODX_CORE_PATH . 'components/antispambycleantalk/model/lib/die_page.html');
                    print str_replace(array('{BLOCK_REASON}','{GENERATED}'), array($ct_result->comment, "<p>The page was generated at&nbsp;".date("D, d M Y H:i:s")."</p>"), $error_tpl);
                    die();
                }
            }
        }

    }

    protected function post_exclusions_check( $post ) {

        $exclusions = array(

        );

        if( ! empty ( $exclusions ) ){
            foreach ( $exclusions as $name => $value ){
                if( isset( $post[ $name ] ) ){
                    if( ! empty( $value ) || ( $value && $post[ $name ] === $value ) ){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    protected function url_exclusions_check() {

        global $modx;

        if ( $modx->getOption('antispambycleantalk.exclusions_url') !== '' ) {

            $exclusions = explode( ',', $modx->getOption('antispambycleantalk.exclusions_url') );

            $haystack = @$_SERVER['REQUEST_URI'];
            foreach ( $exclusions as $exclusion ) {
                if ( stripos( $haystack, $exclusion ) !== false ){
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }

    }

    /**
     * Inner function - Default data array for senders
     * @return array
     */
    protected function get_sender_info( $post )
    {

        global $modx;

        return $sender_info = array(

            // Common
            'remote_addr'     => $_SERVER['REMOTE_ADDR'],
            'USER_AGENT'      => htmlspecialchars($_SERVER['HTTP_USER_AGENT']),
            'REFFERRER'       => htmlspecialchars($_SERVER['HTTP_REFERER']),
            'page_url'        => isset($_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']) : null,
            'php_session'     => session_id() != '' ? 1 : 0,
            'cookies_enabled' => $this->cookies_test(),
            'fields_number'   => sizeof($post),
            'ct_options'      => json_encode(array(
                'auth_key' => $this->get_api_key(),
                'url_exclusions' => $modx->getOption('antispambycleantalk.exclusions_url')
            )),

            // JS params
            'mouse_cursor_positions' => isset($_COOKIE['ct_pointer_data'])          ? json_decode(stripslashes($_COOKIE['ct_pointer_data']), true) : null,
            'js_timezone'            => isset($_COOKIE['ct_timezone'])              ? $_COOKIE['ct_timezone']             : null,
            'key_press_timestamp'    => isset($_COOKIE['ct_fkp_timestamp'])         ? $_COOKIE['ct_fkp_timestamp']        : null,
            'page_set_timestamp'     => isset($_COOKIE['ct_ps_timestamp'])          ? $_COOKIE['ct_ps_timestamp']         : null,
            'form_visible_inputs'    => !empty($_COOKIE['ct_visible_fields_count']) ? $_COOKIE['ct_visible_fields_count'] : null,
            'apbct_visible_fields'   => !empty($_COOKIE['ct_visible_fields'])       ? $_COOKIE['ct_visible_fields']       : null,

        );
    }

}