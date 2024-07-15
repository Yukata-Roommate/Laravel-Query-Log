<?php

return [
    /**
     * Basic
     * 
     * 基本設定
     * 
     * enable             : bool 実行されたSQLをログに出力するかどうか
     * directory          : string SQLログの出力先ディレクトリ
     * slow_time_threshold: int スロークエリとして扱う閾値(ms)
     * catch_transaction  : bool トランザクションに関するログを出力するかどうか
     */
    "enable"              => env("LOG_QUERY_ENABLE", false),
    "directory"           => env("LOG_QUERY_DIRECTORY", "query"),
    "slow_time_threshold" => env("LOG_QUERY_SLOW_TIME_THRESHOLD", 1000),
    "catch_transaction"   => env("LOG_QUERY_CATCH_TRANSACTION", false),
];
