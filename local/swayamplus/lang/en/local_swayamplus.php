<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_swayamplus', get_string('settingspage', 'local_swayamplus'));
    $ADMIN->add('localplugins', $settings);

    // Add a text box to enter the OAuth 2 Issuer ID.
    $settings->add(new admin_setting_configtext(
        'local_swayamplus/oauth_issuer_id',
        get_string('oauthissuerid', 'local_swayamplus'),
        get_string('oauthissuerid_desc', 'local_swayamplus'),
        '1', // Default value.
        PARAM_INT
    ));

    // (Optional) Add a text box for the Swayam API Base URL while you're at it.
    $settings->add(new admin_setting_configtext(
        'local_swayamplus/swayam_url',
        get_string('swayamurl', 'local_swayamplus'),
        get_string('swayamurl_desc', 'local_swayamplus'),
        '',
        PARAM_URL
    ));
}
