<?php

if( $modx->getOption('antispambycleantalk.plugin_enabled') && trim( $modx->getOption( 'antispambycleantalk.api_key' ) ) == '' ) {

    return;

} else {

    require_once MODX_CORE_PATH . 'components/antispambycleantalk/model/classCleantalkAntispamCoreModx.php';
    $cleantalk_antispam_core = new classCleantalkAntispamCoreModx( trim( $modx->getOption( 'antispambycleantalk.api_key' ) ) );

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

return;

