<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        // Feature tests must execute the current route source, not a cache left
        // by an earlier build/checkpoint. Route-cache compilation is verified
        // separately after the suite.
        $cachedRoutes = __DIR__.'/../bootstrap/cache/routes-v7.php';
        if (is_file($cachedRoutes)) {
            unlink($cachedRoutes);
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
