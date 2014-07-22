<?php
/* Configuration File
 * Contains all constants for the image gallery site.
 * Included in the PHPBase class.
 */

// Database connection constants
const DB_HOST           = 'localhost';
const DB_USER           = 'root';
const DB_PASS           = '';
const DB_NAME           = '';
const USER_TABLE        = 'users';

// Other constants
const SYSTEM_FILE_PATH  = '/home/jeffstephens/cse330/Module5';
const FILE_UPLOAD_DIR   = '/home/jeffstephens/cse330/htdocs/module5/uploads';
const SITE_NAME         = 'day2day';
const DEBUG             = false;

// Corresponding return codes (no magic numbers!)
const LOGIN_SUCCESS        =  0;
const LOGIN_FAIL_AUTH      =  1;
const LOGIN_FAIL_SYSTEM    =  2;
const SIGNUP_SUCCESS       =  3;
const SIGNUP_FAIL          =  4; // only type of signup failure is system
const EMAIL_TAKEN          =  5;
const PASSWORD_MISMATCH    =  6;
const UNAUTHORIZED_REQUEST =  7;
const LOGOUT_SUCCESS       =  8;
const LOGOUT_FAIL          =  9;
const MISSING_DATA         = 10;
const MUST_BE_LOGGED_IN    = 11;
const DATABASE_ERROR       = 12;
const INVALID_IMAGE        = 13;
const GET_COMMENTS_FAIL    = 14;
const ADD_COMMENT_FAIL     = 15;
const USER_REMOVAL_SUCCESS = 16;
const USER_REMOVAL_FAIL    = 17;
const PASSWORD_ERROR       = 18;
const UPLOAD_FAILED        = 19;
const DUPLICATE_DETECTED   = 20;
const POINT_ALLOC_SUCCESS  = 21;
const POINT_ALLOC_FAIL     = 22;
const BOOST_SUCCESS        = 23;
const BOOST_FAIL           = 24;
const ENTITLE_SUCCESS      = 25;
const ENTITLE_FAIL         = 26;

// Corresponding to the number of seconds in a month (for removing inactive users)
const INACTIVITY_LENGTH    = 2629740;

// Corresponding to the number of seconds in a week (for points allocation)
const POINTS_ALLOC_TIME    = 60;

// Corresponding to the number of points granted for uploading a picture
const UPLOAD_POINTS        = 5;

// Corresponding to the number of points granted for posting a comment
const COMMENT_POINTS       = 1;
