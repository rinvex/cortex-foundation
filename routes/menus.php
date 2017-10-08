<?php

declare(strict_types=1);

use Spatie\Menu\Laravel\Link;
use Cortex\Foundation\Models\Menu as MenuModel;

if (config('cortex.foundation.route.locale_prefix')) {
    $langSwitcherHeader = Link::to('#', '<span class="fa fa-globe"></span> '.app('laravellocalization')->getCurrentLocaleNative().' <span class="caret"></span>')->addClass('dropdown-toggle')->setAttribute('data-toggle', 'dropdown');
    $langSwitcherBody = function (MenuModel $menu) {
        $menu->addClass('dropdown-menu');
        $menu->addParentClass('dropdown');
        foreach (app('laravellocalization')->getSupportedLocales() as $key => $locale) {
            $menu->url(app('laravellocalization')->localizeURL(request()->fullUrl(), $key), $locale['name']);
        }
    };

    Menu::adminareaTopbar()->submenu($langSwitcherHeader, $langSwitcherBody);
    Menu::tenantareaTopbar()->submenu($langSwitcherHeader, $langSwitcherBody);
    Menu::memberareaTopbar()->submenu($langSwitcherHeader, $langSwitcherBody);
    Menu::guestareaTopbar()->submenu($langSwitcherHeader, $langSwitcherBody);
}
