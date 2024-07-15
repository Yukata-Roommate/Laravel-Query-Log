<?php

namespace YukataRm\Laravel\QueryLog;

use Illuminate\Support\ServiceProvider as Provider;

use YukataRm\Laravel\SimpleLogger\Facade\Logger as LoggerFacade;
use YukataRm\SimpleLogger\Enum\LogLevelEnum;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;

use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitting;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;

/**
 * ServiceProvider
 * 実行されたSQLをログに出力する処理とパッケージに含まれるファイルの公開の設定を行う
 * 
 * @package YukataRm\Laravel\QueryLog
 */
class ServiceProvider extends Provider
{
    /**
     * publications配下を公開する際に使うルートパス
     *
     * @var string
     */
    private string $publicationsPath = __DIR__ . DIRECTORY_SEPARATOR . "publications";

    /**
     * アプリケーションの起動時に実行する
     * SQLが実行された際にログに出力する処理を登録する
     *
     * @return void
     */
    public function register(): void
    {
        // ログを残さない場合は次の処理へ
        if (!$this->isEnable()) return;

        // ログを出力するコールバック関数
        $executeLoggingSQL = function (LogLevelEnum $logLevel, string $message): void {
            $this->logging($logLevel, $message);
        };

        DB::listen(static function (QueryExecuted $event) use ($executeLoggingSQL) {
            // SQLを取得する
            $sql = $event->connection
                ->getQueryGrammar()
                ->substituteBindingsIntoRawSql(
                    sql: $event->sql,
                    bindings: $event->connection->prepareBindings($event->bindings),
                );

            // 実行時間が閾値を超えている場合はWARNINGレベルでログを出力する
            $logLevel = $event->time > config("log.query.slow_time_threshold", 1000) ? LogLevelEnum::WARNING : LogLevelEnum::DEBUG;

            // ログを出力する
            $executeLoggingSQL($logLevel, sprintf("SQL: %s; %.2f ms", $sql, $event->time));
        });

        if (config("log.query.catch_transaction", false)) {
            Event::listen(static function (TransactionBeginning $event) use ($executeLoggingSQL) {
                // トランザクションが開始されたことをログに出力する
                $executeLoggingSQL(LogLevelEnum::DEBUG, "TRANSACTION BEGIN");
            });

            Event::listen(static function (TransactionCommitting $event) use ($executeLoggingSQL) {
                // トランザクションがコミットされることをログに出力する
                $executeLoggingSQL(LogLevelEnum::DEBUG, "TRANSACTION COMMITTING");
            });

            Event::listen(static function (TransactionCommitted $event) use ($executeLoggingSQL) {
                // トランザクションがコミットされたことをログに出力する
                $executeLoggingSQL(LogLevelEnum::DEBUG, "TRANSACTION COMMITTED");
            });

            Event::listen(static function (TransactionRolledBack $event) use ($executeLoggingSQL) {
                // トランザクションがロールバックされたことをログに出力する
                $executeLoggingSQL(LogLevelEnum::WARNING, "TRANSACTION ROLLED BACK");
            });
        }
    }

    /**
     * アプリケーションのブート時に実行する
     * パッケージに含まれるファイルの公開の設定を行う
     * 
     * @return void
     */
    public function boot(): void
    {
        // config配下の公開
        // 自作パッケージ共通タグ
        $this->publishes([
            $this->publicationsPath . DIRECTORY_SEPARATOR . "config" => config_path(),
        ], "publications");

        // このパッケージのみ
        $this->publishes([
            $this->publicationsPath . DIRECTORY_SEPARATOR . "config" => config_path(),
        ], "query-log");
    }

    /*----------------------------------------*
     * Logging
     *----------------------------------------*/

    /**
     * ログを残すかどうかを取得する
     * 
     * @return bool
     */
    private function isEnable(): bool
    {
        // ログを残すかどうか
        $isEnable = config("log.query.enable", false);

        // ログを残すかどうかを返す
        return $isEnable;
    }

    /**
     * ログを出力する
     * 
     * @param \YukataRm\SimpleLogger\Enum\LogLevelEnum $logLevel
     * @param string $message
     */
    private function logging(LogLevelEnum $logLevel, string $message): void
    {
        // Loggerのインスタンスを取得する
        $logger = LoggerFacade::make($logLevel);

        // ログの出力先ディレクトリを設定する
        $logger->setDirectory(config("log.query.directory", "query"));

        // Loggerに出力する内容を設定する
        $logger->add($message);

        // ログに出力する
        $logger->logging();
    }
}
