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
 * question_id / category_id that already exists, the row is updated. A row
 * whose id does NOT exist is reported as an error and skipped (silently
 * re-creating it under a new id would duplicate content when importing
 * against a different environment); leave the id column empty to create a
 * new record.
 *
 * Relation columns written by ExportCommand (store_ids, category_ids,
 * product_ids, customer_group_ids as comma-separated ids; tags as
 * comma-separated names) are applied through the repository. An empty
 * relation cell clears the assignments when its header is present; omit the
 * column entirely to leave existing assignments untouched.
 *
 * A leading single quote added by ExportCommand's spreadsheet-formula
 * protection is stripped.
 *
 * Usage:
 *   bin/magento magendoo:faq:import --entity=questions --file=var/export/faq-questions.csv
 *   bin/magento magendoo:faq:import --entity=categories --file=var/export/faq-categories.csv
 */
class ImportCommand extends Command
{
    private const OPTION_ENTITY = 'entity';
    private const OPTION_FILE = 'file';

    /**
     * Relation columns holding comma-separated id lists, per entity.
     */
    private const ID_LIST_COLUMNS = [
        'questions' => ['store_ids', 'category_ids', 'product_ids', 'customer_group_ids'],
        'categories' => ['store_ids', 'customer_group_ids'],
    ];

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
        if (!$headers) {
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
     * @throws \RuntimeException When the row references an id that does not exist.
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
                throw new \RuntimeException(
                    "question_id {$id} does not exist - row skipped."
                    . ' Clear the id column to create a new record.'
                );
            }
        } else {
            $question = $this->questionFactory->create();
        }

        unset($data[$idField], $data['created_at'], $data['updated_at']);
        $this->applyData($question, $data, self::ID_LIST_COLUMNS['questions']);

        $this->questionRepository->save($question);

        return $isNew ? 'created' : 'updated';
    }

    /**
     * @param array<string, string> $data
     * @return string 'created' or 'updated'
     * @throws \RuntimeException When the row references an id that does not exist.
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
                throw new \RuntimeException(
                    "category_id {$id} does not exist - row skipped."
                    . ' Clear the id column to create a new record.'
                );
            }
        } else {
            $category = $this->categoryFactory->create();
        }

        unset($data[$idField], $data['created_at'], $data['updated_at']);
        $this->applyData($category, $data, self::ID_LIST_COLUMNS['categories']);

        $this->categoryRepository->save($category);

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Apply CSV values to the model.
     *
     * Scalar cells: empty values are skipped so a sparse CSV cannot blank
     * existing data. Id-list relation cells: applied whenever the column is
     * present (an empty cell means "no assignments", matching what
     * ExportCommand wrote for an unrestricted record).
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @param array<string, string> $data
     * @param string[] $idListColumns
     * @return void
     */
    private function applyData(
        \Magento\Framework\Model\AbstractModel $model,
        array $data,
        array $idListColumns
    ): void {
        foreach ($data as $key => $value) {
            $value = $this->unescapeCell((string) $value);

            if (in_array($key, $idListColumns, true)) {
                $model->setData($key, $this->parseIdList($value));
                continue;
            }

            if ($value !== '') {
                $model->setData($key, $value);
            }
        }
    }

    /**
     * Parse a comma-separated id list cell into an int array.
     *
     * @param string $value
     * @return int[]
     */
    private function parseIdList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(
            array_map(
                'intval',
                array_filter(array_map('trim', explode(',', $value)), 'strlen')
            )
        );
    }

    /**
     * Strip the leading single quote added by ExportCommand's formula protection.
     *
     * Applies only when the quote is followed by a formula trigger
     * (=, +, -, @, tab, CR), so legitimate leading quotes survive.
     *
     * @param string $value
     * @return string
     */
    private function unescapeCell(string $value): string
    {
        if (strlen($value) >= 2 && $value[0] === "'" && strpbrk($value[1], "=+-@\t\r") !== false) {
            return substr($value, 1);
        }

        return $value;
    }
}
