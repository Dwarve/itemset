<?php if ( ! defined( 'ABSPATH' ) ) exit;

function lolitemset_add_menu() {
    // Place an admin menu
    $page = add_menu_page( "LoL ItemSet" , "LoL ItemSet", "manage_options", "lolitemset", "lolitemset_admin", "", "42.314" );
}

add_action( 'admin_menu', 'lolitemset_add_menu' );

function lolitemset_admin() {
    global $wpdb;
    
    // Settings update function
    if ( isset( $_POST['lolitemsetupdate'] ) && $_POST[ 'lolitemsetupdate' ] == 'update' )
        $updatemsg = lolitemset_save_settings();
    
    // Check to see if an update was ran
    if ( isset( $_GET[ 'lolitemsetdata' ] ) ) {

        // Update the static databases
        if ( $_GET[ 'lolitemsetdata' ] == 'runupdate' ) {
            
            $updatemsg = lolitemset_get_static();
            
        } else {
        
            // Else update the specified icon sections
            $updatemsg = lolitemset_update_icons( $_GET[ 'lolitemsetdata' ] );
        }
    }
    
    if ( isset( $_GET[ 'lolitemsetmsg' ] ) )
        $updatemsg = base64_decode( $_GET[ 'lolitemsetmsg' ] );
        
        ?>
    
    <div class="wrap">
        <h2>LoL ItemSet Settings</h2>
        
        <?php // Update message display
            if ( isset( $updatemsg ) && $updatemsg != '' ) {?>
            <div id="message" class="updated below-h2">
                <p>
                    <?php echo $updatemsg;?>
                </p>
            </div>
        <?php }?>
        
        <form action="?page=lolitemset" id="lolitemset_settings_form" method="post"><input type="hidden" name="page" value="lolitemset"><input type="hidden" name="lolitemsetupdate" value="update">
            <div id="welcome-panel" class="welcome-panel">
                <div class="welcome-panel-content">
                    <table class="form-table">
                        <tr>
                        <th scope="row"><label for="lolitemset_api_key">API Key</label></th>
                        <td><input name="lolitemset_api_key" type="text" id="lolitemset_api_key" value="<?php echo get_option( 'lolitemset_api_key' );?>" class="regular-text" /></td>
                        </tr>
                        
                        <tr>
                        <th scope="row"><label for="lolitemset_region">Region</label></th>
                        <?php $lolitemset_region = get_option( 'lolitemset_region' ); ?>
                        <td><select name="lolitemset_region" id="lolitemset_region">
                                <option value="br" <?php selected( $lolitemset_region, 'br' ); ?>>BR</option>
                                <option value="eune" <?php selected( $lolitemset_region, 'eune' ); ?>>EUNE</option>
                                <option value="euw" <?php selected( $lolitemset_region, 'euw' ); ?>>EUW</option>
                                <option value="kr" <?php selected( $lolitemset_region, 'kr' ); ?>>KR</option>
                                <option value="lan" <?php selected( $lolitemset_region, 'lan' ); ?>>LAN</option>
                                <option value="las" <?php selected( $lolitemset_region, 'las' ); ?>>LAS</option>
                                <option value="na" <?php selected( $lolitemset_region, 'na' ); ?>>NA</option>
                                <option value="oce" <?php selected( $lolitemset_region, 'oce' ); ?>>OCE</option>
                                <option value="tr" <?php selected( $lolitemset_region, 'tr' ); ?>>TR</option>
                                <option value="ru" <?php selected( $lolitemset_region, 'ru' ); ?>>RU</option>
                            </select></td>
                        </tr>

                        <tr>
                        <th scope="row"><label for="lolitemset_months">Number of Months to Track</label></th>
                        <td><input name="lolitemset_months" type="text" id="lolitemset_months" value="<?php echo get_option( 'lolitemset_months' );?>" class="regular-text" /></td>
                        </tr>

                        <tr>
                        <th scope="row"><label for="lolitemset_static_data">Update Static Data</label></th>
                        <td><a href="?page=lolitemset&lolitemsetdata=runupdate" class="add-new-h2">Update</a></td>
                        </tr>

                        <tr>
                        <th scope="row"><label for="lolitemset_item_icons">Update Item Icons</label></th>
                        <td><a href="?page=lolitemset&lolitemsetdata=item" class="add-new-h2">Update</a></td>
                        </tr>

                        <tr>
                        <th scope="row"><label for="lolitemset_champion_icons">Update Champion Icons</label></th>
                        <td><a href="?page=lolitemset&lolitemsetdata=champion" class="add-new-h2">Update</a></td>
                        </tr>

                        <tr>
                        <th scope="row" colspan="2"><input class="button-primary menu-save" id="lolitemset_save_button" name="lolitemsetbutton_save" type="submit" value="Save Settings" /></th>
                        </tr>
                    </table>                
                </div>
            </div>
        </form>
    </div>
    
    <?php
}

