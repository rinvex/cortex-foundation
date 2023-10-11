module.exports = {
    scanForCssSelectors: [],
    webpackPlugins: [],
    safelist: [],
    install: [
        "datatables.net-searchpanes@^2.0.2",
        "datatables.net-searchpanes-bs@^2.0.2",
    ],
    copy: [
        {from: 'app/modules/cortex/foundation/resources/images/', to: 'public/images/'},
        {from: 'node_modules/tinymce/plugins', to: 'public/tinymce/plugins/'},
        {from: 'node_modules/tinymce/skins/', to: 'public/tinymce/skins/'},
        {from: 'node_modules/datatables.net-searchpanes-bs/css/searchPanes.bootstrap.css', to: 'public/css/searchPanes.css'},
        {from: 'node_modules/datatables.net-searchpanes/js/dataTables.searchPanes.js', to: 'public/js/dataTables.searchPanes.js'},
        {from: 'node_modules/datatables.net-searchpanes-bs/js/searchPanes.bootstrap.js', to: 'public/js/searchPanes.bootstrap.js'},
    ],
    mix: {
        css: [
            {input: 'app/modules/cortex/foundation/resources/sass/theme-frontarea.scss', output: 'public/css/theme-frontarea.css'},
            {input: 'app/modules/cortex/foundation/resources/sass/theme-adminarea.scss', output: 'public/css/theme-adminarea.css'},
        ],
        js: [],
    },
};
