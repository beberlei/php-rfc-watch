var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .addStyleEntry('css/app', './assets/css/app.scss')
    .enableReactPreset()
    .enableSassLoader()
    .addExternals({
        Config: JSON.stringify({}),
    })
;

module.exports = Encore.getWebpackConfig();
