<?php
/**
 * Magendoo Faq Import CLI Command
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Console\Command;

use Magendoo\Faq\Api\CategoryRepositoryInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Model\CategoryFactory;
use Magendoo\Faq\Model\QuestionFactory;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import FAQ questions or categories from a CSV file.
 *
 * CSV must have a header row matching the DB column names. If a row has a
 * question_id / category_id that already exists, the row is updated (upsert);
 * otherwise a new record is created.
 *
 * Usage:
 *   bin/magento magendoo:faq:import --entity=questions --file=var/export/faq-questions.csv
 *   bin/magento magendoo:faq:import --entity=categories --file=var/export/faq-categories.csv
 */
class ImportCommand extends Command
{
    private const OPTION_ENTITY = 'entity';
    private const OPTION_FILE = 'file';

    public function __construct(
        private readonly QuestionFactory $questionFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('magendoo:faq:import')
            ->setDescription('Import FAQ questions or categories from CSV')
            ->addOption(self::OPTION_ENTITY, 'e', InputOption::VALUE_REQUIRED, 'Entity type: questions or categories')
            ->addOption(self::OPTION_FILE, 'f', InputOption::VALUE_REQUIRED, 'Input CSV file path');
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

        $filePath = (string) $input->getOption(self::OPTION_FILE);
        if (!is_file($filePath) || !is_readable($filePath)) {
            $output->writeln("<error>File not found or not readable: {$filePath}</error>");
            return Cli::RETURN_FAILURE;
        }

        $fp = fopen($filePath, 'r');
        if ($fp === false) {
            $output->writeln("<error>Cannot open file: {$filePath}</error>");
            return Cli::RETURN_FAILURE;
        }

        $headers = fgetcsv($fp, 0, ',', '"', '\\');
        if (!$headers || empty($headers)) {
            $output->writeln('<error>CSV file has no header row.</error>');
            fclose($fp);
            return Cli::RETURN_FAILURE;
        }

        $created = 0;
        $updated = 0;
        $errors = 0;
        $line = 1;

        while (($row = fgetcsv($fp, 0, ',', '"', '\\')) !== false) {
            $line++;
            if (count($row) !== count($headers)) {
                $output->writeln("<comment>Line {$line}: column count mismatch — skipped.</comment>");
                $errors++;
                continue;
            }

            $data = array_combine($headers, $row);

            try {
                if ($entity === 'questions') {
                    $result = $this->importQuestion($data);
                } else {
                    $result = $this->importCategory($data);
                }
                if ($result === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $output->writeln("<error>Line {$line}: {$e->getMessage()}</error>");
                $errors++;
            }
        }

        fclose($fp);
        $output->writeln("<info>Import complete: {$created} created, {$updated} updated, {$errors} errors.</info>");

        return $errors > 0 ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }

    /**
     * @param array<string, string> $data
     * @return string 'created' or 'updated'
     */
    private function importQuestion(array $data): string
    {
        $idField = 'question_id';
        $id = !empty($data[$idField]) ? (int) $data[$idField] : 0;
        $isNew = true;

        if ($id > 0) {
            try {
                $question = $this->questionRepository->getById($id);
                $isNew = false;
            } catch (\Exception $e) {
                $question = $this->questionFactory->create();
            }
        } else {
            $question = $this->questionFactory->create();
        }

        unset($data[$idField], $data['created_at'], $data['updated_at']);
        foreach ($data as $key => $value) {
            if ($value !== '' && $value !== null) {
                $question->setData($key, $value);
            }
        }

        $this->questionRepository->save($question);

        return $isNew ? 'created' : 'updated';
    }

    /**
     * @param array<string, string> $data
     * @return string 'created' or 'updated'
     */
    private function importCategory(array $data): string
    {
        $idField = 'category_id';
        $id = !empty($data[$idField]) ? (int) $data[$idField] : 0;
        $isNew = true;

        if ($id > 0) {
            try {
                $category = $this->categoryRepository->getById($id);
                $isNew = false;
            } catch (\Exception $e) {
                $category = $this->categoryFactory->create();
            }
        } else {
            $category = $this->categoryFactory->create();
        }

        unset($data[$idField], $data['created_at'], $data['updated_at']);
        foreach ($data as $key => $value) {
            if ($value !== '' && $value !== null) {
                $category->setData($key, $value);
            }
        }

        $this->categoryRepository->save($category);

        return $isNew ? 'created' : 'updated';
    }
}
