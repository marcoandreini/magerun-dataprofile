<?php

namespace Ikom;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCategories extends AbstractMagentoCommand
{
    protected function configure()
    {
      	$this
          ->setName('ikom:exportcategories')
          ->addArgument('file', InputArgument::OPTIONAL, 'Filename to export')
          ->setDescription('ikom export categories')
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

      		$filename = $input->getArgument('file');
            if ($filename == null) {
                $filename = $dialog->ask($output, '<question>Filename:</question>');
            }

            $allCategories = $this->_getModel('catalog/category');

            $categoryTree = $allCategories->getTreeModel();
            $categoryTree->load();
            $categoryIds = $categoryTree->getCollection()->getAllIds();
            if ($categoryIds) {
            	$write = fopen($filename, 'w');
            	foreach ($categoryIds as $categoryId ) {
            		$data = array($allCategories->load($categoryId)->getName(), $categoryId);
            		fputcsv($write, $data);
            	}
            }

            fclose($write);

      		\Mage::log("exported all categories", null, $logfile);
      	}
    }
}