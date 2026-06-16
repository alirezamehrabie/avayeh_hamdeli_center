<?php

return [
    /*
    |--------------------------------------------------------------------------
    | People Search Driver
    |--------------------------------------------------------------------------
    |
    | The index page currently uses the database driver. If search volume or
    | typo tolerance requirements grow, this seam can be switched to a Scout
    | backed implementation such as Meilisearch or Typesense.
    |
    | Supported today: "database"
    | Recommended future external engines: "meilisearch", "typesense"
    |
    */
    'driver' => env('PEOPLE_SEARCH_DRIVER', 'database'),
];
