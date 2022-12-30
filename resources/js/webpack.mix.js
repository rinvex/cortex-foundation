module.exports = {
    scanForCssSelectors: [],
    webpackPlugins: [],
    safelist: [],
    install: [],
    copy: [
        {from: 'app/cortex/foundation/resources/images/', to: 'public/images/'},
        {from: 'node_modules/tinymce/plugins', to: 'public/tinymce/plugins/'},
        {from: 'node_modules/tinymce/skins/', to: 'public/tinymce/skins/'},
        {from: 'node_modules/muuri/dist/muuri.js', to: 'public/js/muuri.js'},
        {from: 'node_modules/web-animations-js/web-animations.min.js', to: 'public/js/web-animations.min.js'},
    ],
    mix: {
        css: [
            {input: 'app/cortex/foundation/resources/sass/theme-frontarea.scss', output: 'public/css/theme-frontarea.css'},
            {input: 'app/cortex/foundation/resources/sass/theme-adminarea.scss', output: 'public/css/theme-adminarea.css'},
            {input: 'app/cortex/foundation/resources/sass/theme-tenantarea.scss', output: 'public/css/theme-tenantarea.css'},
            {input: 'app/cortex/foundation/resources/sass/theme-managerarea.scss', output: 'public/css/theme-managerarea.css'},
            {input: 'app/cortex/foundation/resources/sass/muuri.scss', output: 'public/css/muuri.css'},
        ],
        js: [
            {input: 'app/cortex/foundation/resources/js/adjustable-layout/adjustable-layout.js', output: 'public/js/adjustable-layout.js'},
        ],
    },
};
