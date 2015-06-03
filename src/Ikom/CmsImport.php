<?php

namespace Ikom;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CmsImport extends AbstractMagentoCommand
{
    protected function configure()
    {
      	$this
          ->setName('ikom:cms:import')
          ->addArgument('urlkey', InputArgument::REQUIRED, 'Url key')
          ->addArgument('file', InputArgument::REQUIRED, 'Filename')
          ->addArgument('storeid', InputArgument::OPTIONAL, 'Store Id')
          ->setDescription('import a cms page')
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

            // read json file:
            $data = json_decode(file_get_contents($filename), TRUE);
            if (0 !== json_last_error()) {
            	throw new \InvalidArgumentException('Could not parse JSON file: '. $filename .'. '.json_last_error_msg());
            }
            $onlyKeys = array_flip(array('title', 'content', 'identifier'));
            $data = array_intersect_key($data, $onlyKeys);

            $store = array($storeId);
            $store[] = \Mage_Core_Model_App::ADMIN_STORE_ID;

            $pageModel = \Mage::getModel('cms/page');
            $collection = $pageModel->getCollection()
            	->addFieldToFilter('identifier', $urlkey)
            	->addStoreFilter($storeId)
            	->addFieldToFilter('store_id', array('in' => $store));

            $page = $collection->getFirstItem();
            if ($page && $page->getPageId()) {
            	$page->load();
            	$prevData = $page->getData();
            	$data = array_merge($prevData, $data);
            	$page->setData($data)->save();
            	\Mage::log("updated ${urlkey} page", null, $logfile);
            } else {
            	$pageModel->setData($data)->save();
            	\Mage::log("created a new ${urlkey} page", null, $logfile);
            }
      		\Mage::log("successfully imported page ${urlkey}", null, $logfile);
      	}
    }
}