// Admin Save Settings Function
function lolitemset_save_settings(){
    global $wpdb;
    
    // Return Var
    $returnme = '';
    
    // Quick way to do my update 
    foreach( $_POST as $k => $v ) {
        
        // All form element names start with this so only ones that changed will be updated and show the updated message
        if ( stristr( $k, 'lolitemset_' ) !== false ) {
            
            $updatestatus = update_option( $k, $v );
            
            if ( $updatestatus !== false ) {

                if ( $returnme != '' ) {
                    $returnme .= ' , ';
                } else {
                    $returnme .= 'Updated: ';
                }
                
                $returnme .= str_replace( 'lolitemset_', '', $k );
            }
                
        }
    }

    return $returnme;
}

// Retrieve Static Data Function
function lolitemset_get_static() {
    global $wpdb;
    
    // Ensures the API Key is valid before running the function.
    if ( LOLITEMSET_VERSION == '' )
        return 'Please enter a valid API Key and click Save Settings';
        
    // Input Champion Data
    $lolitemset_getchampiondata = wp_remote_get( LOLITEMSET_STATIC_DATA . 'champion?api_key=' . LOLITEMSET_API_KEY );
    $lolitemset_championjson = json_decode( $lolitemset_getchampiondata[ 'body' ] );
    //var_dump($lolitemset_championjson->data);
    lolitemset_data( 'champion', $lolitemset_championjson->data );
        
    // Input Item Data
    $lolitemset_getitemdata = wp_remote_get( LOLITEMSET_ITEM_STATIC_DATA );
    $lolitemset_itemjson = json_decode( $lolitemset_getitemdata[ 'body' ] );
    lolitemset_data( 'item', $lolitemset_itemjson->data );
        
    return 'Static data updated';
}
    
// Update Icon Function
function lolitemset_update_icons( $type, $name = null ) {
    global $wpdb;
    
    // Ensures the API Key is valid before running the function.
    if ( LOLITEMSET_VERSION == '' )
        return 'Please enter a valid API Key and click Save Settings';
    
    // Create dir if it does not exist
    if ( ! wp_mkdir_p( LOLITEMSET_UPLOAD_DIR . '/lolitemset/' . strtolower( $type ) . 'icons/' ) )
        return 'ERROR: Icon directory could not be created';
    
    // If name is null then pull names from database else just run the named icon
    if ( $name==null ) {
        
        // If statements ensure type is acceptable. Each is pulling the correct column from the db to keep the file names the same.
        if ( $type == 'champion' ) {
            $lolimages = $wpdb->get_results( "SELECT lolkey FROM " . constant( 'LOLITEMSET_' . strtoupper( $type ) ) );
            foreach ( $lolimages as $lolimage ) {
                copy( 'http://ddragon.leagueoflegends.com/cdn/5.2.1/img/' . strtolower( $type ) . '/' . $lolimage->lolkey . '.png', LOLITEMSET_UPLOAD_DIR . '/lolitemset/' . strtolower( $type ) . 'icons/' . $lolimage->lolkey . '.png' );
            }
            
            return 'Updated ' . $type . ' icons.';
        }

        if ( $type == 'item' ) {
            $lolimages = $wpdb->get_results( "SELECT id FROM " . constant( 'LOLITEMSET_' . strtoupper( $type ) ) );
            foreach ( $lolimages as $lolimage ){
                copy( 'http://ddragon.leagueoflegends.com/cdn/5.2.1/img/' . strtolower( $type ) . '/' . $lolimage->id . '.png', LOLITEMSET_UPLOAD_DIR . '/lolitemset/' . strtolower( $type ) . 'icons/' . $lolimage->id . '.png' );
            }

            return 'Updated ' . $type . ' icons.';
        }

        return 'Type Incorrect - Nothing Updated.';
    
    } else {
        // Copy the remote image files to local server
        copy( 'http://ddragon.leagueoflegends.com/cdn/' . LOLITEMSET_VERSION . '/img/' . $type . '/' . $name . '.png', LOLITEMSET_UPLOAD_DIR . '/lolitemset/' . strtolower( $type ) . 'icons/' . $name . '.png' );
    }
}
    
