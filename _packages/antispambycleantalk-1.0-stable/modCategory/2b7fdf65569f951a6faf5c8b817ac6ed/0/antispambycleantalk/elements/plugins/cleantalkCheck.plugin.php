<?php
$e = $modx->Event;
if ($e->name == 'OnLoadWebDocument')
{
	$plugin_enabled = $modx->getOption('antispambycleantalk.plugin_enabled');
	if ($plugin_enabled)
	{
		$ct_temp_msg_data = getFieldsAny($_POST);
		if (isset($ct_temp_msg_data['email']) && (isset($ct_temp_msg_data['nickname']) || isset($ct_temp_msg_data['message'])))
		{
			$api_key = $modx->getOption('antispambycleantalk.api_key');
			$sender_email    = ($ct_temp_msg_data['email']    ? $ct_temp_msg_data['email']    : '');
			$sender_nickname = ($ct_temp_msg_data['nickname'] ? $ct_temp_msg_data['nickname'] : '');
			$sender_message_post  = ($ct_temp_msg_data['message']  ? $ct_temp_msg_data['message']  : array());
			$sender_message == null;
			foreach ($sender_message_post as $key=>$value)
			{
				if ($key == 'message'   ||
					$key == 'contact'   ||
					$key == 'msg'       ||
					$key == 'contactus' ||
					$key == 'contact_us'
				)
					$sender_message = $value;
			}
			$check_type = null;
			if ($sender_message !== null)
			    $check_type = 'check_message';
			else $check_type = 'check_newuser';
			$path = $modx->getOption('antispambycleantalk.core_path', null, $modx->getOption('core_path') . 'components/antispambycleantalk/model/');
			if (is_readable($path . 'cleantalk.class.php') && is_readable($path . 'cleantalkhelper.class.php') && is_readable($path . 'cleantalkrequest.class.php') && is_readable($path . 'cleantalkresponse.class.php')) {
			    $cleantalk = $modx->getService('antispambycleantalk','Cleantalk', $path);
			    $cleantalk_request = $modx->getService('antispambycleantalk','CleantalkRequest', $path);
			    $cleantalk_response = $modx->getService('antispambycleantalk','CleantalkResponse',$path);
			    $cleantalk_helper = $modx->getService('antispambycleantalk','CleantalkHelper',$path);
			}
			
			/* Content check */
			$refferrer = null;
			if (isset($_SERVER['HTTP_REFERER'])) {
			    $refferrer = htmlspecialchars((string) $_SERVER['HTTP_REFERER']);
			}

			$user_agent = null;
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
			    $user_agent = htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']);
			}
			$sender_info = array(
			    'REFFERRER' => $refferrer,
			    'post_url' => $refferrer,
			    'USER_AGENT' => $user_agent
			);
			$js_on = 0;
			if (isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y"))
			        $js_on = 1;
			$cookie_timestamp = (isset($_COOKIE['ct_timestamp']) ? $_COOKIE['ct_timestamp'] : 0);
			$sender_info = json_encode($sender_info);   
			$cleantalk_request->auth_key = trim($api_key);
			$cleantalk_request->sender_email = $sender_email;
			$cleantalk_request->sender_nickname = $sender_nickname;
			$cleantalk_request->sender_ip = $cleantalk->cleantalk_get_real_ip();
			$cleantalk_request->agent = 'modx-10';
			$cleantalk_request->js_on = $js_on;
			$cleantalk_request->submit_time = time() - $cookie_timestamp;
			$cleantalk_request->sender_info = $sender_info;
			$cleantalk->work_url = 'http://moderate.cleantalk.org';
			$cleantalk->server_url = 'http://moderate.cleantalk.org';
			if ($check_type == 'check_message'){
			    $cleantalk_request->message = $sender_message;
			    $ct_result = $cleantalk->isAllowMessage($cleantalk_request);   
			}
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


/*
* Get data from submit recursively
*/

function getFieldsAny($arr, $message=array(), $email = null, $nickname = array('nick' => '', 'first' => '', 'last' => ''), $subject = null, $contact = true, $prev_name = ''){
	
	$obfuscate_params = array( //Fields to replace with ****
		'password',
		'pass',
		'pwd',
		'pswd'
	);
	
	$skip_fields_with_strings = array( //Array for strings in keys to skip and known service fields
		// Common
		'ct_checkjs', //Do not send ct_checkjs
		'nonce', //nonce for strings such as 'rsvp_nonce_name'
		'security',
		'action',
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
	);
	if(count($arr)){
		foreach($arr as $key => $value){
			
			if(gettype($value)=='string'){
				$decoded_json_value = json_decode($value, true);
				if($decoded_json_value !== null)
					$value = $decoded_json_value;
			}
			
			if(!is_array($value) && !is_object($value)){
				
				if($value === '')
					continue;
				
				//Skipping fields names with strings from (array)skip_fields_with_strings
				foreach($skip_fields_with_strings as $needle){
					if (strpos($prev_name.$key, $needle) !== false){
						continue(2);
					}
				}unset($needle);
				
				//Obfuscating params
				foreach($obfuscate_params as $needle){
					if (strpos($key, $needle) !== false){
						$value = obfuscate_param($value);
						continue(2);
					}
				}unset($needle);
				
				//Email
				if (!$email && preg_match("/^\S+@\S+\.\S+$/", $value)){
					$email = $value;
					
				//Names
				}elseif (preg_match("/name/i", $key)){
										
					if(preg_match("/first/i", $key) || preg_match("/fore/i", $key) || preg_match("/private/i", $key))
						$nickname['first'] = $value;
					elseif(preg_match("/last/i", $key) || preg_match("/sur/i", $key) || preg_match("/family/i", $key) || preg_match("/second/i", $key))
						$nickname['last'] = $value;
					elseif(!$nickname['nick'])
						$nickname['nick'] = $value;
					else
						$message[$prev_name.$key] = $value;
				
				//Subject
				}elseif ($subject === null && preg_match("/subj/i", $key)){
					$subject = $value;
				
				//Message
				}else{
					$message[$prev_name.$key] = $value;					
				}
				
			}else if(!is_object($value)&&@get_class($value)!='WP_User'){
				
				$prev_name_original = $prev_name;
				$prev_name = ($prev_name === '' ? $key.'_' : $prev_name.$key.'_');
				
				$temp = $this->getFieldsAny($value, $message, $email, $nickname, $subject, $contact, $prev_name);
				
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
/**
* Masks a value with asterisks (*) Needed by the getFieldsAny()
* @return string
*/
function obfuscate_param($value = null) {
	if ($value && (!is_object($value) || !is_array($value))) {
		$length = strlen($value);
		$value = str_repeat('*', $length);
	}

	return $value;
}