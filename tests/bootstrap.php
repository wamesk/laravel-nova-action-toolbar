<?php

declare(strict_types=1);

/**
 * The package's own vendor/ after `composer install`; otherwise the vendor/ of an
 * application that holds the package in a sub-folder (e.g. wamesk/<package>).
 */
foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../vendor/autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        return require $autoload;
    }
}

fwrite(STDERR, "No vendor/autoload.php found. Run `composer install` in the package first.\n");

exit(1);
