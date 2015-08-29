<?php if ( ! defined( 'ABSPATH' ) ) exit;

// Activation function
function lolitemset_activation(){
    global $wpdb;
    
    // Check for the api-key option to determine if settings need to be added
    $current_settings = get_option( 'lolitemset_api_key', false );

         if ( ! $current_settings ) {
            update_option( 'lolitemset_api_key', '' );
            update_option( 'lolitemset_region', 'na' );
         }
    
    // Needed for dbdelta
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    // Create the static information tables
    $sql = "CREATE TABLE IF NOT EXISTS `" . LOLITEMSET_CHAMPION . "` (
            `autoinc` int(11) NOT NULL AUTO_INCREMENT,
            `id` int(11) NOT NULL,
            `lolkey` varchar(50) NOT NULL,
            `name` varchar(50) NOT NULL,
            `title` text NOT NULL,
            `simplified_name` varchar(50) NOT NULL,
            `total_games` int(11) NOT NULL,
            `itemlist` text NOT NULL,
            PRIMARY KEY (`autoinc`),
            UNIQUE KEY `id` (`id`)
            ) DEFAULT CHARSET=utf8;";
    dbdelta( $sql );
    
    $sql = "CREATE TABLE IF NOT EXISTS `" . LOLITEMSET_ITEM . "` (
            `autoinc` int(11) NOT NULL AUTO_INCREMENT,
            `id` int(11) NOT NULL,
            `plaintext` text,
            `description` text,
            `name` varchar(50) NOT NULL,
            `lolgroup` varchar(50) DEFAULT NULL,
            `simplified_name` varchar(50) NOT NULL,
            `altid` int(11) NOT NULL,
            PRIMARY KEY (`autoinc`)
            ) DEFAULT CHARSET=utf8;";
    dbdelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `" . LOLITEMSET_ITEMPURCHASES . "` (
            `autoinc` int(11) NOT NULL AUTO_INCREMENT,
            `item_id` int(11) NOT NULL,
            `champion_id` int(11) NOT NULL,
            `total_purchases` int(11) NOT NULL,
            PRIMARY KEY (`autoinc`)
            ) DEFAULT CHARSET=utf8;";
    dbdelta( $sql );
    
    $sql = "CREATE TABLE IF NOT EXISTS `" . LOLITEMSET_MATCHLIST . "` (
            `autoinc` int(11) NOT NULL AUTO_INCREMENT,
            `timestamp` bigint(20) NOT NULL,
            `matchId` int(11) NOT NULL,
            `processed` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`autoinc`)
            ) DEFAULT CHARSET=utf8;";
    dbdelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `" . LOLITEMSET_PLAYERLIST . "` (
            `autoinc` int(11) NOT NULL AUTO_INCREMENT,
            `playerid` int(11) NOT NULL,
            `tier` varchar(15) NOT NULL,
            `processed` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`autoinc`)
            ) DEFAULT CHARSET=utf8;";
    dbdelta( $sql );

    // Triggers a redirect on activation
    add_option( 'lolitemset_do_activation_redirect', true );
}

// Redirect function
function lolitemset_redirect(){
    // On activation, redirect user to settings page with a message for the next steps
    if ( get_option( 'lolitemset_do_activation_redirect', false ) ) {
        delete_option( 'lolitemset_do_activation_redirect' );
        wp_redirect( admin_url( 'admin.php?page=lolitemset&lolitemsetmsg=RW50ZXIgQVBJIGtleSBhbmQgY2xpY2sgU2F2ZSBTZXR0aW5ncy4gVGhlbiBjbGljayBVcGRhdGUgTG9MIFN0YXRpYyBEYXRhIGFib3ZlLiBUaGVuIGVhY2ggb2YgdGhlIHVwZGF0ZSBpY29uIGJ1dHRvbnMgYmVsb3cgdG8gcmV0cmlldmUgaWNvbnMu' ) );
    }
}
