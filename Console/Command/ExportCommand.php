<?php
/**
 * Magendoo Faq Export CLI Command
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Console\Command;

use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export FAQ questions or categories to a CSV file.
 *
 * Usage:
 *   bin/magento magendoo:faq:export --entity=questions --file=var/export/faq-questions.csv
 *   bin/magento magendoo:faq:export --entity=categories --file=var/export/faq-categories.csv
 */
class ExportCommand extends Command
{
    private const OPTION_ENTITY = 'entity';
    private const OPTION_FILE = 'file';

    public function __construct(
        private readonly QuestionCollectionFactory $questionCollectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('magendoo:faq:export')
            ->setDescription('Export FAQ questions or categories to CSV')
            ->addOption(self::OPTION_ENTITY, 'e', InputOption::VALUE_REQUIRED, 'Entity type: questions or categories')
            ->addOption(self::OPTION_FILE, 'f', InputOption::VALUE_OPTIONAL, 'Output file path (default: var/export/faq-{entity}.csv)');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Already set.
        }

        $entity = (string) $input->getOption(self::OPTION_ENTITY);
        if (!in_array($entity, ['questions', 'categories'], true)) {
            $output->writeln('<error>--entity must be "questions" or "categories".</error>');
            return Cli::RETURN_FAILURE;
        }

        $filePath = (string) ($input->getOption(self::OPTION_FILE) ?: "var/export/faq-{$entity}.csv");
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fp = fopen($filePath, 'w');
        if ($fp === false) {
            $output->writeln("<error>Cannot open file: {$filePath}</error>");
            return Cli::RETURN_FAILURE;
        }

        if ($entity === 'questions') {
            $this->exportQuestions($fp, $output);
        } else {
            $this->exportCategories($fp, $output);
        }

        fclose($fp);
        $output->writeln("<info>Exported to {$filePath}</info>");

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param resource $fp
     */
    private function exportQuestions($fp, OutputInterface $output): void
    {
        $headers = [
            'question_id', 'title', 'url_key', 'short_answer', 'full_answer',
            'status', 'visibility', 'position', 'sender_name', 'sender_email',
            'meta_title', 'meta_description', 'created_at', 'updated_at',
        ];
        fputcsv($fp, $headers, ",", "\"", "\\");

        $collection = $this->questionCollectionFactory->create();
        $count = 0;
        foreach ($collection as $question) {
            $row = [];
            foreach ($headers as $col) {
                $row[] = (string) $question->getData($col);
            }
            fputcsv($fp, $row, ",", "\"", "\\");
            $count++;
        }

        $output->writeln("<info>{$count} questions exported.</info>");
    }

    /**
     * @param resource $fp
     */
    private function exportCategories($fp, OutputInterface $output): void
    {
        $headers = [
            'category_id', 'name', 'url_key', 'description', 'position',
            'status', 'meta_title', 'meta_description', 'created_at', 'updated_at',
        ];
        fputcsv($fp, $headers, ",", "\"", "\\");

        $collection = $this->categoryCollectionFactory->create();
        $count = 0;
        foreach ($collection as $category) {
            $row = [];
            foreach ($headers as $col) {
                $row[] = (string) $category->getData($col);
            }
            fputcsv($fp, $row, ",", "\"", "\\");
            $count++;
        }

        $output->writeln("<info>{$count} categories exported.</info>");
    }
}
