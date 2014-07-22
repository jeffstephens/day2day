<?php
session_start();

// Require configuration file
require_once('config.php');

class PHPBase{
    // Global variables
    public static $mysqli;
    public $userInfo;
    public $pagename;
    public $execStartTime;

    function __construct(){
        global $execStartTime;
        $execStartTime = microtime( true );

        global $mysqli;
        $mysqli   = null;
        self::dbconnect();
    }

    /* Return the execution start timestamp */
    function getExecStartTime(){
        global $execStartTime;
        return $execStartTime;
    }

    /* Connect to the database and store the connection to a global static variable. */
    function dbconnect(){
        global $mysqli;

        if( is_null( $mysqli ) ){
            $mysqli = new mysqli( self::DB_HOST, self::DB_USER, self::DB_PASS );
            $mysqli->select_db( self::DB_NAME );

            // Did it work?
            if( mysqli_connect_errno() ){
                self::log_error( 'Database connection failed. ' . mysqli_connect_error() );
                return false;
            }
        }
        return true;
    }

    /* Sanitize input - remove all HTML and escape characters so it's MySQL-safe.
     *
     * @param $input    String The string to sanitize
     * @return          String The sanitized and optionally formatted input.
     */
    function sanitize( $input ){
        global $mysqli;

        if( is_null( $mysqli ) ){
            self::dbconnect();
        }

        $output = $mysqli->real_escape_string( $input );
        $output = trim( $output );
        $output = htmlentities( $output );

        return $output;
    }

    /* Remove any number of query arguments from a URI. Generally used for referrers when redirection
     * back with an error message to prevent these messages from stacking.
     *
     * @param $input     String                  The URI string with query variables
     * @param $blacklist String or Array(String) A pattern or array of arguments to clean from the URI.
     * @return           String                  The cleaned URI with query variables
     */
    function cleanQueryString( $input, $blacklist ){
        $referrerQuery = explode( '?', $input );
        $queryParts    = explode( '&', $referrerQuery[ ( sizeof( $referrerQuery ) - 1 ) ] );
        $referrer      = $referrerQuery[0];

        /* Loud debugging logs, only turn on if needed
        self::log_debug( 'input=' . $input );
        self::log_debug( 'referrerQuery=' . print_r( $referrerQuery, true ) );
        self::log_debug( 'queryParts=' . print_r( $queryParts, true ) );
        self::log_debug( 'referrer=' . $referrer );
        /**/

        for( $i = 1; $i < ( sizeof( $referrerQuery  ) - 1 ) ; $i++ ){
            $referrer .= $referrerQuery[$i];
        }

        // self::log_debug( 'after adding in non-query parts, referrer=' . $referrer );

        // change $blacklist into an array if necessary
        if( is_string( $blacklist ) ){
            $blacklist = array( $blacklist );
            // self::log_debug( 'string given for blacklist, creating 1-element array' );
        }
        elseif( !is_array( $blacklist ) ){
            // self::log_warning( 'cleanQueryString expects a second parameter of a string or array of strings.' );
            return $input;
        }

        $referrer .= '?1';

        if( $queryParts[0] . '?1' != $referrer ){
            foreach( $queryParts as $query ){
                $queryArgs = explode( '=', $query );
                if( !in_array( $queryArgs[0], $blacklist ) && $queryArgs[0] != '1' ){
                    $referrer .= '&' . $queryArgs[0] . '=' . $queryArgs[1];
                    self::log_debug( 're-adding query ' . $queryArgs[0] );
                }
                else{
                    self::log_debug( 'skipping query ' . $queryArgs[0] );
                }
            }
        }

        self::log_debug( 'final referrer: ' . $referrer );
        return $referrer;
    }

    /* Given an error code as defined config.php, return the corresponding message.
     */
    function getErrorMessage( $errorCode ){
        switch( $errorCode ){
            case self::PASSWORD_MISMATCH:
                return 'Your two passwords didn\'t match. Please try again.';
            case self::UNAUTHORIZED_REQUEST:
                return 'Unauthorized request. Please try again.';
            case self::LOGOUT_FAIL:
                return 'Logout failed. Please try again.';
            case self::MUST_BE_LOGGED_IN:
                return 'You must be logged in to view that page.';
            case self::DATABASE_ERROR:
                return 'A system error occurred. Please try again later.';
            case self::INVALID_IMAGE:
                return 'Invalid image. Try clicking on a link instead of guessing IDs!';
            case self::GET_COMMENTS_FAIL:
                return 'Comments are unavailable right now. Please try again later.';
            case self::ADD_COMMENT_FAIL:
                return 'Failed to add your comment. Please try again later.';
            case self::PASSWORD_ERROR:
                return 'Failed to set your password. Please try again later.';
            case self::UPLOAD_FAILED:
                return 'File upload failed. Please try again later.';
            case self::MISSING_DATA:
                return 'Please supply all data in the form.';
            case self::DUPLICATE_DETECTED:
                return 'Duplicate image detected. OC only, please.';
            default:
                self::log_warning( 'getErrorMessage was called with an invalid error code (' . $errorCode . ')' );
                return 'An unknown error occurred. This has been logged and will be looked into.';
        }
    }

