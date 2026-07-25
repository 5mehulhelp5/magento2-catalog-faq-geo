<?php
/**
 * Magendoo Faq Reindex CLI Command
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Console\Command;

use Magendoo\Faq\Model\UrlRewrite\FaqUrlRewriteGenerator;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to regenerate FAQ URL rewrites for all categories and questions.
 */
class ReindexCommand extends Command
{
    /**
     * @var FaqUrlRewriteGenerator
     */
    private FaqUrlRewriteGenerator $urlRewriteGenerator;

    /**
     * @var State
     */
    private State $appState;

    /**
     * @param FaqUrlRewriteGenerator $urlRewriteGenerator
     * @param State $appState
     */
    public function __construct(
        FaqUrlRewriteGenerator $urlRewriteGenerator,
        State $appState
    ) {
        $this->urlRewriteGenerator = $urlRewriteGenerator;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('magendoo:faq:reindex')
            ->setDescription('Regenerate URL rewrites for all FAQ categories and questions');
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            try {
                $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                // Area code already set — safe to ignore.
            }

            $output->writeln('<info>Regenerating FAQ URL rewrites...</info>');
            $errors = $this->urlRewriteGenerator->generateAll();

            if ($errors) {
                // URL key collisions are skipped per entity instead of aborting the run,
                // which would leave the rewrite table half-rebuilt.
                foreach ($errors as $error) {
                    $output->writeln('<comment>' . $error . '</comment>');
                }
                $output->writeln(
                    sprintf(
                        '<comment>FAQ URL rewrites regenerated with %d skipped %s (see above).</comment>',
                        count($errors),
                        count($errors) === 1 ? 'entity' : 'entities'
                    )
                );
            } else {
                $output->writeln('<info>FAQ URL rewrites regenerated successfully.</info>');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error regenerating FAQ URL rewrites: ' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
