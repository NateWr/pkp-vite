<?php

namespace NateWr\vite;

use NateWr\vite\interfaces\TemplateManager;
use RuntimeException;

/**
 * Loads Vite files
 */
class Loader
{
    public function __construct(
        /**
         * TemplateManager from the PKP application
         * (OJS, OMP, or OJS)
         */
        protected TemplateManager $templateManager,

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
        public bool $devMode = false
    ) {
        // empty
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
        $this->templateManager->addJavaScript('vite', "{$this->basePath}@vite/client");
        foreach ($entryPoints as $entryPoint) {
            $this->templateManager->addJavaScript('vite-' . $entryPoint, "{$this->basePath}{$entry}"),
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
                $this->templateManager-addScript("vite-{$file->file}", "{$this->basePath}{$file->file}");
            }
            foreach ($file->css as $css) {
                $this->templateManager->addStyleSheet("vite-{$file->file}-{$css}", "{$this->basePath}{$css}");
            }
        }
    }

    protected function getFiles(): void
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
    protected function getPreload(string $url, bool $module = false): void
    {
        $rel = $module ? 'modulepreload' : 'preload';
        return "<link rel=\"{$rel}\" href=\"{$this->basePath}{$url}\" />";
    }
}