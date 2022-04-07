<?php

namespace OptimistDigital\NovaTranslationsLoaderPHP;

use Exception;
use Laravel\Nova\Nova;
use Illuminate\Support\Arr;
use Illuminate\Container\Container;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Contracts\Translation\Loader;

trait LoadsNovaTranslations
{
    protected $packageTranslationsDir;
    protected $packageName;
    protected $publishTranslations;

    /**
     * Loads translations into the Nova system.
     *
     * @param string $packageTranslationsDir The directory for the packages' translation files.
     * @param string $packageName The name of the current package (ie 'nova-menu-builder').
     * @param boolean $publishTranslations Whether to also automatically make translations publishable.
     * @return null
     **/
    protected function loadTranslations($packageTranslationsDir, $packageName, $publishTranslations = true)
    {
        $packageTranslationsDir = $packageTranslationsDir ?? __DIR__ . '/../resources/lang';
        $packageTranslationsDir = rtrim($packageTranslationsDir, '/');
        $packageName = trim($packageName);

        $this->translations($packageTranslationsDir, $packageName, $publishTranslations);
    }

    private function translations($pckgTransDir, $pckgName, $publish)
    {
        $publishTransDir = lang_path("vendor/{$pckgName}");

        if (app()->runningInConsole() && $publish) {
            $this->publishes([$pckgTransDir => $publishTransDir], 'translations');
        }

        $this->loadTranslationsFrom($pckgTransDir, "{$pckgName}-vendor");
        $this->loadTranslationsFrom($publishTransDir, $pckgName);

        if (!method_exists(Nova::class, 'translations')) throw new Exception('Nova::translations method not found, please ensure you are using the correct version of Nova.');

        Nova::serving(function (ServingNova $event) use ($pckgName) {
            $this->callAfterResolving('translator', function ($translator) use ($pckgName) {
                $locale = app()->getLocale();
                $fallbackLocale = config('app.fallback_locale');

                $loader = $translator->getLoader();
                $vndrName = "{$pckgName}-vendor";

                Nova::translations(array_merge(
                    // First vendor files
                    Arr::dot($loader->load('en', 'nova-multiselect-field', $vndrName), "{$pckgName}::"),
                    Arr::dot($loader->load($fallbackLocale, 'nova-multiselect-field', $vndrName), "{$pckgName}::"),
                    Arr::dot($loader->load($locale, 'nova-multiselect-field', $vndrName), "{$pckgName}::"),

                    // Then project files
                    Arr::dot($loader->load('en', 'nova-multiselect-field', $pckgName), "{$pckgName}::"),
                    Arr::dot($loader->load($fallbackLocale, 'nova-multiselect-field', $pckgName), "{$pckgName}::"),
                    Arr::dot($loader->load($locale, 'nova-multiselect-field', $pckgName), "{$pckgName}::"),
                ));
            });
        });
    }
}
