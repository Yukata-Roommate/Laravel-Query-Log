<?php

namespace YukataRm\Laravel\QueryLog\Provider;

use Illuminate\Support\ServiceProvider;

/**
 * Publication Service Provider
 * 
 * @package YukataRm\Laravel\QueryLog\Provider
 */
class PublicationServiceProvider extends ServiceProvider
{
    /**
     * publish config
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            $this->path("config") => config_path("yukata-roommate"),
        ], "yukata-roommate");
    }

    /**
     * get path to publications
     * 
     * @param string|array<string> $path
     * @return string
     */
    private function path(string|array $path): string
    {
        if (is_array($path)) $path = implode(DIRECTORY_SEPARATOR, $path);

        return __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "publications" . DIRECTORY_SEPARATOR . $path;
    }
}
