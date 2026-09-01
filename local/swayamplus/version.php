<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_swayamplus';
$plugin->version   = 2025090101;   // YYYYMMDDXX, incremented for pushqueue table + retry_queue task
$plugin->requires  = 2022041900;   // minimum Moodle version this plugin supports (adjust to your Moodle branch)
$plugin->maturity  = MATURITY_ALPHA; // switch to MATURITY_STABLE once it's production-ready
$plugin->release   = '0.1';
