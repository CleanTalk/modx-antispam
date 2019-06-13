<?php

// Cookie names to validate
  $cookie_test_value = array(
      'cookies_names' => array(),
      'check_value' => trim($modx->getOption('antispambycleantalk.api_key')),
  );  

  // Submit time
  $ct_timestamp = time();
  setcookie('ct_timestamp', $ct_timestamp, 0, '/');
  $cookie_test_value['cookies_names'][] = 'ct_timestamp';
  $cookie_test_value['check_value'] .= $ct_timestamp;

  //Previous referer
  if(!empty($_SERVER['HTTP_REFERER'])){
      setcookie('ct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
      $cookie_test_value['cookies_names'][] = 'ct_prev_referer';
      $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
  }

   // Cookies test
  $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
  setcookie('ct_cookies_test', json_encode($cookie_test_value), 0, '/');


$plugin_enabled = $modx->getOption('antispambycleantalk.plugin_enabled');
$api_key = trim($modx->getOption('antispambycleantalk.api_key'));

if ($plugin_enabled && $api_key != '')
{
  $path = $modx->getOption('antispambycleantalk.core_path', null, $modx->getOption('core_path') . 'components/antispambycleantalk/model/');
  if (is_readable($path . 'cleantalk.class.php')) 
  {
    $modx->loadClass('Cleantalk', $path, true, true);
    $modx->loadClass('CleantalkRequest', $path, true, true);
    $modx->loadClass('CleantalkResponse', $path, true, true);
    $modx->loadClass('CleantalkHelper', $path, true, true);

    $ct_temp_msg_data = _cleantalk_get_fields_any($_POST);  
        
    $sender_email    = ($ct_temp_msg_data['email']    ? $ct_temp_msg_data['email']    : '');
    $sender_nickname = ($ct_temp_msg_data['nickname'] ? $ct_temp_msg_data['nickname'] : '');
    $sender_message_post  = ($ct_temp_msg_data['message']  ? $ct_temp_msg_data['message']  : '');
    if (is_array($sender_message_post))
      $sender_message_post = implode("\n", $sender_message_post);
    if ($sender_email != '' || $sender_message_post != '')
    {
      /* Content check */

      $page_set_timestamp = (isset($_COOKIE['ct_ps_timestamp']) ? $_COOKIE['ct_ps_timestamp'] : 0);
      $js_timezone = (isset($_COOKIE['ct_timezone']) ? $_COOKIE['ct_timezone'] : '');
      $first_key_timestamp = (isset($_COOKIE['ct_fkp_timestamp']) ? $_COOKIE['ct_fkp_timestamp'] : '');
      $pointer_data = (isset($_COOKIE['ct_pointer_data']) ? json_decode($_COOKIE['ct_pointer_data']) : '');
      $ct_cookies_test = null;
        if(isset($_COOKIE['ct_cookies_test'])){
            
            $cookie_test = json_decode(stripslashes($_COOKIE['ct_cookies_test']), true);
            
            $check_srting = $api_key;
            foreach($cookie_test['cookies_names'] as $cookie_name){
                $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
            } unset($cokie_name);
            
            if($cookie_test['check_value'] == md5($check_srting)){
                $ct_cookies_test = 1;
            }else{
                $ct_cookies_test = 0;
            }
        }else{
            $ct_cookies_test = null;
        }   
        $cleantalk_request = new CleantalkRequest();
      $cleantalk_request->auth_key = $api_key;
      $cleantalk_request->sender_email = $sender_email;
      $cleantalk_request->sender_nickname = $sender_nickname;
      $cleantalk_request->sender_ip = CleantalkHelper::ip_get(array('real'), false);
      $cleantalk_request->x_forwarded_for = CleantalkHelper::ip_get(array('x_forwarded_for'), false);
      $cleantalk_request->x_real_ip       = CleantalkHelper::ip_get(array('x_real_ip'), false);
      $cleantalk_request->agent = 'modx-11';
      $cleantalk_request->js_on = isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y") ? 1 : 0;
      $cleantalk_request->submit_time = ($ct_cookies_test == 1) ? time() - (int)$_COOKIE['ct_timestamp'] : null;
      if ($sender_message_post != '')
        $cleantalk_request->message = $sender_message_post;

        $cleantalk_request->sender_info = json_encode(array(
            'page_url' => htmlspecialchars(@$_SERVER['SERVER_NAME'].@$_SERVER['REQUEST_URI']),
            'REFFERRER' => $_SERVER['HTTP_REFERER'],
            'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],   
            'js_timezone' => $js_timezone,
            'mouse_cursor_positions' => $pointer_data,
            'key_press_timestamp' => $first_key_timestamp,
            'page_set_timestamp' => $page_set_timestamp, 
            'fields_number' => sizeof($_POST),  
        'REFFERRER_PREVIOUS' => isset($_COOKIE['ct_prev_referer']) ? $_COOKIE['ct_prev_referer'] : null,
        'cookies_enabled' => $ct_cookies_test,                 
        ));
        $cleantalk_request->post_info = json_encode(array('post_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 'comment_type' => 'feedback_general_contact_form'));
      $cleantalk = new Cleantalk();
      $cleantalk->work_url = 'http://moderate.cleantalk.org';
      $cleantalk->server_url = 'http://moderate.cleantalk.org';
      if ($sender_message_post != '')
        $ct_result = $cleantalk->isAllowMessage($cleantalk_request); 
      else $ct_result = $cleantalk->isAllowUser($cleantalk_request);

      if($ct_result->errno == 0 && $ct_result->allow == 0)
      {
          $error_tpl=file_get_contents($path."/error.html");
          print str_replace('%ERROR_TEXT%',$ct_result->comment,$error_tpl);         
          die();
      }       
    }     

  
  }


}

if (!function_exists("_cleantalk_get_fields_any"))
{
function _cleantalk_get_fields_any($arr, $message=array(), $email = null, $nickname = array('nick' => '', 'first' => '', 'last' => ''), $subject = null, $contact = true, $prev_name = '')
  {
    //Skip request if fields exists
    $skip_params = array(
        'ipn_track_id',   // PayPal IPN #
        'txn_type',     // PayPal transaction type
        'payment_status',   // PayPal payment status
        'ccbill_ipn',     // CCBill IPN 
      'ct_checkjs',     // skip ct_checkjs field
      'api_mode',         // DigiStore-API
      'loadLastCommentId', // Plugin: WP Discuz. ticket_id=5571
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
      'avatar__file_image_data',
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
        
        if(gettype($value)=='string'){
          $decoded_json_value = json_decode($value, true);
          if($decoded_json_value !== null)
            $value = $decoded_json_value;
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
              $value = _cleantalk_obfuscate_param($value);
              continue(2);
            }
          }unset($needle);
          

          // Decodes URL-encoded data to string.
          $value = urldecode($value); 

          // Email
          if (!$email && preg_match("/^\S+@\S+\.\S+$/", $value)){
            $email = $value;
            
          // Names
          }elseif (preg_match("/name/i", $key)){
            
            preg_match("/((name.?)?(your|first|for)(.?name)?)$/", $key, $match_forename);
            preg_match("/((name.?)?(last|family|second|sur)(.?name)?)$/", $key, $match_surname);
            preg_match("/^(name.?)?(nick|user)(.?name)?$/", $key, $match_nickname);
            
            if(count($match_forename) > 1)
              $nickname['first'] = $value;
            elseif(count($match_surname) > 1)
              $nickname['last'] = $value;
            elseif(count($match_nickname) > 1)
              $nickname['nick'] = $value;
            else
              $message[$prev_name.$key] = $value;
          
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
          
          $temp =_cleantalk_get_fields_any($value, $message, $email, $nickname, $subject, $contact, $prev_name);
          
          $message  = $temp['message'];
          $email    = ($temp['email']     ? $temp['email'] : null);
          $nickname   = ($temp['nickname']  ? $temp['nickname'] : null);        
          $subject  = ($temp['subject']   ? $temp['subject'] : null);
          if($contact === true)
            $contact = ($temp['contact'] === false ? false : true);
          $prev_name  = $prev_name_original;
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
      'email'   => $email,
      'nickname'  => $nickname,
      'subject'   => $subject,
      'contact'   => $contact,
      'message'   => $message
    );  
    return $return_param;

}
}

if (!function_exists("_cleantalk_obfuscate_param"))
{
function _cleantalk_obfuscate_param($value = null) 
{
  if ($value && (!is_object($value) || !is_array($value))) {
    $length = strlen($value);
    $value = str_repeat('*', $length);
  }

  return $value;
}  
}
