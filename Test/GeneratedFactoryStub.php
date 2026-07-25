<?php
/**
 * Stand-in for the *Factory classes Magento's DI compiler writes into generated/code
 *
 * Magento does not ship factories as source files — `bin/magento setup:di:compile` (or
 * developer mode, on the fly) generates one per class or interface that some constructor
 * asks for. A unit suite running against a bare Composer install has no such compile step,
 * so classes like Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory simply do not exist
 * and any test that doubles one dies in the mock generator's reflection pass.
 *
 * The tests only ever need the factory to exist as a type and to expose create(); they
 * always stub its behaviour. Test/bootstrap.php therefore aliases missing factories onto
 * this class, but only when running standalone — inside a Magento install the real
 * generated class is present and is used instead.
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test;

/**
 * Mirrors the shape of a DI-generated factory: a single create() taking constructor data.
 */
class GeneratedFactoryStub
{
    /**
     * Create the target instance
     *
     * Never reached in practice — every test stubs create() — so it is left unimplemented
     * rather than pretending to resolve a class without an object manager.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data = [])
    {
        return null;
    }
}
