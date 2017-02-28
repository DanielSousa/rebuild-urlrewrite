<?php
/**
 * Rebuild Url Rewrite for magento 2
 * Copyright (C) 2017
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Products extends AbstractUrlRewriteCommand
{

    /**
     * Name of product input option
     */
    const INPUT_PRODUCT = 'product';

    /**
     * Force of force input option
     */
    const INPUT_FORCE = 'force';

    /**
     * Name of product input option
     */
    const INPUT_STORE = 'store';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $collection = null;

    /**
     * Generate product url by product
     * @param $args
     */
    public function callbackGenerateProductUrl($args)
    {
        try {
            if (!isset($args['row']['entity_id'])) {
                $this->output->writeln('Id not found');
                return;
            }
            $productId = $args['row']['entity_id'];
            $this->progressBar->setMessage($productId);
            $this->progressBar->advance();


            $product = clone $args['product'];
            $product->load($productId);

            $storeId = $args['storeId'];
            if (is_null($storeId)) {
                $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }
            $product->setStoreId($storeId);
            $this->removeProductUrls($productId, $storeId);
            $this->replaceUrls(
                $this->prepareUrls($product)
            );
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage() . '- Product ID -' . $productId);
            return;
        }
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
            $this->getUrlPersist()->deleteByData($data);
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
     * @return array
     */
    private function prepareUrls($product)
    {
        return $this->getProductUrlRewriteGenerator()->generate($product);
    }

    /**
     *  Get Product Url Generator
     *
     * @return \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator
     */
    protected function getProductUrlRewriteGenerator()
    {
        return $this->getObjectManager()->create('\Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::INPUT_PRODUCT,
                'p',
                InputOption::VALUE_OPTIONAL,
                'Reindex a specific product'
            ),
            new InputOption(
                self::INPUT_STORE,
                's',
                InputOption::VALUE_OPTIONAL,
                'Reindex a specific store',
                \Magento\Store\Model\Store::DEFAULT_STORE_ID
            )
        ];
        $this->setName('urlrewrite:rebuild:products');
        $this->setDescription('Rebuild Product URL Rewrites');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        try {

            $productIds = $input->getOption(self::INPUT_PRODUCT);
            $storeId = $input->getOption(self::INPUT_STORE);
            $this->getProductCollection()
                ->addStoreFilter($storeId)
                ->setStoreId($storeId);

            $this->addFilterProductIds($productIds);


            $size = $this->getProductCollection()->getSize();
            if (!$size) {
                $this->output->write('', true);
                $this->output->write('Nothing to process', true);
                return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
            }

            $this->progressBar->start($size);

            $this->getIterator()->walk(
                $this->collection->getSelect(),
                [[$this, 'callbackGenerateProductUrl']],
                [
                    'product' => $this->getProductFactory(),
                    'storeId' => $storeId
                ]
            );
            $this->progressBar->finish();
            $this->output->write('', true);
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductCollection()
    {
        if (is_null($this->collection)) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
            $this->collection = $this->getObjectManager()->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')->create();
        }
        return $this->collection;
    }

    private function addFilterProductIds($productIds = null)
    {
        if (is_null($productIds) || is_null($this->collection)) {
            return;
        }
        $this->collection->addIdFilter(explode(',', $productIds));
    }

    /**
     * Create product factory
     *
     * @return \Magento\Catalog\Model\ProductFactory
     */
    protected function getProductFactory()
    {
        return $this->getObjectManager()->create('\Magento\Catalog\Model\ProductFactory')->create();
    }
}
