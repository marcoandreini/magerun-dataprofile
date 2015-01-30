<?php

namespace Ikom;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunDataProfile extends AbstractMagentoCommand
{
    protected function configure()
    {
      	$this
          ->setName('ikom:rundataprofile')
          ->addArgument('profileId', InputArgument::OPTIONAL, 'Profile Id')
          ->setDescription('ikom dataprofiles')
      	;
    }

   /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$logfile  = 'export_data.log';      // Import/Export log file
		$table = 'dataflow_batch_export';

		$this->detectMagento($output);
      	if ($this->initMagento()) {
      		$dialog = $this->getHelperSet()->get('dialog');

      		$profileId = $input->getArgument('profileId');
            if ($profileId == null) {
                $profileId = $dialog->ask($output, '<question>Profile Id:</question>');
            }

      		// \Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
      		$profile = $this->_getModel('dataflow/profile');
      		$userModel = $this->_getModel('admin/user');
      		$userModel->setUserId(0);
      		\Mage::getSingleton('admin/session')->setUser($userModel);
      		$profile->load($profileId);
      		if (!$profile->getId()) {
      			\Mage::getSingleton('adminhtml/session')->addError('ERROR: Incorrect profile id');
      			$output->writeln("Error, profile not found: ". $profileId);
      			return;
      		}

      		\Mage::log('Export ' . $profileId . ' Started.', null, $logfile);
      		\Mage::register('current_convert_profile', $profile);
      		$profile->run();
      		$recordCount = 0;
      		$batchModel = \Mage::getSingleton('dataflow/batch');

      		\Mage::log('Export '.$profileId.' Complete. BatchID: '.$batchModel->getId(),
      				null, $logfile);

      		$output->writeln("Export Complete. BatchID: " . $batchModel->getId());
      	}
    }
}