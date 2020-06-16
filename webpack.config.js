var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .addEntry('js/app', './assets/js/app.js')
    .addStyleEntry('css/app', './assets/css/app.scss')
    .enableReactPreset()
    .enableSassLoader()
    .enablePostCssLoader()
    .addExternals({
        Config: JSON.stringify({
            mercureUrl: Encore.isProduction() ? 'https://php-rfc-watch.beberlei.de/mercure' : 'http://127.0.0.1:3000'
        }),
    })
;

module.exports = Encore.getWebpackConfig();
