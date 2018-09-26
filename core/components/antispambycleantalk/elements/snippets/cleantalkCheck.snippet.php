<?php

	$plugin_enabled = $modx->getOption('antispambycleantalk.plugin_enabled');
	if ($plugin_enabled)
	{
		$ct_temp_msg_data = getFieldsAny($_POST);
		$api_key = trim($modx->getOption('antispambycleantalk.api_key'));

		if ($api_key !== '' && (isset($ct_temp_msg_data['email']) || isset($ct_temp_msg_data['message'])))
		{			
			$sender_email    = ($ct_temp_msg_data['email']    ? $ct_temp_msg_data['email']    : '');
			$sender_nickname = ($ct_temp_msg_data['nickname'] ? $ct_temp_msg_data['nickname'] : '');
			$sender_message_post  = ($ct_temp_msg_data['message']  ? $ct_temp_msg_data['message']  : '');
			if (is_array($sender_message_post))
				$sender_message_post = implode("\n", $sender_message_post);

			$path = $modx->getOption('antispambycleantalk.core_path', null, $modx->getOption('core_path') . 'components/antispambycleantalk/model/');
			if (is_readable($path . 'cleantalk.class.php') && is_readable($path . 'cleantalkhelper.class.php') && is_readable($path . 'cleantalkrequest.class.php') && is_readable($path . 'cleantalkresponse.class.php')) {
			    $cleantalk = $modx->getService('antispambycleantalk','Cleantalk', $path);
			    $cleantalk_request = $modx->getService('antispambycleantalk','CleantalkRequest', $path);
			    $cleantalk_response = $modx->getService('antispambycleantalk','CleantalkResponse',$path);
			    $cleantalk_helper = $modx->getService('antispambycleantalk','CleantalkHelper',$path);
			}
			
			/* Content check */

			$page_set_timestamp = (isset($_COOKIE['ct_ps_timestamp']) ? $_COOKIE['ct_ps_timestamp'] : 0);
			$js_timezone = (isset($_COOKIE['ct_timezone']) ? $_COOKIE['ct_timezone'] : '');
			$first_key_timestamp = (isset($_COOKIE['ct_fkp_timestamp']) ? $_COOKIE['ct_fkp_timestamp'] : '');
			$pointer_data = (isset($_COOKIE['ct_pointer_data']) ? json_decode($_COOKIE['ct_pointer_data']) : '');
  
			$cleantalk_request->auth_key = $api_key;
			$cleantalk_request->sender_email = $sender_email;
			$cleantalk_request->sender_nickname = $sender_nickname;
		    $ct_request->sender_ip = CleantalkHelper::ip_get(array('real'), false);
		    $ct_request->x_forwarded_for = CleantalkHelper::ip_get(array('x_forwarded_for'), false);
		    $ct_request->x_real_ip       = CleantalkHelper::ip_get(array('x_real_ip'), false);
			$cleantalk_request->agent = 'modx-11';
			$cleantalk_request->js_on = isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y") ? 1 : 0;
			$cleantalk_request->submit_time = time() - intval($page_set_timestamp);
			$cleantalk_request->message = $sender_message_post;
		    $ct_request->sender_info = json_encode(array(
		        'page_url' => htmlspecialchars(@$_SERVER['SERVER_NAME'].@$_SERVER['REQUEST_URI']),
		        'REFFERRER' => $_SERVER['HTTP_REFERER'],
		        'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],   
		        'js_timezone' => $js_timezone,
		        'mouse_cursor_positions' => $pointer_data,
		        'key_press_timestamp' => $first_key_timestamp,
		        'page_set_timestamp' => $page_set_timestamp,          
		    ));

			$cleantalk->work_url = 'http://moderate.cleantalk.org';
			$cleantalk->server_url = 'http://moderate.cleantalk.org';

			$ct_result = $cleantalk->isAllowMessage($cleantalk_request);   
			if($ct_result->errno == 0 && $ct_result->allow == 0)
			{
			  	$error_tpl=file_get_contents($path."/error.html");
				print str_replace('%ERROR_TEXT%',$ct_result->comment,$error_tpl);			   	
				die();
			}
		}
	}