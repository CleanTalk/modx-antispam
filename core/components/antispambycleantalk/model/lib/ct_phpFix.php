<?php

/* 
 * Patch for filter_var()
 */
if(!function_exists('filter_var')){
	
	define('FILTER_VALIDATE_IP', 'ip');
	define('FILTER_FLAG_IPV4', 'ipv4');
	define('FILTER_FLAG_IPV6', 'ipv6');
	define('FILTER_VALIDATE_EMAIL', 'email');
	define('FILTER_FLAG_EMAIL_UNICODE', 'unicode');
	
	function filter_var($variable, $filter, $option = false){
		if($filter == 'ip'){
			if($option == 'ipv4'){
				if(preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $variable, $matches)){
					$variable = $matches[1];
					return $variable;
				}
			}
			if($option == 'ipv6'){
				if(preg_match("/\s*(([:.]{0,7}[0-9a-fA-F]{0,4}){1,8})\s*/", $variable, $matches)){
					$variable = $matches[1];
					return $variable;
				}
			}
		}
		if($filter == 'email'){
			if($option == 'unicode' || $option == false){
				if(preg_match("/\s*(\S*@\S*\.\S*)\s*/", $variable, $matches)){
					$variable = $matches[1];
					return $variable;
				}
			}
		}
	}
}

/*
 * Patch for apache_request_headers()
 */
if( ! function_exists('apache_request_headers') ) {

    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return( $arh );
    }

}
