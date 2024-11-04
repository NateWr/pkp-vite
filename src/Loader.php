<?php

namespace NateWr\vite;

use NateWr\vite\interfaces\TemplateManager;
use RuntimeException;
use ThemePlugin;

/**
 * Loads Vite files
 */
class Loader
{
    /**
     * @param TemplateManager
     */
    protected $templateManager;

    public function __construct(
        /**
         * TemplateManager from the PKP application
         * (OJS, OMP, or OJS)
         *
         * @param TemplateManager
         */
        $templateManager,

        /**
         * Absolute path to vite's manifest.json file
         */
        protected string $manifestPath,

        /**
         * Base path to vite files
         *
         * In dev mode, this should be the local or network
         * address to the vite server.
         *
         * In production, this should be the relative path to the
         * built vite files within the OJS, OMP or OPS installation.
         * Typically, this is `/plugins/themes/<plugin>/dist/...`.
         */
        protected string $basePath,

        /**
         * Whether to serve built files or load from vite's
         * HMR server.
         */
        public bool $devMode = false,

        /**
         * Register assets as part of a theme
         */
        public ?ThemePlugin $theme = null,
    ) {
        $this->templateManager = $templateManager;
    }

    /**
     * Load Vite assets for one or more entry points
     *
     * Adds the scripts, styles, and other assets to the template
     * using the TemplateManager class from OJS, OMP or OPS.
     */
    public function load(array $entryPoints): void
    {
        if ($this->devMode) {
            $this->loadDev($entryPoints);
        } else {
            $this->loadProd();
        }
    }

    protected function loadDev(array $entryPoints): void
    {
        $this->loadScript('vite', "{$this->basePath}@vite/client", ['type' => 'module']);
        foreach ($entryPoints as $entryPoint) {
            $this->loadScript('vite-' . $entryPoint, "{$this->basePath}{$entryPoint}", ['type' => 'module']);
        }
    }

    protected function loadProd(): void
    {
        $files = $this->getFiles();
        foreach ($files as $file) {
            if (str_ends_with($file->file, '.js')) {
                $this->templateManager->addHeader("vite-{$file->file}-preload", $this->getPreload($file->file, true));
            }
            if ($file->isEntry) {
                $this->loadScript("vite-{$file->file}", "{$this->basePath}{$file->file}", ['type' => 'module']);
            }
            foreach ($file->css as $css) {
                $this->loadStyle("vite-{$file->file}-{$css}", "{$this->basePath}{$css}");
            }
        }
    }

    protected function getFiles(): array
    {
        if (!is_readable($this->manifestPath)) {
            throw new RuntimeException(
                file_exists($this->manifestPath)
                    ? "Manifest file is not readable: {$this->manifestPath}"
                    : "Manifest file not found: {$this->manifestPath}"
            );
        }

        return array_map(
            fn(array $chunk) => ManifestFile::create($chunk),
            json_decode(file_get_contents($this->manifestPath), true)
        );
    }

    /**
     * Get preload tag
     */
    protected function getPreload(string $url, bool $module = false): string
    {
        $rel = $module ? 'modulepreload' : 'preload';
        return "<link rel=\"{$rel}\" href=\"{$this->basePath}{$url}\" />";
    }

    /**
     * Load script asset
     */
    protected function loadScript(string $name, string $path, array $args): void
    {
        if ($this->theme) {
            $this->theme->addScript($name, $path, $args);
        } else {
            $this->templateManager->addJavaScript($name, $path, $args);
        }
    }

    /**
     * Load style asset
     */
    protected function loadStyle(string $name, string $path, array $args = []): void
    {
        if ($this->theme) {
            $this->theme->addStyle($name, $path, $args);
        } else {
            $this->templateManager->addStyleSheet($name, $path, $args);
        }
    }
}