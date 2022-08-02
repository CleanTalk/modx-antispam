<?php

$s = array(
    'antispambycleantalk.api_key' => '',
    'antispambycleantalk.plugin_enabled' => true,
    'antispambycleantalk.exclusions_url' => '',
    'antispambycleantalk.check_forms_without_email' => false
);

$settings = array();

foreach ($s as $key => $value) {
    if (is_string($value) || is_int($value)) { $type = 'textfield'; }
    elseif (is_bool($value)) { $type = 'combo-boolean'; }
    else { $type = 'textfield'; }

    $parts = explode('.',$key);
    if (count($parts) == 1) { $area = 'Default'; }
    else { $area = $parts[0]; }
    
    $settings[$key] = $modx->newObject('modSystemSetting');
    $settings[$key]->set('key', $key);
    $settings[$key]->fromArray(array(
        'value' => $value,
        'xtype' => $type,
        'namespace' => PKG_NAME_LOWER,
        'area' => $area
    ));
}

return $settings;
