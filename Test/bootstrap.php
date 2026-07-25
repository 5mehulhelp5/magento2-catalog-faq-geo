<?php
/**
 * Magendoo Faq unit-test bootstrap
 *
 * The suite has to boot in two places:
 *
 *  1. Inside a Magento install, where the module sits in app/code/Magendoo/Faq and the
 *     install-wide unit framework bootstrap is the right entry point (it registers the
 *     Magento autoloader and the ComponentRegistrar the tests rely on).
 *  2. As a standalone checkout — CI clones the repository on its own and runs
 *     `composer install` inside it, so there is no Magento install above it and the only
 *     autoloader is the module's own vendor/autoload.php.
 *
 * Picking the wrong one is a hard failure ("Cannot open bootstrap script"), so resolve it
 * here rather than hard-coding a relative path in phpunit.xml.dist.
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

$magentoUnitBootstrap = __DIR__ . '/../../../../../dev/tests/unit/framework/bootstrap.php';
if (file_exists($magentoUnitBootstrap)) {
    require $magentoUnitBootstrap;

    return;
}

$standaloneAutoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($standaloneAutoloader)) {
    require $standaloneAutoloader;

    /**
     * Supply the DI-generated factories the suite doubles. Registered after Composer's
     * autoloader so it only ever sees names Composer could not resolve, and it aliases a
     * name only when the class or interface it is a factory *for* really exists — a
     * genuinely misspelled class still fails loudly. See Test/GeneratedFactoryStub.php.
     */
    spl_autoload_register(
        static function (string $className): void {
            if (!str_ends_with($className, 'Factory')) {
                return;
            }

            $target = substr($className, 0, -strlen('Factory'));
            if (!class_exists($target) && !interface_exists($target)) {
                return;
            }

            class_alias(\Magendoo\Faq\Test\GeneratedFactoryStub::class, $className);
        }
    );

    return;
}

throw new \RuntimeException(
    'Magendoo_Faq: no autoloader found. Run "composer install" in the module directory, '
    . 'or run the suite from a Magento install that provides '
    . 'dev/tests/unit/framework/bootstrap.php.'
);
