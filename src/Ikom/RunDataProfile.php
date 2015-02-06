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
		$logfile  = 'import_export.log';      // Import/Export log file
		$table = 'dataflow_batch_export';

		$this->detectMagento($output);
      	if ($this->initMagento()) {
      		$dialog = $this->getHelperSet()->get('dialog');

      		$profileId = $input->getArgument('profileId');
            if ($profileId == null) {
                $profileId = $dialog->ask($output, '<question>Profile Id:</question>');
            }

            // \Mage::app() -> ADMIN_STORE_ID
      		\Mage::app()->setCurrentStore(0);
      		$profile = $this->_getModel('dataflow/profile', null);
      		$userModel = $this->_getModel('admin/user', null);
      		$userModel->setUserId(0);
      		\Mage::getSingleton('admin/session')->setUser($userModel);

      		\Mage::getSingleton('dataflow/batch')->delete();

      		$profile->load($profileId);
      		if (!$profile->getId()) {
      			\Mage::getSingleton('adminhtml/session')->addError('ERROR: Incorrect profile id');
      			$output->writeln("Error, profile not found: ". $profileId);
      			return;
      		}

      		$direction = ucwords($profile->getDirection());

      		\Mage::log($direction.' profile '.$profileId. ' started.', null, $logfile);
      		\Mage::register('current_convert_profile', $profile);
      		$profile->run();
      		$batchSingleton = \Mage::getSingleton('dataflow/batch');

      		$direction = ucwords($profile->getDirection());
      		$recordCount = 0;
      		if ($direction == "Import") {
	      		if ($batchSingleton->getId()) {
	      			if ($batchSingleton->getAdapter()) {
	      				\Mage::app()->setCurrentStore(\Mage::getModel('core/store')->load(0));

	      				$batchImportModel = $batchSingleton->getBatchImportModel();
	      				$importIds = $batchImportModel->getIdCollection();
	      				$batchModel = \Mage::getModel('dataflow/batch')->load($batchSingleton->getId());
	      				$adapter = \Mage::getModel($batchModel->getAdapter());
	      				$adapter->setBatchParams($batchModel->getParams());
	      				\Mage::log("Batch profile ". $batchSingleton->getId()
	      						." has ". count($importIds) ." steps");
	      				foreach ($importIds as $importId) {
	      					$recordCount++;
	      					try{
	      						$batchImportModel->load($importId);
	      						if (!$batchImportModel->getId()) {
	      							$errors[] = \Mage::helper('dataflow')->__('Skip undefined row');
	      							continue;
	      						}

	      						try {
	      							$importData = $batchImportModel->getBatchData();
	      							$adapter->saveRow($importData);
	      						} catch (Exception $e) {
	      							\Mage::log($e->getMessage(), null, $logfile);
	      							continue;
	      						}

	      						if ($recordCount % 10 == 0) {
	      							\Mage::log("Successfully processed ".
	      									$recordCount." steps", null, $logfile);
	      						}
	      					} catch(Exception $ex) {
	      						\Mage::log('Record #'.$recordCount.' - SKU = '
	      								.$importData['sku'].' - Error - '.$ex->getMessage(),
	      								null, $logfile);
	      					}
	      				}
	      				foreach ($profile->getExceptions() as $e) {
	      					\Mage::log($e->getMessage(), null, $logfile);
	      				}
	      			}
	      		}
      		}

      		\Mage::log($direction.' profile '.$profileId.
      				' complete. BatchID: '.$batchSingleton->getId(),
      				null, $logfile);

      		\Mage::getSingleton('dataflow/batch')->delete();
      	}
    }
}