    /* Given a UNIX timestamp, return a nice formatted human-readable date.
     * Relative dates are used for up to 2 days ago.
     */
    function formatDate( $timestamp ){
        $currentTime = time();
        $difference = $currentTime - $timestamp;

        if( $difference < 60 ){
            return 'less than a minute ago';
        }
        elseif( $difference < 120 ){
            return 'just a minute ago';
        }
        elseif( $difference < 300 ){
            return 'a few minutes ago';
        }
        elseif( $difference < 3600 ){
            return round( $difference / 60 ) . ' minutes ago';
        }
        elseif( $difference < 5400 ){
            return 'an hour ago';
        }
        elseif( $difference < 7200 ){
            return 'an hour and a half ago';
        }
        elseif( $difference < 86400 ){
            return round( $difference / 60 / 60 ) . ' hours ago';
        }
        elseif( date( 'n/j/Y', $timestamp ) == date( 'n/j/Y', ( $currentTime - 86400 ) ) ){
            return 'yesterday at ' . date ( 'g:i a', $timestamp );
        }
        elseif( date( 'n/j/Y', $timestamp ) == date( 'n/j/Y', ( $currentTime - 172800 ) ) ){
            return 'two days ago at ' . date ( 'g:i a', $timestamp );
        }
        else{
            return date( 'g:i a n/j/Y', $timestamp );
        }
    }

    /* Log an error message.
     *
     * @param $msg String The error message to log.
     * @return void
     */
    function log_error( $msg ){
        self::system_log( $msg, 'ERROR' );
    }

    /* Log a warning message. Not as critical as an error but something that should be attended to.
     *
     * @param $msg String The error message to log.
     * @return void
     */
    function log_warning( $msg ){
        self::system_log( $msg, 'WARNING' );
    }

    /* Log an information message.
     *
     * @param $msg String The info message to log.
     */
    function log_info( $msg ){
        self::system_log( $msg, 'INFO' );
    }

    /* Log a debug message.
     *
     * @param $msg String The info message to log.
     */
    function log_debug( $msg ){
        if( self::DEBUG ){
            self::system_log( $msg, 'DEBUG' );
        }
    }

