<?php

/**
 *
 * File to be ran by Cron or other automated service on a standard interval
 * Step-processor being developed to allow full initial load using AJAX
 *
 */

class lolitemset_processing {

    // Class variables
    var $blog_header;
    var $api_key;
    var $region;
    var $month;
    var $timestamp;
    var $current;
    var $previous;
    var $starttime;

    // Constructor
    function __construct() {

        // Require step processing
        if ( ! isset( $_GET[ 'step' ] ) )
            $this->process_er( 'This script must be ran through step-processing.', true );

        // Require the core Wordpress stuff
        $this->blog_header = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-blog-header.php'; //
        if ( ! file_exists( $this->blog_header ) )
            $this->process_er( 'Auto changing this file has failed. Please manually update the previous variable with the full dir path to wp-blog-header.php', true );
            
        require( $this->blog_header );

        $this->process_main();
    }
    
    // Main processing function
    function process_main() {
        global $wpdb;
        
        // Set needed variables
        $this->api_key = get_option( 'lolitemset_api_key', null );
        if ( ! $this->api_key )
            $this->process_er( 'No API Key', true );

        $this->process_time();
        if ( $this->timestamp === false || $this->current === false || $this->previous === false )
            $this->process_er( 'There was an error with the timestamps.', true );

        $this->region = get_option( 'lolitemset_region', 'na' );

        switch( $_GET[ 'step' ] ) {
            case "startup":
                echo 'Done';
                break;
            case "playerlist":
                // Get players from challenger and master solo (TODO: Add ranked 5x5 team)
                $this->process_api( 'https://' . $this->region . '.api.pvp.net/api/lol/' . $this->region . '/v2.5/league/challenger?type=RANKED_SOLO_5x5&api_key=' . $this->api_key, array( 'Challenger' ) );
                //$this->process_api( 'https://' . $this->region . '.api.pvp.net/api/lol/' . $this->region . '/v2.5/league/challenger?type=RANKED_TEAM_5x5&api_key=' . $this->api_key );
                $this->process_api( 'https://' . $this->region . '.api.pvp.net/api/lol/' . $this->region . '/v2.5/league/master?type=RANKED_SOLO_5x5&api_key=' . $this->api_key, array( 'Master' ) );
                //$this->process_api( 'https://' . $this->region . '.api.pvp.net/api/lol/' . $this->region . '/v2.5/league/master?type=RANKED_TEAM_5x5&api_key=' . $this->api_key );
                echo 'Done';
                break;
            case "matchlist":
                // Get matchlist from player list
                $sql = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_playerlist` WHERE `processed` = 0 ORDER BY `autoinc`";
                $result = $this->process_db( $sql );
                $sql2 = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_playerlist` WHERE `processed` = 1 ORDER BY `autoinc`";
                $result2 = $this->process_db( $sql2 );
                $total_players = count( $result ) + count( $result2 );
                if ( $_GET[ 'playerId' ] == 0 ) {
                    $player_id = $result[0][ 'playerid' ];
                } else {
                    $player_id = $_GET[ 'playerId' ];
                }
                $extra_data = ( $_GET[ 'startIndex' ] == 0 ) ? array( "0" ) : array( $_GET[ 'startIndex' ] );

                $next_run = $this->process_matches( $player_id, $extra_data );

                if ( $next_run[ 'player_id' ] == 0 ) {
                    $sql3 = "UPDATE `" . $wpdb->prefix . "lolitemset_playerlist` SET `processed` = 1 WHERE `playerid` = " . $player_id;
                    $result3 = $this->process_db( $sql3 );
                    $current_players = count( $result2 ) + 1;
                    if ( count( $result2 ) + 1 == count( $result ) + count( $result2 ) ) {
                        echo "(" . $current_players . "/" . $total_players . " Players) ... Done";
                    } else {
                        echo "(" . $current_players . "/" . $total_players . " Players) (--/-- Games) ... :0:0";
                    }
                } else {
                    echo "(" . count( $result2 ) . "/" . $total_players . " Players) (" . $next_run[ 'start_index' ] . "/" . $next_run[ 'total_games' ] . " Games) ... :" . $player_id . ":" . $next_run[ 'start_index' ];
                }
                break;
            case "itemdata":
                // Get itemlist
                $sql = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_matchlist` WHERE `processed` = '0'";
                $match_list = $this->process_db( $sql );
                $ml_count = count( $match_list );
                $sql2 = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_matchlist` WHERE `processed` = '1'";
                $match_list2 = $this->process_db( $sql2 );
                $ml_count2 = count( $match_list2 );
                $ml_count_total = $ml_count + $ml_count2;
                $ml_count_update = $ml_count2 + 1;
                if ( isset( $match_list[0] ) ) {
                    $get_match = $this->process_api( 'https://' . $this->region . '.api.pvp.net/api/lol/' . $this->region . '/v2.2/match/' . $match_list[0][ 'matchId' ] . '?api_key=' . $this->api_key );
                    if ( $get_match !== false ) {
                        echo "(" . $ml_count_update . "/" . $ml_count_total . " Matches) ...";
                        $sql = "UPDATE `" . $wpdb->prefix . "lolitemset_matchlist` SET `processed`= 1 WHERE `autoinc`=" . $match_list[0][ 'autoinc' ];
                        $this->process_db( $sql );
                    } else {
                        echo "(" . $ml_count2 . "/" . $ml_count_total . " Matches) ...";
                    }
                } else {
                    echo '(' . $ml_count2 . '/' . $ml_count_total . ' Matches) ... Done';
                }
                break;
            case "computedata":
                $sql = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_champions`";
                $champions = $this->process_db( $sql );
                foreach ( $champions as $k => $v ) {
                    $item_array = array();
                    $sql = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_itempurchases` WHERE `champion_id` = '" . $v[ 'id' ] . "'";
                    $items = $this->process_db( $sql );
                    foreach ( $items as $key => $val ) {
                        $item_array[ $val[ 'item_id' ] ] = $val[ 'total_purchases' ] / $v[ 'total_games' ];
                    }
                    arsort( $item_array );
                    $sql = "UPDATE `" . $wpdb->prefix . "lolitemset_champions` SET `itemlist`= '" . serialize( $item_array ) . "' WHERE `autoinc`=" . $v[ 'autoinc' ];
                    $this->process_db( $sql );
                }
                echo '(' . count( $champions ) . '/' . count( $champions ) . ' Champions) ... Done';
                break;
            case "cleanup":
                echo 'Done';
                break;
        }

    }

    // Get Matchlists
    function process_matches( $player_id, $extra_data = array() ) {
        $start_index = ( isset( $extra_data[0] ) ) ? $extra_data[0] : 0;
        $end_index = $start_index + 20;

        $new_index = $this->process_api( 'https://' . $this->region . '.api.pvp.net/api/lol/' . $this->region . '/v2.2/matchlist/by-summoner/' . $player_id . '?rankedQueues=RANKED_SOLO_5x5&beginTime=' . $this->timestamp . '&beginIndex=' . $start_index . '&endIndex=' . $end_index . '&api_key=' . $this->api_key, $extra_data );
        $value = ( $new_index[0] == 0 ) ? array( 'player_id' => 0, 'start_index' => 0, 'total_games' => 0 ) : array( 'player_id' => $player_id, 'start_index' => $new_index[0], 'total_games' => $new_index[1] );

        return $value;
    }
    
    // API function
    function process_api( $address, $extra_data = array() ) {
        $response = wp_remote_get( $address );

        if ( is_wp_error( $response ) )
            return false;
            
        if ( isset( $response[ 'response' ][ 'code' ] ) && $response[ 'response' ][ 'code' ] == 200 ) {
            return $this->process_json( $response[ 'body' ], $extra_data );
        } else {
            $this->process_er( 'Bad response or no connection.' );
        }
    }

    // JSON parser
    function process_json( $json, $extra_data = array() ) {
        global $wpdb;
        
        $json = json_decode( $json );
        if ( isset( $json->entries ) ) {
            foreach ( $json->entries as $k => $v ) {
                $tier = ( isset( $extra_data[0] ) && ($extra_data[0] == 'Challenger' || $extra_data[0] == 'Master' ) ) ? $extra_data[0] : '';
                $sql = "INSERT INTO `" . $wpdb->prefix . "lolitemset_playerlist` (`playerid`, `tier`) VALUES ('" . $v->playerOrTeamId . "','" . $tier . "')";
                $this->process_db( $sql );
            }
        }
        if ( isset( $json->matches ) ) {
            foreach ( $json->matches as $k => $v ) {
                $sql = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_matchlist` WHERE `matchId` = '" . $v->matchId . "'";
                if ( $this->process_db( $sql ) == null ) {
                    $sql = "INSERT INTO `" . $wpdb->prefix . "lolitemset_matchlist` (`timestamp`, `matchId`) VALUES ('" . $v->timestamp . "','" . $v->matchId . "')";
                    $this->process_db( $sql );
                }
            }

            $new_index = $json->startIndex + 20;

            if ( $json->totalGames > $json->startIndex + 20 ) {
                return array( $json->startIndex + 20, $json->totalGames );
            } else {
                return array( 0, 0 );
            }
        }
        if ( isset( $json->participants ) ) {
            foreach ( $json->participants as $k => $v ) {
                $sql = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_itempurchases` WHERE `champion_id` = " . $v->championId;
                $result = $this->process_db( $sql );
                $item_ids = array( $v->stats->item0, $v->stats->item1, $v->stats->item2, $v->stats->item3, $v->stats->item4, $v->stats->item5, $v->stats->item6 );
                $item_ids = array_unique( $item_ids );
                $stored_ids = array();
                foreach ( $result as $key => $val ) {
                    if ( in_array( $val[ 'item_id' ], $item_ids ) ) {
                        $purchases = $val[ 'total_purchases' ] + 1;
                        $sql = "UPDATE `" . $wpdb->prefix . "lolitemset_itempurchases` SET `total_purchases`=" . $purchases . " WHERE `autoinc`=" . $val[ 'autoinc' ];
                        $this->process_db( $sql );
                    }
                    $stored_ids[] = $val[ 'item_id' ];
                }
                $diff_ids = array_diff( $item_ids, $stored_ids );
                foreach ( $diff_ids as $key => $val ) {
                    if ( $val != 0 ) {
                        $sql = "INSERT INTO `" . $wpdb->prefix . "lolitemset_itempurchases` (`item_id`, `champion_id`, `total_purchases`) VALUES ('" . $val . "','" . $v->championId . "', 1)";
                        $this->process_db( $sql );
                    }
                }
                $sql = "SELECT * FROM `" . $wpdb->prefix . "lolitemset_champions` WHERE `id` = " . $v->championId;
                $champions = $this->process_db( $sql );
                $games = $champions[0][ 'total_games' ] + 1;
                $sql = "UPDATE `" . $wpdb->prefix . "lolitemset_champions` SET `total_games`=" . $games . " WHERE `id`=" . $v->championId;
                $this->process_db( $sql );
            }
        }
    }
    
    // Handle Timestamps
    function process_time() {
        $this->month = get_option( 'lolitemset_months', 1 );
        $start_date = date( "Y-m-d", mktime( 0, 0, 0, date( "m" ) - $this->month, date( "d" ), date( "Y" ) ) );
        $this->starttime = strtotime( $start_date ) * 1000;
        $this->previous = get_option( 'lolitemset_previous', null );
        if ( $this->previous == null ) {
            $this->timestamp = $this->starttime;
        } else {
            $this->timestamp = $this->previous;
        }

        $current_date = date( "Y-m-d" );
        $this->current = strtotime( $current_date ) * 1000;
    }
    
    // DB Writer
    function process_db( $data ) {
        global $wpdb;
        return $wpdb->get_results( $data, 'ARRAY_A' );
    }

    // Error handler
    function process_er( $msg, $killprocessing = false ) {
        // ADD ERROR LOGGING HERE

        // Temp display
        echo $msg;

        if ( $killprocessing )
            die();
    }

}

// Self instantiating file
new lolitemset_processing();
?>
