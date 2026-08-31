<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_swayamplus', 'Swayam Plus Settings');
    $ADMIN->add('localplugins', $settings);

    // Add a text box to enter the OAuth 2 Issuer ID
    $settings->add(new admin_setting_configtext(
        'local_swayamplus/oauth_issuer_id',
        'OAuth 2 Issuer ID',
        'Enter the ID number of the Swayam Mock / Production service created in OAuth 2 Services.',
        '1', // Default value
        PARAM_INT
    ));

    // (Optional) Add a text box for the Swayam API Base URL while you're at it
    $settings->add(new admin_setting_configtext(
        'local_swayamplus/swayam_url',
        'Swayam API Base URL',
        'e.g. https://a1b2-c3d4.ngrok-free.app',
        '',
        PARAM_URL
    ));
}
