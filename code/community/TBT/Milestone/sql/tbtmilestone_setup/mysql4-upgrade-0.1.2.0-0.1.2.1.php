<?php

	$this->startSetup();
	
	$conflictingModule = "JMT_PointsMilestone";	
	if (Mage::helper('core')->isModuleEnabled($conflictingModule)) {
		$wasSuccessful = Mage::helper('rewards/config')->disableModule($conflictingModule);
		if (!$wasSuccessful) {
			$this->createInstallNotice(
					"Please disable <b>{$conflictingModule}</b>",
					"Please disable the $conflictingModule module manually. Featurs of this module are now built directly into MageRewards.",
					"",
					Mage_AdminNotification_Model_Inbox::SEVERITY_MAJOR
			);
		} else {
			// clear cache
			$this->prepareForDb();
		}
	}
	
	

	
	$this->endSetup();
