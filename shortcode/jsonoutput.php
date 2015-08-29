<?php if ( ! isset( $_POST[ 'data' ] ) ) exit;

$blog_header = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-blog-header.php'; //
if ( ! file_exists( $blog_header ) )
    die();

require( $blog_header );

$itemlist = explode( 'lolitemset-header', $_POST[ 'data' ] );
$title = ( isset( $_POST[ 'lolitemsettitle' ] ) && ! empty( $_POST[ 'lolitemsettitle'] ) ) ? $_POST[ 'lolitemsettitle' ] : 'Custom Generated';
$json = (object)array(
    "title" => $title,
    "type" => "custom",
    "map" => "any",
    "mode" => "any",
    "priority" => false,
    "sortrank" => 0,
    "blocks" => array()
    );
    
$i = 1;
foreach ( $itemlist as $k => $block ) {

    if ( stristr( $block, '.png' ) !== false && stristr( $block, 'WILL NOT EXPORT' ) === false ) {
        $items = explode( '/lolitemset/itemicons/', $block );
        $itemarray = array();
    
        foreach ( $items as $key => $item ) {
            $id = substr( $item, 0, 4 );
            if ( is_numeric( $id ) === true ) {
                $itemarray[] = (object)array(
                    "id" => $id,
                    "count" => 1
                    );
            }
        }
    
        switch( $i ) {
            case 1:
                $type = $_POST[ 'lolitemsetfirst' ];
                break;
            case 2:
                $type = $_POST[ 'lolitemsetsecond' ];
                break;
            case 3:
                $type = $_POST[ 'lolitemsetthird' ];
                break;
            case 4:
                $type = $_POST[ 'lolitemsetfourth' ];
                break;
            case 5:
                $type = $_POST[ 'lolitemsetfifth' ];
                break;
            default:
                $type = 'GROUP';
                break;
        }
        
        $json->blocks[] = (object)array(
            "type" => $type,
            "recMath" => false,
            "minSummonerLevel" => -1,
            "maxSummonerLevel" => -1,
            "showIfSummonerSpell" => "",
            "hideIfSummonerSpell" => "",
            "items" => $itemarray
            );


    }
    $i++;
}

$json = json_encode( $json );

// Create dir if it does not exist
if ( ! wp_mkdir_p( LOLITEMSET_UPLOAD_DIR . '/lolitemset/tmp/' ) )
    die();

$tmp_file_name = 'lolitemset-'.rand().'.json';
$tmp_file = LOLITEMSET_UPLOAD_DIR . '/lolitemset/tmp/' . $tmp_file_name;
$tmp_path = LOLITEMSET_UPLOAD_URL . '/lolitemset/tmp/' . $tmp_file_name;

$file = fopen( $tmp_file, "w" );
fwrite( $file, $json );
fclose( $file );

echo $tmp_path;

?>
