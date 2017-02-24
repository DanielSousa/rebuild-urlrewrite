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

use Magento\Backend\App\Area\FrontNameResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractUrlRewriteCommand extends Command
{

    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;
    /**
     * @var \Magento\Framework\App\ObjectManagerFactory
     */
    private $objectManagerFactory;

    /**
     * @param \Magento\Framework\App\ObjectManagerFactory $objectManagerFactory
     * @internal param \Magento\Framework\ObjectManagerInterface $objectManager
     * @internal param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Magento\Framework\App\ObjectManagerFactory $objectManagerFactory
    )
    {
        $this->objectManagerFactory = $objectManagerFactory;
        parent::__construct();

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->setupProgress();
    }

    /**
     * Setup progress bar
     */
    private function setupProgress()
    {
        $this->progressBar = new \Symfony\Component\Console\Helper\ProgressBar($this->output);
        $this->progressBar->setFormat(
            '<info>Product ID: %message%</info> %current%/%max% [%bar%] %percent:3s%%'
        );
    }

    /**
     * Replace data with new product urls
     *
     * @param $urls
     */
    protected function replaceUrls(array $urls)
    {
        if (empty($urls)) {
            return;
        }

        $this->getUrlPersist()->replace(
            $urls
        );
    }

    /**
     * Get Url UrlPersist
     * @return \Magento\UrlRewrite\Model\UrlPersistInterface
     */
    protected function getUrlPersist()
    {
        /** @var \Magento\UrlRewrite\Model\UrlPersistInterface $urlPersist */
        return $this->getObjectManager()->get('\Magento\UrlRewrite\Model\UrlPersistInterface');
    }

    /**
     * Gets initialized object manager
     *
     * @return \Magento\Framework\ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        if (null == $this->objectManager) {
            $area = FrontNameResolver::AREA_CODE;
            $this->objectManager = $this->objectManagerFactory->create($_SERVER);
            /** @var \Magento\Framework\App\State $appState */
            $appState = $this->objectManager->get('Magento\Framework\App\State');
            $appState->setAreaCode($area);
            $configLoader = $this->objectManager->get('Magento\Framework\ObjectManager\ConfigLoaderInterface');
            $this->objectManager->configure($configLoader->load($area));
        }
        return $this->objectManager;
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Iterator
     */
    public function getIterator()
    {
        return $this->objectManager->create('\Magento\Framework\Model\ResourceModel\Iterator');
    }

}