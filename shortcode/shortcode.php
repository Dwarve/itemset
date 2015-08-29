<?php if ( ! defined( 'ABSPATH' ) ) exit;

function lolitemset_shortcode( $atts ) {

    if ( isset( $atts[0] ) && $atts[0] == 'instruction' ) {
        return lolitemset_instruction();
    } else {
    // Initialize variables
    global $wpdb;
    
    $returnatts = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="' . LOLITEMSET_PLUGIN_URL . 'css/lolitemset.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>';
    $returnatts .= '<div class="lolitemset-return"><input type="button" class="lolitemset-export-button" value="Save Item Set" /><input type="button" class="lolitemset-return-button" value="Champion List" /></div>
            <div class="lolitemset-header lolitemset-title">
            <input type="text" name="title" value="Custom Item Set" /></div><div class="lolitemset-header lolitemset-first">
            <input type="text" name="first" value="First Group (81 - 100%)" /></div><div id="lolitemset-itemlist"></div>
        <script type="text/javascript">
            $(document).ready(function(){
                $(\'#lolitemset-itemlist\').sortable({
                    cancel: ".lolitemset-header"
                });
                $(\'.lolitemset-stored\').hide();
                $(\'.lolitemset-first\').hide();
                $(\'.lolitemset-title\').hide();
                $(\'.lolitemset-return\').hide();
                $(\'.lolitemset-return-button\').bind(\'click\', function() {
                    $(\'#lolitemset-itemlist\').hide();
                    $(\'.lolitemset-return\').hide();
                    $(\'input[name=first]\').val( "First Group (81 - 100%)" );
                    $(\'input[name=title]\').val( "Custom Item Set" );
                    $(\'.lolitemset-first\').hide();
                    $(\'.lolitemset-title\').hide();
                    $(\'.lolitemset-champion\').show();
                });
                $(\'.lolitemset-export-button\').bind(\'click\', function() {
                    $.ajax({
                        method: "POST",
                        url: "' . LOLITEMSET_PLUGIN_URL . 'shortcode/jsonoutput.php",
                        data: {
                            data: $(\'#lolitemset-itemlist\').html(),
                            lolitemsettitle: $(\'input[name=title]\').val(),
                            lolitemsetfirst: $(\'input[name=first]\').val(),
                            lolitemsetsecond: $(\'#lolitemset-itemlist input[name=second]\').val(),
                            lolitemsetthird: $(\'#lolitemset-itemlist input[name=third]\').val(),
                            lolitemsetfourth: $(\'#lolitemset-itemlist input[name=fourth]\').val(),
                            lolitemsetfifth: $(\'#lolitemset-itemlist input[name=fifth]\').val()
                        }
                    })
                    .done(function( returned ) {
                        window.open( returned );
                    });
                });
        ';
    $champdata = '';
    
    $sql = "SELECT * FROM `" . LOLITEMSET_CHAMPION . "` ORDER BY `name`";
    $champions = $wpdb->get_results( $sql, 'ARRAY_A' );
    foreach ( $champions as $key => $champion ) {
        $itemlist = unserialize( $champion[ 'itemlist' ] );

        $returnatts .= "
            $( '#lolitemset-" . $champion[ 'simplified_name' ] . "' ).bind('click', function() {
                $( '#lolitemset-itemlist' ).html( $( '#lolitemset-" . $champion[ 'simplified_name' ] . " span' ).html() ).show();
                $( '.lolitemset-champion' ).hide();
                $( '.lolitemset-first' ).show();
                $( '.lolitemset-title' ).show();
                $( '.lolitemset-return' ).show();
            });
            ";
        $champdata .= '<span id="lolitemset-' . $champion[ 'simplified_name' ] . '" class="lolitemset-champion">
            <span class="lolitemset-stored">
            ';
            
        $firstgroup = '';
        $secondgroup = '';
        $thirdgroup = '';
        $fourthgroup = '';
        $fifthgroup = '';
        $noexportgroup = '';
        
        foreach ( $itemlist as $item => $percent ) {
            $sql = "SELECT * FROM `" . LOLITEMSET_ITEM . "` WHERE `altid` = '" . $item . "'";
            $itemdata = $wpdb->get_results( $sql, 'ARRAY_A' );
            $itemimg = ( isset( $itemdata[0][ 'id' ] ) ) ? $itemdata[0][ 'id' ] : $item;

            $imgcheck = LOLITEMSET_UPLOAD_DIR . '/lolitemset/itemicons/' . $itemimg . '.png';
            $imglink = LOLITEMSET_UPLOAD_URL . '/lolitemset/itemicons/' . $itemimg . '.png';

            if ( file_exists( $imgcheck ) ) {
                if ( $percent >= .81 )
                    $firstgroup .= '<img src="' . $imglink . '" width="50px">';

                if ( $percent >= .61 && $percent < .81 )
                    $secondgroup .= '<img src="' . $imglink . '" width="50px">';

                if ( $percent >= .41 && $percent < .61 )
                    $thirdgroup .= '<img src="' . $imglink . '" width="50px">';

                if ( $percent >= .21 && $percent < .41 )
                    $fourthgroup .= '<img src="' . $imglink . '" width="50px">';

                if ( $percent >= .11 && $percent < .21 )
                    $fifthgroup .= '<img src="' . $imglink . '" width="50px">';

                if ( $percent >= 0 && $percent < .11 )
                    $noexportgroup .= '<img src="' . $imglink . '" width="50px">';
            }

        }
        $champdata .= $firstgroup . '
            <div class="lolitemset-header"><input type="text" name="second" value="Second Group (61 - 80%)" /></div>' . $secondgroup . '
            <div class="lolitemset-header"><input type="text" name="third" value="Third Group (41 - 60%)" /></div>' . $thirdgroup . '
            <div class="lolitemset-header"><input type="text" name="fourth" value="Fourth Group (21 - 40%)" /></div>' . $fourthgroup . '
            <div class="lolitemset-header"><input type="text" name="fifth" value="Fifth Group (11 - 20%)" /></div>' . $fifthgroup . '
            <div class="lolitemset-header">WILL NOT EXPORT (01 - 10%)</div>' . $noexportgroup;
            
        $champdata .= '</span>
            <img src="' . LOLITEMSET_UPLOAD_URL . '/lolitemset/championicons/' . $champion[ 'lolkey' ] . '.png" width="50px"></span>';
    }
    
    $returnatts .= '});</script><div id="lolitemset-championlist">' . $champdata . '</div>
        <div class="lolitemset-return"><input type="button" class="lolitemset-export-button" value="Save Item Set" /><input type="button" class="lolitemset-return-button" value="Champion List" /></div>';
    return $returnatts;
    }
}
add_shortcode( 'lolitemset', 'lolitemset_shortcode' );

function lolitemset_instruction() {
    $instr = '
    <div class="lolitemset-instruction">
        <p><b>TLDR: Goto Generator page, click a champ, change the text, move the items, click save, right click new window and Save As, save it to {YOUR LOL DIR}/Config/Champions/{CHAMP NAME}/Recommended</b></p>
        <p>
        The LoL Item Set Generator is based on stats from a months worth of Challenger and Master (NA) games (Over 15000 Games) on which items the players ended the game with. The percentages
        were determined by dividing the number of times a champ finished the game with an item by the total number of games played with that champ. The generator is very easy to use but
        if you have any questions or comments then feel free to fill out the contact form and let me know. Additionally if any bugs are found or improvements you want to recommend, please do
        so directly on GitHub at: <a href="https://github.com/Dwarve/itemset">My GitHub Item Set Page</a>.
        </p>
    </div>
    <div class="lolitemset-instruction">
        <b>Basic Instructions:</b>
        <ul>
            <li>Enable pop-ups for this site to ensure you get your JSON file.</li>
            <li>Click the generator link at the top.</li>
            <li>Once you see the champion list (grid of champion icons) then you are ready to start.</li>
            <li>Click whichever champion you want to make an item list for.</li>
            <li>The initial layout of the items is sorted by the percentage mentioned above.</li>
            <li>You can drag and drop the items into any slot you want to make the list match your preference.</li>
            <li>The title and group names are customizable so just change the text for any of those to whatever you like.</li>
            <li>The first 5 sections will be exported while the last one will not. (If a section has no items in it then it will be skipped)</li>
            <li>Once you have it customized to your liking, click Save Item List.</li>
            <li>If at any point you click Champion List it will reset all of your settings so be sure to Save the JSON when finished.</li>
            <li>After clicking Save, a new window will open (Pop-up blocker may block this) with the temp JSON file.</li>
            <li>Just right-click and Save As.</li>
            <li>Save the file to {YOUR LOL DIR}/Config/Champions/{CHAMP NAME}/Recommended where {YOUR LOL DIR} is where you have League of Legends installed and {CHAMP NAME} is the champion name. (Note: Wukong is called MonkeyKing)</li>
            <li>NOTE: The custom list will not show up under Item Sets in the client but will show up as an option in the shop in game.</li>
        </ul>
    </div>
    <div class="lolitemset-instruction">
        <b>How it all works:</b>
        <ul>
            <li>The main goal was to develop a piece of a larger wordpress plugin aimed at giving players and teams flexibility and ease of creating awesome sites for themselves.</li>
            <li>This part is aimed at making custom item sets easy to create but also gives quality suggestions based on a large amount of game data.</li>
            <li>The first step was to pull all of the players from Challenger and Master on ranked Solo in NA.</li>
            <li>Then pulled a list of matches for each player over the past month. (The list is unique as well)</li>
            <li>The resulting 15000+ games was shocking and thus also required a long time to pull all of the item data from every player in each game.</li>
            <li>In total the script ran for over 2 days to get all of the information from the Riot API.</li>
            <li>Once the item information was gathered, it was parsed to see how many times an item was still in the players inventory at the end of a game compared to number of games a champion was played.</li>
            <li>All of this information is stored locally in the database to make the viewing experience faster for the end user.</li>
            <li>Then Wordpress shortcode was setup to display all of the needed JS/jQuery and images to make the whole things.</li>
            <li>By utilizing Wordpress shortcode, it gives flexibility to add the display to any page on a Wordpress site by simply typing [lolitemset] into the editor for a given page.</li>
            <li>Additionally this instruction code is also shortcoded so that typing [lolitemset instruction] will display all of the text on this page wherever.</li>
            <li>The parsing script is setup as a JS/jQuery step processor making AJAX calls in order to allow it to run as long as needed without timing out.</li>
            <li>The goal with all of this being built into a Wordpress plugin is to simplify the setup process for anyone to utilize this on their own site.</li>
            <li>The Wordpress admin section has options to enter API key, choose region, update champion and item static data, and to download champion and item icons.</li>
            <li>Currently the processing script could be setup to be ran periodically to get more up to date data but a more advanced updating script is planned for the future.</li>

        </ul>
    </div>
    <p>Item Set Generator isn\'t endorsed by Riot Games and doesn\'t reflect the views or opinions of Riot Games or anyone officially involved in producing or managing League of Legends. League of Legends and Riot Games are trademarks or registered trademarks of Riot Games, Inc. League of Legends © Riot Games, Inc.</p>
    ';
    return $instr;
}

