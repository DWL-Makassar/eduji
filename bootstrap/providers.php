<?php

use App\Cms\Providers\CmsServiceProvider;
use App\Core\Providers\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use App\Website\Providers\WebsiteServiceProvider;

return [
    CoreServiceProvider::class,
    CmsServiceProvider::class,
    WebsiteServiceProvider::class,
    AppServiceProvider::class,
];