    /* Write a log message with severity.
     * All log entries will automatically include date/time and a bunch of the user's
     * information.
     *
     * @param $msg The message to log
     * @param $level The severity level of the message. Defaults to info.
     * @return void
     */
    function system_log( $msg, $level = 'INFO' ){
        if( isset( $_SERVER['REMOTE_ADDR'] ) ){
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        else{
            $ip = '[No IP address available]';
        }

        if( isset( $_SERVER['PHP_SELF'] ) ){
            $script = $_SERVER['PHP_SELF'];
        }
        else{
            $script = 'unavailable';
        }

        if( isset( $_SERVER['QUERY_STRING'] ) ){
            $query = $_SERVER['QUERY_STRING'];
        }
        else{
            $query = 'unavailable';
        }

        $datetime = date('g:i a n/j/y');

        $log_msg  = "\n\n=========================================\n"
                  . $level . " at " . $datetime . "\n"
                  . "Script: " . $script . "\n"
                  . "Query: " . $query . "\n"
                  . "Message: " . $msg;

        file_put_contents( self::SYSTEM_FILE_PATH . "/error.log", $log_msg, FILE_APPEND );

        // Notify Jeff by email
        if( $level == 'ERROR' ){
            self::sendMail( 'jefftheman45@gmail.com', self::SITE_NAME . ' Error Report', $log_msg );
        }
    }



    /* Encrypt a password. If not given a salt, generate one and return a two-element
     * array. Otherwise, just use the salt and return just the hashed password.
     *
     * @param $password String The password you'd like to hash.
     * @param $salt     String The optional salt to hash it with.
     * @return Just a password if a salt is provided, else Array [hashed password,salt]
     */
    function encrypt( $password, $salt = "" ){
        if( !strlen( $salt ) ){
            // First generate a salt
            $salt = self::getSalt();
            $madeASalt = true;
        }
        else{
            $madeASalt = false;
        }

        // Blowfish-hash the password
        $hashed = crypt( $password, $salt );

        // Return different data depending on whether a salt was provided
        if( $madeASalt ){
            return Array( $hashed, $salt );
        }
        else{
            return $hashed;
        }
    }



    /* Generate a random string */
    function randString($length, $strength=8) {
        $vowels = 'aeuy';
        $consonants = 'bdghjmnpqrstvz';
        if ($strength >= 1) {
            $consonants .= 'BDGHJLMNPQRSTVWXZ';
        }
        if ($strength >= 2) {
            $vowels .= "AEUY";
        }
        if ($strength >= 4) {
            $consonants .= '23456789';
        }
        if ($strength >= 8) {
            $consonants .= '@#$%';
        }

        $password = '';
        $alt = time() % 2;
        for ($i = 0; $i < $length; $i++) {
            if ($alt == 1) {
                $password .= $consonants[(rand() % strlen($consonants))];
                $alt = 0;
            } else {
                $password .= $vowels[(rand() % strlen($vowels))];
                $alt = 1;
            }
        }
        return $password;
    }



    /* Generate a shiny new salt for something say, hashing a password.
     * Pay no attention to the code inside this function.
     */
    function getSalt(){
        $inside = time() . "az51" . time();
        $salt = '$2a$07$' . $inside . '$';
        return $salt;
    }



    /* Send email to a person using the Swiftmailer library
     *
     * @param $to      int    The user ID of the recipient
     * @param $subject String The subject of the email
     * @param $body    String The content of the email
     */
    function sendMail( $to, $subject, $body ){
        // Look up user information
        global $mysqli;

        if( is_null( $mysqli ) ){
            self::dbconnect();
        }

        if( is_numeric( $to ) ){
            $query = 'SELECT `email` FROM `users` WHERE `id` = "'. self::sanitize( $to ) .'";';
            $result = $mysqli->query( $query );
            $userData = $result->fetch_row();
            $toEmail = $userData[0];
            self::log_debug( 'looked up email for "' . $to . '" = "' . $toEmail . '"' );
        }
        else{
            $toEmail = $to;
        }

        // Send mail using Swiftmailer with Gmail
        $transporter = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
            ->setUsername( self::GMAIL_USER )
            ->setPassword( self::GMAIL_PASS );

        $mailer = Swift_Mailer::newInstance( $transporter );

        $message = Swift_Message::newInstance( $transporter )
            ->setSubject( $subject )
            ->setFrom( Array( self::GMAIL_USER => self::SITE_NAME . ' Team' ) )
            ->setTo( Array( $toEmail ) )
            ->setBody( $body )
        ;

        $result = $mailer->send( $message );

        if( !$result ){
            self::log_error( 'Mail failed to send!' );
            return false;
        }

        return true;
    }



    /* Update a user's auth token in the database and return this new auth token.
     */
    function updateAuthToken( $userID = null, $updateActivationToken = false ){
        global $mysqli;

        self::log_debug( 'updating authToken' );

        if( is_null( $mysqli ) ){
            self::dbconnect();
        }

        if( is_null( $userID ) ){
            if( isset( $_SESSION['userid'] ) ){
                $userID = $_SESSION['userid'];
            }
            else{
                self::log_debug( "Not generating new auth token; user is not logged in" );
                return "0";
            }
        }

        $newToken = self::sanitize( self::genAuthToken() );
        $userID = self::sanitize( $userID );

        if( $updateActivationToken ){
            $sql = 'UPDATE `users` SET `auth` = "'. $newToken .'", `activationToken` = "' . $newToken . '" WHERE `id` = '. $userID .';';
            self::log_debug( 'Setting activation token for user #' . $userID );
        }
        else{
            $sql = 'UPDATE `users` SET `auth` = "'. $newToken .'" WHERE `id` = '. $userID .';';
        }

        if( $result = $mysqli->query( $sql ) ){
            return $newToken;
        }
        else{
            self::log_error( "Failed to generate a new auth token.\n" . $sql . "\n\n". $mysqli->error );
        }
    }

    /* Check a given Auth Token against the user's auth token. This also consumes their auth token.
     */
    function validateAuthToken( $authToken, $userID = null ){
        global $mysqli;

        if( is_null( $userID ) ){
            if( isset( $_SESSION['userid'] ) ){
                $userID = $_SESSION['userid'];
            }
            else{
                // not logged in and no ID specified, abort
                return false;
            }
        }

        $authToken = self::sanitize( $authToken );
        $userID    = self::sanitize( $userID );
        $sql = 'SELECT `auth` FROM `users` WHERE `id` = '. $userID .';';

        if( is_null( $mysqli ) ){
            self::dbconnect();
        }

        if( !strlen( $authToken ) ){
            return false;
        }

        if( $result = $mysqli->query( $sql ) ){
            $data = $result->fetch_assoc();
            if( $data['auth'] == $authToken ){
                self::updateAuthToken( $userID );
                return true;
            }
            else{
                self::updateAuthToken( $userID );
                self::log_info( "Auth token invalid. " . $authToken ." vs. " . $data['auth'] );
                return false;
            }
        }
        else{
            self::log_error( "Failed to validate auth token.\n" . $sql . "\n\n". $mysqli->error );
        }
        return false;
    }

    /* Generate a psuedorandom string to be used as an auth token.
     */
    function genAuthToken( $string = self::SITE_NAME ){
        $timestamp = time();
        $username = $_SESSION['userid'];
        $random = rand(0, 50);

        return md5( $string . $timestamp . $username . $random );
    }

    /* Debug log the json, then output it and die. */
    function returnJson( $json ){
        self::log_debug( 'Returning JSON payload: ' . $json );
        die( $json );
    }

    /* Output $n tabs. Used to make HTML generated by PHP not look god awful.
     */
    function tab( $n ){
        $return = '';
        for( $i = 0; $i < $n; $i++ ){
            $return .= '    ';
        }
        return $return;
    }
}
