<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Http\HttpClient;

abstract class AbstractResource
{
    public function __construct(
        protected HttpClient $http
    ) {
    }
}
