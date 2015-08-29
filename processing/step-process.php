<html>
<head>
<title>Step Processor</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script type="text/javascript">
$.ajax({
    method: "GET",
    url: "../class/processing.php",
    data: { step: "startup" }
})
.done(function( msg ) {
    $( ".startup" ).html( msg );
    playerlist();
});
function playerlist() {
    $.ajax({
        method: "GET",
        url: "../class/processing.php",
        data: { step: "playerlist" }
    })
    .done(function( msg ) {
        $( ".playerlist" ).html( msg );
        matchlist( 0, 0 );
    });
}
function matchlist( playerId, startIndex ) {
    $.ajax({
        method: "GET",
        url: "../class/processing.php",
        data: { step: "matchlist", playerId: playerId, startIndex: startIndex }
    })
    .done(function( msg ) {
        // Return msg in format - html:playerId:startIndex - if no [1] field then call itemdata otherwise recall matchlist with [1] and [2]
        var match_array = msg.split( ':' );
        $( ".matchlist" ).html( match_array[0] );
        if ( typeof( match_array[1] ) != "undefined" ) {
            matchlist( match_array[1], match_array[2] );
        } else {
            itemdata();
        }
    });
}
function itemdata() {
    $.ajax({
        method: "GET",
        url: "../class/processing.php",
        data: { step: "itemdata" }
    })
    .done(function( msg ) {
        $( ".itemdata" ).html( msg );
        if ( msg.indexOf( 'Done' ) !== -1 ) {
            computedata();
        } else {
            itemdata();
        }
    });
}
function computedata() {
    $.ajax({
        method: "GET",
        url: "../class/processing.php",
        data: { step: "computedata" }
    })
    .done(function( msg ) {
        $( ".computedata" ).html( msg );
        if ( msg.indexOf( 'Done' ) !== -1 ) {
            cleanup();
        } else {
            computedata();
        }
    });
}
function cleanup() {
    $.ajax({
        method: "GET",
        url: "../class/processing.php",
        data: { step: "cleanup" }
    })
    .done(function( msg ) {
        $( ".cleanup" ).html( msg );
    });
}
</script>
</head>
<body>
<div id="startup">1. Start Up ... <span class="startup"></span></div>
<div id="playerlist">2. Grab Player List ... <span class="playerlist"></span></div>
<div id="matchlist">3. Grab Match List <span class="matchlist">(--/-- Players) (--/-- Games) ... </span></div>
<div id="itemdata">4. Grab Item Data <span class="itemdata">(--/-- Matches) ... </span></div>
<div id="computedata">5. Compute Data <span class="computedata">(--/-- Champions) ... </span></div>
<div id="cleanup">6. Clean Up ... <span class="cleanup"></span></div>
</body>
</html>
