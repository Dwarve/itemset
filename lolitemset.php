<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
* Plugin Name: League of Legends ItemSet Generator
* Description: A quick way generate ItemSets for champions based on most popular items purchased by top level players.
* Version: 1.0
* Author: Bruce "Dwarve" Lance
* License: GPL2
*/

class lolitemset {
        
    private static $instance;
        
    // Main function to setup the class
    public static function instance() {
                
        // Checks to see if a current instance of the class is found before creating a new one
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof lolitemset ) ) {
                        
            // Create new instance of class and run the functions to build it
            self::$instance = new lolitemset;
            self::$instance->init();
            self::$instance->includes();
        
            register_activation_hook( __FILE__, 'lolitemset_activation' );
            add_action( 'admin_init', 'lolitemset_redirect' );
        }

        // Returns either a fresh instance if created or the original if already existed
        return self::$instance;
    }
        
    // Initializes all of the globals
    public function init(){
        global $wpdb;

        /* Define Constants */
        // Items Table
        if ( ! defined( 'LOLITEMSET_ITEM' ) )
            define( 'LOLITEMSET_ITEM', $wpdb->prefix . "lolitemset_items" );
                
        // Champions Table
        if ( ! defined( 'LOLITEMSET_CHAMPION' ) )
            define( 'LOLITEMSET_CHAMPION', $wpdb->prefix . "lolitemset_champions" );

        // Item Purchases Table
        if ( ! defined( 'LOLITEMSET_ITEMPURCHASES' ) )
            define( 'LOLITEMSET_ITEMPURCHASES', $wpdb->prefix . "lolitemset_itempurchases" );

        // Matches Table
        if ( ! defined( 'LOLITEMSET_MATCHLIST' ) )
            define( 'LOLITEMSET_MATCHLIST', $wpdb->prefix . "lolitemset_matchlist" );

        // Players Table
        if ( ! defined( 'LOLITEMSET_PLAYERLIST' ) )
            define( 'LOLITEMSET_PLAYERLIST', $wpdb->prefix . "lolitemset_playerlist" );

        // API_Key
        if ( ! defined( 'LOLITEMSET_API_KEY' ) )
            define( 'LOLITEMSET_API_KEY', get_option( 'lolitemset_api_key' ) );
                                
        // Region
        if ( ! defined( 'LOLITEMSET_REGION' ) )
            define( 'LOLITEMSET_REGION', get_option( 'lolitemset_region' ) );
                                
        // Static Data Link
        if ( ! defined( 'LOLITEMSET_STATIC_DATA' ) )
            define( 'LOLITEMSET_STATIC_DATA', 'https://global.api.pvp.net/api/lol/static-data/' . LOLITEMSET_REGION . '/v1.2/' );

        // Item Static Data
        if ( ! defined( 'LOLITEMSET_ITEM_STATIC_DATA' ) )
            define( 'LOLITEMSET_ITEM_STATIC_DATA', 'http://ddragon.leagueoflegends.com/cdn/5.2.1/data/en_US/item.json' );
            
        // Version
        if ( ! defined( 'LOLITEMSET_VERSION' ) ) {
            // Checks to see if an API Key has been entered.
            if ( LOLITEMSET_API_KEY != ''){
                $this->getversiondata = wp_remote_get( LOLITEMSET_STATIC_DATA . 'versions?api_key=' . LOLITEMSET_API_KEY );

                // If it fails to connect then blank is set
                if ( is_wp_error( $this->getversiondata ) ) {
                    define( 'LOLITEMSET_VERSION', '' );
                } else {
                    $versionjson = json_decode( $this->getversiondata[ 'body' ], 'ASSOC_A' );

                    // Checks to see if the result is bad meaning a bad API Key
                    if ( isset( $versionjson[ 'status' ][ 'status_code' ] ) ) {
                        define( 'LOLITEMSET_VERSION', '' );
                    } else {
                        // If all is well then set the version allowing admin functions to work
                        define( 'LOLITEMSET_VERSION', $versionjson[0] );
                    }
                }
            } else {
                define( 'LOLITEMSET_VERSION', '' );
            }
        }
                
        // Plugin version
        if ( ! defined( 'LOLITEMSET_PLUGIN_VS' ) )
            define( 'LOLITEMSET_PLUGIN_VS', '1.0' );

        // Plugin Folder Path
        if ( ! defined( 'LOLITEMSET_PLUGIN_DIR' ) )
            define( 'LOLITEMSET_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

        // Plugin Folder URL
        if ( ! defined( 'LOLITEMSET_PLUGIN_URL' ) )
            define( 'LOLITEMSET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

        // Plugin Root File
        if ( ! defined( 'LOLITEMSET_PLUGIN_FILE' ) )
            define( 'LOLITEMSET_PLUGIN_FILE', __FILE__ );

        // Wordpress Upload Directory
        if ( ! defined( 'LOLITEMSET_UPLOAD_DIR' ) ) {
            $upload_dir = wp_upload_dir();
            define( 'LOLITEMSET_UPLOAD_DIR', $upload_dir[ 'basedir' ] );
        }

        // Wordpress Upload Directory URL
        if ( ! defined( 'LOLITEMSET_UPLOAD_URL' ) ) {
            $upload_dir = wp_upload_dir();
            define( 'LOLITEMSET_UPLOAD_URL', $upload_dir[ 'baseurl' ] );
        }
                
    }
        
    // Requires the additional files
    function includes() {
        require_once( LOLITEMSET_PLUGIN_DIR . 'shortcode/shortcode.php');
        require_once( LOLITEMSET_PLUGIN_DIR . 'admin/admin.php' );
        require_once( LOLITEMSET_PLUGIN_DIR . 'admin/activation.php' );
                
    }
        
}        

// Highlander function to force a single instance
function lolitemset() {
    return lolitemset::instance();
}

// Initialization call and allows the class to be assigned to a variable by Example: $lolitemset=lolitemset();
lolitemset();
