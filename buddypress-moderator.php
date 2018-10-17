<?php
/*
Plugin Name: Buddypress Moderator Plugin
Plugin URI:
Description: Allows forum posts to be flagged for moderation.  If moderator group doesn't exists it creates one
Version: 1.0.0
Author: Kirk Johnson
Author URI:
License: GPL3
License URI: http://www.gnu.org/licenses/gpl.html
 */
$includes = array(
    'includes/class-front-moderator.php',
    'includes/class-admin-moderator.php'
);

foreach ($includes as $file) {
    include_once $file;
}
