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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Products extends AbstractUrlRewriteCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('urlrewrite:rebuild:products');
        $this->setDescription('Rebuild Product URL Rewrites');
        parent::configure();
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input,$output);
        try {
            $productCollection = $this->getProductCollection();
            $this->progressBar->start($productCollection->getSize());

            $this->getIterator()->walk(
                $productCollection->getSelect(),
                [[$this, 'callbackGenerateProductUrl']],
                [
                    'product' => $this->getProductFactory()
                ]
            );

            $this->progressBar->finish();
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
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
     * @return UrlRewrite[]
     */
    private function prepareUrls($product)
    {
        return $this->getProductUrlRewriteGenerator()->generate($product);
    }
}
