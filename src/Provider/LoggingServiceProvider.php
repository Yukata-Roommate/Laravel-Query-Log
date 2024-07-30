<?php

namespace YukataRm\Laravel\QueryLog;

use Illuminate\Support\ServiceProvider;

use YukataRm\Laravel\SimpleLogger\Interface\LoggerInterface;
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
 * Logging Service Provider
 * 
 * @package YukataRm\Laravel\QueryLog
 */
class LoggingServiceProvider extends ServiceProvider
{
    /**
     * register logging
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->isEnable()) return;

        $this->registerLoggingSQL();
        $this->registerLoggingTransaction();
    }

    /**
     * register logging of executed SQL queries
     *
     * @return void
     */
    private function registerLoggingSQL(): void
    {
        $executeLoggingSQL = function (LogLevelEnum $logLevel, string $message): void {
            $logger = $this->getLogger($logLevel);

            $logger->add($message);

            $logger->logging();
        };

        $slowTimeThreshold = $this->configSlowTimeThreshold();

        DB::listen(static function (QueryExecuted $event) use ($executeLoggingSQL, $slowTimeThreshold) {
            $sql = $event->connection
                ->getQueryGrammar()
                ->substituteBindingsIntoRawSql(
                    sql: $event->sql,
                    bindings: $event->connection->prepareBindings($event->bindings),
                );

            $logLevel = $event->time > $slowTimeThreshold ? LogLevelEnum::WARNING : LogLevelEnum::INFO;

            $executeLoggingSQL($logLevel, sprintf("SQL: %s; %.2f ms", $sql, $event->time));
        });
    }

    /**
     * register logging of executed transactions
     * 
     * @return void
     */
    private function registerLoggingTransaction(): void
    {
        if (!$this->configCatchTransaction()) return;

        $executeLoggingSQL = function (LogLevelEnum $logLevel, string $message): void {
            $logger = $this->getLogger($logLevel);

            $logger->add($message);

            $logger->logging();
        };

        Event::listen(static function (TransactionBeginning $event) use ($executeLoggingSQL) {
            $executeLoggingSQL(LogLevelEnum::INFO, "TRANSACTION BEGIN");
        });

        Event::listen(static function (TransactionCommitting $event) use ($executeLoggingSQL) {
            $executeLoggingSQL(LogLevelEnum::INFO, "TRANSACTION COMMITTING");
        });

        Event::listen(static function (TransactionCommitted $event) use ($executeLoggingSQL) {
            $executeLoggingSQL(LogLevelEnum::INFO, "TRANSACTION COMMITTED");
        });

        Event::listen(static function (TransactionRolledBack $event) use ($executeLoggingSQL) {
            $executeLoggingSQL(LogLevelEnum::WARNING, "TRANSACTION ROLLED BACK");
        });
    }

    /*----------------------------------------*
     * Logging
     *----------------------------------------*/

    /**
     * whether to enable logging
     * 
     * @return bool
     */
    private function isEnable(): bool
    {
        return $this->configEnable();
    }

    /**
     * get Logger instance
     * 
     * @param \YukataRm\SimpleLogger\Enum\LogLevelEnum $logLevel
     * @return \YukataRm\Laravel\SimpleLogger\Interface\LoggerInterface
     */
    private function getLogger(LogLevelEnum $logLevel): LoggerInterface
    {
        $logger = LoggerFacade::make($logLevel);

        $logger->setDirectory($this->configDirectory());

        $logger->setFormat("%message%");

        return $logger;
    }

    /*----------------------------------------*
     * Config
     *----------------------------------------*/

    /**
     * get config or default
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function config(string $key, mixed $default): mixed
    {
        return config("yukata-roommate.log.query.{$key}", $default);
    }

    /**
     * get config enable
     * 
     * @return bool
     */
    protected function configEnable(): bool
    {
        return $this->config("enable", false);
    }

    /**
     * get config directory
     * 
     * @return string
     */
    protected function configDirectory(): string
    {
        return $this->config("directory", "query");
    }

    /**
     * get config slow time threshold
     * 
     * @return int
     */
    protected function configSlowTimeThreshold(): int
    {
        return $this->config("slow_time_threshold", 1000);
    }

    /**
     * get config catch transaction
     * 
     * @return bool
     */
    protected function configCatchTransaction(): bool
    {
        return $this->config("catch_transaction", false);
    }
}
