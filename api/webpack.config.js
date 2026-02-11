const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/ts/app.ts')
    .addEntry('base', './assets/ts/common/base.ts')
    .addEntry('game', './assets/ts/game/main.ts')
    .addEntry('gameModalInitializer', './assets/ts/game/modalInitializer.ts')
    .addEntry('gameResultsExpander', './assets/ts/game/resultsExpander.ts')
    .addEntry('season', './assets/ts/season/main.ts')
    .addEntry('seasonModalInitializer', './assets/ts/season/modalInitializer.ts')
    .addEntry('team', './assets/ts/team/main.ts')
    .addEntry('teamModalInitializer', './assets/ts/team/modalInitializer.ts')
    .addEntry('teamStats', './assets/ts/team/resultSeasonStats.ts')
    .addEntry('gameResult', './assets/ts/gameResult/main.ts')
    .addEntry('gameResultModalInitializer', './assets/ts/gameResult/modalInitializer.ts')
    .addEntry('sport', './assets/ts/sport/main.ts')
    .addEntry('sportModalInitializer', './assets/ts/sport/modalInitializer.ts')
    .addEntry('event', './assets/ts/event/main.ts')
    .addEntry('eventModalInitializer', './assets/ts/event/modalInitializer.ts')
    .addEntry('country', './assets/ts/country/main.ts')
    .addEntry('countryModalInitializer', './assets/ts/country/modalInitializer.ts')
    .addEntry('home', './assets/ts/home/main.ts')
    .addEntry('competition', './assets/ts/competition/main.ts')
    .addEntry('competitionModalInitializer', './assets/ts/competition/modalInitializer.ts')
    .addEntry('bracket', './assets/ts/bracket/svg.ts')

    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .enableTypeScriptLoader()
;

module.exports = Encore.getWebpackConfig();
