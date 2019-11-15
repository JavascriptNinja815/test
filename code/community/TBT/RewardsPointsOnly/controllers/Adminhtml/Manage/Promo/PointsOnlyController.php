<?php

include_once(Mage::getModuleDir('controllers', 'TBT_Rewards').DS.'Admin'.DS.'AbstractController.php');

class TBT_RewardsPointsOnly_Adminhtml_Manage_Promo_PointsOnlyController
    extends TBT_Rewards_Admin_AbstractController
{
    /**
     * Init Menu And Breadcrum
     * @return \TBT_RewardsPointsOnly_Adminhtml_Manage_Promo_PointsOnlyController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('rewards/rules/catalog')->_addBreadcrumb(
                Mage::helper ( 'rewards' )->__ ( 'Points Only Rules'),
                Mage::helper ( 'rewards' )->__ ( 'Points Only Rule' )
            );

        return $this;
    }

    /**
     * Index Action
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * New Action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Edit Action
     * @return
     */
    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');

		$model = Mage::getModel('rewardspointsonly/rule');

		if ($id) {
			$model->load($id);

			if (!$model->getRuleId()) {
				Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('rewardspointsonly')->__('This rule no longer exists'));
                
				$this->_redirect('*/*');
				return;
			}
		}

		$data = Mage::getSingleton('adminhtml/session')->getPageData(true);

		if (!empty($data)) {
			$model->addData($data);
		}

		$model->getConditions()->setJsFormObject('rule_conditions_fieldset');

		Mage::register('current_promo_pointsonly_rule', $model);

        $this->loadLayout();

		$this->_initAction();
		$this->_addBreadcrumb(
            $id ? Mage::helper('rewardspointsonly')->__('Edit Rule') : Mage::helper('rewardspointsonly')->__('New Rule'),
            $id ? Mage::helper('rewardspointsonly')->__('Edit Rule') : Mage::helper('rewardspointsonly')->__('New Rule')
        );
        
        $this->renderLayout();
    }

    /**
     * Save Action
     * @return \TBT_RewardsPointsOnly_Adminhtml_Manage_Promo_PointsOnlyController
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('rewardspointsonly/rule');
            $data['conditions'] = $data['rule']['conditions'];
            unset($data['rule']);

            if (!empty($data['auto_apply'])) {
                $autoApply = true;
                unset($data['auto_apply']);
            } else {
                $autoApply = false;
            }

            if (Mage::helper('rewards')->isBaseMageVersionAtLeast('1.4.0.0')) {
                $data = $this->_filterDates($data, array('from_date', 'to_date'));
                $validateResult = $model->validateData(new Varien_Object($data));

                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->_getSession()->addError($errorMessage);
                    }
                    $this->_getSession()->setPageData($data);
                    $this->_redirect( '*/*/', array('type' => $model->getRuleTypeId(), 'id' => $model->getId()));
                    return;
                }
            }

            $model->loadPost($data);
            Mage::getSingleton('adminhtml/session')->setPageData($model->getData());

            try {
                $model->save();

                Mage::helper('rewardspointsonly')->validatePointsOnlyRuleAdminSettings($model);

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('catalogrule' )->__('Rule was successfully saved')
                );
                Mage::getSingleton ( 'adminhtml/session' )->setPageData ( false );

                if ($autoApply) {
                    $this->_forward('applyRules', null, null, array('id' => $model->getId()));
                } else {
                    $this->_redirect( '*/*/', array ('id' => $model->getId()));
                }

                return $this;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setPageData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('rule_id')));

                return $this;
            }
        }
        $this->_redirect('*/*/');

        return $this;
    }

    /**
     * Delete Action
     * @return
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('rewardspointsonly/rule');
                $model->load($id);

                if (!$model->getId()) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalogrule')->__('The rule you are trying to delete no longer exists'));
                    Mage::getSingleton('adminhtml/session')->setPageData($data);
                    $this->_redirect('*/*/edit');
                    return;
                }
                $id = $model->getId();

                $model->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalogrule')->__('Rule was successfully deleted'));
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }

        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalogrule')->__('Unable to find a page to delete'));
        $this->_redirect('*/*/');
    }

    /**
     * Reload Conditions Html Action
     */
    public function newConditionHtmlAction()
    {
		$id = $this->getRequest ()->getParam('id');
		$typeArr = explode ('|', str_replace ('-', '/', (string)$this->getRequest()->getParam('type')));
		$type = $typeArr[0];

		$model = Mage::getModel( $type )->setId( $id )->setType($type)->setRule(Mage::getModel('catalogrule/rule'))->setPrefix('conditions');
		if (!empty($typeArr[1])) {
			$model->setAttribute($typeArr[1]);
		}

		if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
			$model->setJsFormObject($this->getRequest()->getParam('form'));
			$html = $model->asHtmlRecursive();
		} else {
			$html = '';
		}
		$this->getResponse()->setBody($html);
	}

    /**
     * Chooser Action
     */
	public function chooserAction()
    {
		switch ($this->getRequest()->getParam('attribute')) {
			case 'sku':
				$type = 'adminhtml/promo_widget_chooser_sku';
				break;

			case 'categories':
				$type = 'adminhtml/promo_widget_chooser_categories';
				break;
		}
		if (!empty($type)) {
			$block = $this->getLayout()->createBlock($type);
			if ($block) {
				$this->getResponse()->setBody($block->toHtml());
			}
		}
	}
}
