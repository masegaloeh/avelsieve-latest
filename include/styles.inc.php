<?php

function avelsieve_css_styles() {
    global $color;
    return '
.avelsieve_div {
        width: 90%;
        margin-left: auto;
        padding: 0.5em;
        margin-right: auto;
        text-align:left;
        border: 3px solid '.$color[5].';
}
.avelsieve_quoted {
        border-left: 1em solid '.$color[12].';
}
.avelsieve_source {
        width: 99%;
        overflow:auto;
        border: 1px dotted '.$color[12].';
        font-family: monospace;
        font-size: 0.8em;
}
';
}