// Update or Insert Data Function
function lolitemset_data( $type, $data ) {
    global $wpdb;
        
    // Turn the outer shell into an array and sort it
    $dataarray = get_object_vars( $data );
    ksort( $dataarray );
    //var_dump($dataarray);
    //die();

    // Run through the array
    foreach( $dataarray as $k => $v ) {
        
        // Need a simplified name to make the shortcode more logical
        $simplified = preg_replace( '/[^a-z]/i', '', strtolower( $dataarray[ $k ]->name ) );
            
        // Build the input/update array depending on type
        if ( $type == 'champion' )
            $inputarray = array( 'id' => $dataarray[ $k ]->id, 'lolkey' => $dataarray[ $k ]->key, 'name' => $dataarray[ $k ]->name, 'title' => $dataarray[ $k ]->title, 'simplified_name' => $simplified );

        if ( $type == 'item' ) {
            if ( ! isset( $dataarray[ $k ]->plaintext ) )
                $dataarray[ $k ]->plaintext = null;
                
            if ( ! isset( $dataarray[ $k ]->description ) )
                $dataarray[ $k ]->description = null;
                
            if ( ! isset( $dataarray[ $k ]->group ) )
                $dataarray[ $k ]->group = null;

            if ( $altid = lolitemset_altids( $k ) !== false ) {
                $dataarray[ $k ]->altid = $altid;
            } else {
                $dataarray[ $k ]->altid = 0;
            }
            
            $inputarray = array( 'id' => $k, 'plaintext' => $dataarray[ $k ]->plaintext, 'description' => $dataarray[ $k ]->description, 'name' => $dataarray[ $k ]->name, 'lolgroup' => $dataarray[ $k ]->group, 'simplified_name' => $simplified, 'altid' => $dataarray[ $k ]->altid );
        }
        
        // Check the db for an element that has the same simple name
        if ( ! isset( $dataarray[ $k ]->id ) )
            $dataarray[ $k ]->id = $k;
            
        $dbcheck = $wpdb->get_results( "SELECT * FROM " . constant( 'LOLITEMSET_' . strtoupper( $type ) ) . " WHERE `id`='" . $dataarray[ $k ]->id . "'" );
        if ( $dbcheck ) {
        
            // If one exists then update it
            $wpdb->update( constant( 'LOLITEMSET_' . strtoupper( $type ) ), $inputarray, array( 'simplified_name' => $simplified ) );
            
        } else {
        
            // If one does not then create it
            $wpdb->insert( constant( 'LOLITEMSET_' . strtoupper( $type ) ), $inputarray );
        }
        

    }
    
    // If you want the icon update by clicking the update static button then uncomment the next line but load time will be longer.
    // lolitemset_update_icons($type);

}

function lolitemset_altids( $id ) {

    $altid_ar = array(
        3250 => 1304,
        3251 => 1302,
        3252 => 1300,
        3253 => 1303,
        3254 => 1301,
        3255 => 1314,
        3256 => 1312,
        3257 => 1310,
        3258 => 1313,
        3259 => 1311,
        3260 => 1319,
        3261 => 1317,
        3262 => 1315,
        3263 => 1318,
        3264 => 1316,
        3265 => 1324,
        3266 => 1322,
        3267 => 1320,
        3268 => 1323,
        3269 => 1321,
        3270 => 1329,
        3271 => 1327,
        3272 => 1325,
        3273 => 1328,
        3274 => 1326,
        3275 => 1334,
        3276 => 1332,
        3277 => 1330,
        3278 => 1333,
        3279 => 1331,
        3280 => 1309,
        3281 => 1307,
        3282 => 1305,
        3283 => 1308,
        3284 => 1306,
    );
    
    if ( array_key_exists( $id, $altid_ar ) ) {
        return $altid_ar[ $id ];
    } else {
        return false;
    }
}
?>
