<?php

namespace Ikom;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CmsExport extends AbstractMagentoCommand
{
    protected function configure()
    {
      	$this
          ->setName('ikom:cms:export')
          ->addArgument('urlkey', InputArgument::REQUIRED, 'Url key')
          ->addArgument('file', InputArgument::REQUIRED, 'Filename')
          ->addArgument('storeid', InputArgument::OPTIONAL, 'Store Id')
          ->setDescription('export a cms page')
      	;
    }

   	/**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$logfile  = 'import_export.log';      // Import/Export log file

		$this->detectMagento($output);
      	if ($this->initMagento()) {
      		$dialog = $this->getHelperSet()->get('dialog');

      		$urlkey = $input->getArgument('urlkey');
      		if ($urlkey == null) {
      			$urlkey = $dialog->ask($output, '<question>Page url key:</question>');
      		}
      		$filename = $input->getArgument('file');
            if ($filename == null) {
                $filename = $dialog->ask($output, '<question>Filename:</question>');
            }
            $storeId = $input->getArgument('storeid');

            if ($storeId != null) {
            	$store = array($storeId);
            	$store[] = \Mage_Core_Model_App::ADMIN_STORE_ID;
            }

            $pageModel = \Mage::getModel('cms/page');
            $collection = $pageModel->getCollection()
            	->addFieldToFilter('identifier', $urlkey);
            if ($storeId != null) {
            	$collection = $collection
            		->addStoreFilter($storeId)
            		->addFieldToFilter('store_id', array('in' => $store));
            }
           	$page = $collection->getFirstItem();
            if ($page && $page->getPageId()) {
            	$page->load();
            	$data = json_encode($page->getData(), JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
	            file_put_contents($filename, $data);
      			\Mage::log("exported page ${urlkey}", null, $logfile);
            } else {
            	throw new \InvalidArgumentException('Could not exists: '. $urlkey.' page.');
            }

      	}
    }
}