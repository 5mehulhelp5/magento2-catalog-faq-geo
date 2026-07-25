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
 * Relation assignments (stores, categories, products, tags, customer groups)
 * are exported alongside the main-table columns so an export/import round
 * trip preserves them: id lists as comma-separated values, tags as a
 * comma-separated list of tag names (the format the repository accepts).
 *
 * Cell values starting with a spreadsheet formula trigger (=, +, -, @, tab,
 * CR) are prefixed with a single quote; ImportCommand strips it again.
 *
 * Usage:
 *   bin/magento magendoo:faq:export --entity=questions --file=var/export/faq-questions.csv
 *   bin/magento magendoo:faq:export --entity=categories --file=var/export/faq-categories.csv
 */
class ExportCommand extends Command
{
    private const OPTION_ENTITY = 'entity';
    private const OPTION_FILE = 'file';

    /**
     * Rows fetched per page so the whole table is never held in memory.
     */
    private const PAGE_SIZE = 500;

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
        $columns = [
            'question_id', 'title', 'url_key', 'short_answer', 'full_answer',
            'status', 'visibility', 'position', 'sender_name', 'sender_email',
            'meta_title', 'meta_description', 'created_at', 'updated_at',
        ];
        $relationColumns = ['store_ids', 'category_ids', 'product_ids', 'tags', 'customer_group_ids'];
        fputcsv($fp, array_merge($columns, $relationColumns), ",", "\"", "\\");

        $collection = $this->questionCollectionFactory->create();
        $connection = $collection->getConnection();
        $relations = [
            'store_ids' => $this->fetchRelationMap(
                $connection,
                $collection->getTable('magendoo_faq_question_store'),
                'question_id',
                'store_id'
            ),
            'category_ids' => $this->fetchRelationMap(
                $connection,
                $collection->getTable('magendoo_faq_question_category'),
                'question_id',
                'category_id'
            ),
            'product_ids' => $this->fetchRelationMap(
                $connection,
                $collection->getTable('magendoo_faq_question_product'),
                'question_id',
                'product_id'
            ),
            'tags' => $this->fetchTagNameMap($connection, $collection),
            'customer_group_ids' => $this->fetchRelationMap(
                $connection,
                $collection->getTable('magendoo_faq_question_customer_group'),
                'question_id',
                'customer_group_id'
            ),
        ];

        $count = $this->writeRows($fp, $collection, 'question_id', $columns, $relationColumns, $relations);

        $output->writeln("<info>{$count} questions exported.</info>");
    }

    /**
     * @param resource $fp
     */
    private function exportCategories($fp, OutputInterface $output): void
    {
        $columns = [
            'category_id', 'name', 'url_key', 'description', 'position',
            'status', 'meta_title', 'meta_description', 'created_at', 'updated_at',
        ];
        $relationColumns = ['store_ids', 'customer_group_ids'];
        fputcsv($fp, array_merge($columns, $relationColumns), ",", "\"", "\\");

        $collection = $this->categoryCollectionFactory->create();
        $connection = $collection->getConnection();
        $relations = [
            'store_ids' => $this->fetchRelationMap(
                $connection,
                $collection->getTable('magendoo_faq_category_store'),
                'category_id',
                'store_id'
            ),
            'customer_group_ids' => $this->fetchRelationMap(
                $connection,
                $collection->getTable('magendoo_faq_category_customer_group'),
                'category_id',
                'customer_group_id'
            ),
        ];

        $count = $this->writeRows($fp, $collection, 'category_id', $columns, $relationColumns, $relations);

        $output->writeln("<info>{$count} categories exported.</info>");
    }

    /**
     * Write all collection rows page by page.
     *
     * @param resource $fp
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @param string $idField
     * @param string[] $columns
     * @param string[] $relationColumns
     * @param array $relations Relation maps keyed by column, then entity id.
     * @return int Number of rows written.
     */
    private function writeRows(
        $fp,
        \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection,
        string $idField,
        array $columns,
        array $relationColumns,
        array $relations
    ): int {
        $collection->setOrder($idField, 'ASC');
        $collection->setPageSize(self::PAGE_SIZE);
        $lastPage = $collection->getLastPageNumber();
        $count = 0;

        for ($page = 1; $page <= $lastPage; $page++) {
            $collection->clear();
            $collection->setCurPage($page);

            foreach ($collection as $item) {
                $id = (int) $item->getData($idField);
                $row = [];
                foreach ($columns as $col) {
                    $row[] = $this->escapeCell((string) $item->getData($col));
                }
                foreach ($relationColumns as $col) {
                    $row[] = $this->escapeCell($relations[$col][$id] ?? '');
                }
                fputcsv($fp, $row, ",", "\"", "\\");
                $count++;
            }
        }

        return $count;
    }

    /**
     * Fetch a junction table as an entity-id => comma-separated-values map.
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $table
     * @param string $idColumn
     * @param string $valueColumn
     * @return array<int, string>
     */
    private function fetchRelationMap(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        string $table,
        string $idColumn,
        string $valueColumn
    ): array {
        $select = $connection->select()
            ->from($table, [$idColumn, $valueColumn])
            ->order([$idColumn, $valueColumn]);

        $map = [];
        foreach ($connection->fetchAll($select) as $row) {
            $id = (int) $row[$idColumn];
            $map[$id] = isset($map[$id])
                ? $map[$id] . ',' . $row[$valueColumn]
                : (string) $row[$valueColumn];
        }

        return $map;
    }

    /**
     * Fetch question-id => comma-separated tag names (the repository's tag format).
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return array<int, string>
     */
    private function fetchTagNameMap(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
    ): array {
        $select = $connection->select()
            ->from(['qt' => $collection->getTable('magendoo_faq_question_tag')], ['question_id'])
            ->join(
                ['t' => $collection->getTable('magendoo_faq_tag')],
                'qt.tag_id = t.tag_id',
                ['name']
            )
            ->order(['qt.question_id', 't.name']);

        $map = [];
        foreach ($connection->fetchAll($select) as $row) {
            $id = (int) $row['question_id'];
            $map[$id] = isset($map[$id]) ? $map[$id] . ',' . $row['name'] : (string) $row['name'];
        }

        return $map;
    }

    /**
     * Neutralize spreadsheet formula injection.
     *
     * A cell starting with =, +, -, @, tab or CR executes as a formula when
     * the CSV is opened in a spreadsheet, so such cells are prefixed with a
     * single quote (ImportCommand reverses this).
     *
     * @param string $value
     * @return string
     */
    private function escapeCell(string $value): string
    {
        if ($value !== '' && strpbrk($value[0], "=+-@\t\r") !== false) {
            return "'" . $value;
        }

        return $value;
    }
}
