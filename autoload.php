<?php

/*
 * This file is part of the stoneworld/socialite.
 *
 * (c) overtrue <i@stoneworld.github.io>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

spl_autoload_register(function ($class) {
    if (false !== stripos($class, 'Pegziq\WorkWeChat')) {
        require_once __DIR__.'/src/'.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 10)).'.php';
    }
});
