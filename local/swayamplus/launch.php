<?php
// File: local/swayamplus/launch.php
require(__DIR__ . '/../../config.php');

$iss = optional_param('iss', '', PARAM_URL);
$target = optional_param('target_link_uri', $CFG->wwwroot . '/my/', PARAM_URL);
// The enrollmentId comes in either param depending on the launch type
$enrollmentid = optional_param('swayamEnrollmentId', optional_param('lti_message_hint', '', PARAM_RAW), PARAM_RAW);

if ($iss !== 'https://swayamplus.education.gov.in/oidc') {
    throw new moodle_exception('invalidrequest', 'error');
}

// 1. Temporarily stash the enrollmentId in the session
if (!empty($enrollmentid)) {
    $SESSION->swayam_pending_enrollment = $enrollmentid;
}

// 2. Setup standard Moodle OIDC redirect
$wantsurl = new moodle_url($target);
if ($wantsurl->get_host() !== (new moodle_url($CFG->wwwroot))->get_host()) {
    $wantsurl = new moodle_url('/my/');
}

if (isloggedin() && !isguestuser()) {
    redirect($wantsurl);
}

// Ensure this matches the Issuer ID generated in Moodle Site Admin -> OAuth 2 Services
$issuerid = get_config('local_swayamplus', 'oauth_issuer_id'); 

redirect(new moodle_url('/auth/oauth2/login.php', [
    'id' => $issuerid,
    'wantsurl' => $wantsurl->out(false),
    'sesskey' => sesskey(),
]));
