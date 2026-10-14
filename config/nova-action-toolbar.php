<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | How the buttons sit next to each other on the index and the detail:
    |
    | "grouped"  — one strip with a slightly darker background and thin dividers,
    |              the ⋯ menu as its own tile at the end.
    | "separate" — separate buttons with a gap between them.
    |
    | Table rows always show icons the way Nova draws them.
    |
    */

    'layout' => env('NOVA_ACTION_TOOLBAR_LAYOUT', 'grouped'),

];
