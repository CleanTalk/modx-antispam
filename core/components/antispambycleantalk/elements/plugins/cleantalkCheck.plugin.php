<?php

if( $modx->getOption('antispambycleantalk.plugin_enabled') && trim( $modx->getOption( 'antispambycleantalk.api_key' ) ) == '' ) {

    return;

} else {
    
    $path = MODX_CORE_PATH . 'components/antispambycleantalk/model/';
    $modx->getService('cleantalk_antispam_core', 'classCleantalkAntispamCoreModx', $path, [
        'api_key' => trim( $modx->getOption( 'antispambycleantalk.api_key' ) )
    ]);    
    $cleantalk_antispam_core = $modx->cleantalk_antispam_core;

}

switch ($modx->event->name) {

    case 'OnLoadWebDocument' :

        // Set cookies
        $cleantalk_antispam_core->set_cookies();

        // Fire the anti spam checking
        if( ! empty( $_POST ) ){
            $cleantalk_antispam_core->ccf_spam_test($_POST);
        }

        break;

    case 'OnMODXInit' :

        // Handle AJAX requests only
        if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' ) { return; }

        // Fire the anti spam checking
        if( ! empty( $_POST ) ){
            $cleantalk_antispam_core->ccf_spam_test($_POST);
        }

        break;

}

