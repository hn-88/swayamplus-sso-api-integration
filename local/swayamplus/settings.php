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

    // Required by classes/api.php::get_access_token() for the client_credentials
    // grant against the Swayam Plus partner API.
    $settings->add(new admin_setting_configtext(
        'local_swayamplus/client_id',
        get_string('clientid', 'local_swayamplus'),
        get_string('clientid_desc', 'local_swayamplus'),
        '',
        PARAM_RAW_TRIMMED
    ));

    // Secret — use configpasswordunmask so it isn't shown in plain text on the
    // settings page, and is stored/encrypted the way Moodle handles other
    // client secrets (e.g. auth_oauth2 does the same for its clientsecret field).
    $settings->add(new admin_setting_configpasswordunmask(
        'local_swayamplus/client_secret',
        get_string('clientsecret', 'local_swayamplus'),
        get_string('clientsecret_desc', 'local_swayamplus'),
        ''
    ));
}
