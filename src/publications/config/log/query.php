<?php

return [
    /*----------------------------------------*
     * Basic
     *----------------------------------------*/

    "enable"    => env("LOG_QUERY_ENABLE", false),
    "directory" => env("LOG_QUERY_DIRECTORY", "query"),

    /*----------------------------------------*
     * Logging
     *----------------------------------------*/

    "slow_time_threshold" => env("LOG_QUERY_SLOW_TIME_THRESHOLD", 1000),
    "catch_transaction"   => env("LOG_QUERY_CATCH_TRANSACTION", false),
];
