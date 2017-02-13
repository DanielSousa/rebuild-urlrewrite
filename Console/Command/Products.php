<?php
/**
 * Rebuild Url Rewrite for magento 2
 * Copyright (C) 2016
 *
 * This file is part of DanielSousa/UrlRewrite.
 *
 * DanielSousa/UrlRewrite is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace DanielSousa\UrlRewrite\Console\Command;

use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Products extends Command
{


    /** Command name */
    const NAME = 'urlrewrite:rebuild:products';

    const DESCRIPTION = 'Rebuild Product URL Rewrites';

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var \Magento\UrlRewrite\Model\UrlPersistInterface
     */
    private $urlPersist;
    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    private $progressBar;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\State $state
    )
    {
        $this->objectManager = $objectManager;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription(self::DESCRIPTION);
        parent::configure();
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode('adminhtml');
            $this->output = $output;
            $productCollection = $this->getProductCollection();
            $this->setupProgress();
            $this->progressBar->start($productCollection->getSize());


            /** @var \Magento\Framework\Model\ResourceModel\Iterator $iterator */
            $iterator = $this->objectManager->create('\Magento\Framework\Model\ResourceModel\Iterator');
            $iterator->walk(
                $productCollection->getSelect(),
                [[$this, 'callbackGenerateProductUrl']],
                [
                    'product' => $this->createProductFactory()
                ]
            );
            $this->progressBar->finish();
        } catch (\Exception $e) {
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }


    /**
     * Generate product url by product
     * @param $args
     */
    public function callbackGenerateProductUrl($args)
    {
        if (!isset($args['row']['entity_id'])) {
            $this->output->writeln('Id not found');
            return;
        }
        $id = $args['row']['entity_id'];
        $this->progressBar->setMessage($id);
        $this->progressBar->advance();

        try {
            $product = clone $args['product'];
            $product->load($id);
            $product->setStoreId(null);
            $this->replaceUrls(
                $this->prepareUrls($product)
            );
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage() . '- Product ID -' . $id);
            return;
        }
    }

    /**
     * Replace data with new product urls
     *
     * @param $urls
     */
    private function replaceUrls(array $urls)
    {
        if (empty($urls)) {
            $this->output->writeln('Product without new urls');
            return;
        }
        /** @var \Magento\UrlRewrite\Model\UrlPersistInterface $urlPersist */
        $urlPersist = $this->objectManager->create('\Magento\UrlRewrite\Model\UrlPersistInterface');
        $urlPersist->replace(
            $urls
        );
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function getProductCollection()
    {

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')->create();
        return $collection->addAttributeToSelect('*');
    }

    /**
     * Setup progress bar
     */
    private function setupProgress()
    {
        $this->progressBar = $this->objectManager->create('\Symfony\Component\Console\Helper\ProgressBar',
            [
                'output' => $this->output
            ]
        );
        $this->progressBar->setFormat(
            '<info>Product ID: %message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %estimated%        '
        );
    }

    /**
     * Create product factory
     *
     * @return \Magento\Catalog\Model\ProductFactory
     */
    private function createProductFactory()
    {
        return $this->objectManager->create('\Magento\Catalog\Model\ProductFactory')->create();
    }


    /**
     *  Remove Product urls
     *
     * @param $productId
     * @param null $storeId
     * @return bool
     */
    protected function removeProductUrls($productId, $storeId = null)
    {
        if (!is_numeric($productId)) {
            return false;
        }
        $data = [
            UrlRewrite::ENTITY_ID => $productId,
            UrlRewrite::ENTITY_TYPE => \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator::ENTITY_TYPE,
            UrlRewrite::REDIRECT_TYPE => 0,
        ];

        if (!is_null($storeId)) {
            $data[] = [UrlRewrite::STORE_ID => $storeId];
        }
        try {
            $this->urlPersist->deleteByData($data);
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     *  Generate list of product urls
     *
     * @param $product
     * @return UrlRewrite[]
     */
    private function prepareUrls($product)
    {
        /** @var \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator $productUrlRewriteGenerator */
        $productUrlRewriteGenerator = $this->objectManager->create('\Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator');
        return $productUrlRewriteGenerator->generate($product);
    }
}
