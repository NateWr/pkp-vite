# pkp-vite

Vite integration for themes built for PKP's software (OJS, OMP, and OPS).

## Usage

> **The following instructions assume you already have [composer](https://getcomposer.org/) and [vite](https://vite.dev/) set up in your theme.**

Run the following commands from the root directory of your custom theme in order to add this library as a dependency.

```
composer require natewr/pkp-vite
npm install vite-pkp-theme
```

Add `pkpThemePlugin` to your vite configuration (`vite.config.js`).

```js
import { defineConfig } from 'vite'
import path from 'path'
import pkpThemePlugin from 'vite-pkp-theme'

export default defineConfig({
  plugins: [
    pkpThemePlugin({
      configFile: './.vite.server.json',
    }),
  ],
  build: {
    manifest: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'src', 'main.js'),
    },
  },
})
```

Add the following to your `.gitignore` file.

```
# Vite
dist
.vite.server.json
```

Configure Vite in your theme's `init()` method.

```php
require __DIR__ . '/vendor/autoload.php';

use NateWr\vite\Loader;
use TemplateManager;

import('lib.pkp.classes.plugins.ThemePlugin');

class ExampleTheme extends ThemePlugin
{
    // ...

    public function init()
    {
        $viteLoader = new Loader(
            /**
             * Absolute path to vite's manifest.json file
             */
            manifestPath: dirname(__FILE__) . '/dist/.vite/manifest.json',

            /**
             * Absolute path to vite-pkp-theme config file
             *
             * This must match the `configFile` param to pkpThemePlugin
             * in the vite.config.js file.
             */
            serverPath: dirname(__FILE__) . '/.vite.server.json'),

            /**
             * Base URL to vite build directory
             *
             * This is usually /dist/ in your theme's root directory. If
             * you want to change it, it must be changed in your
             * vite.config.js file.
             */
            buildUrl: $this->getPluginUrl() . '/dist',

            /**
             * Pass the TemplateManager from OJS/OMP/OPS. This is used to
             * register assets in the system.
             */
            templateManager: TemplateManager::getManager(
                Application::get()->getRequest()
            ),

            /**
             * (Optional) Theme plugin
             *
             * Pass the theme plugin if you want to register JS/CSS
             * assets through ThemePlugin::addScript() and
             * ThemePLugin::addStyle() instead of using TemplateManager.
             *
             * This makes it easier to work with child themes.
             */
            theme: $this,
        );

        /**
         * Load asset entry points
         *
         * Typically, you should only have a single entry point
         * which imports all files, including CSS and image assets.
         */
        $viteLoader->load(['src/main.js']);
    }
}
```

## Credit

This library is distributed under GPL 3.0. It is based on [php-vite](https://github.com/mindplay-dk/php-vite) by [@mindplay-dk](https://github.com/mindplay-dk).