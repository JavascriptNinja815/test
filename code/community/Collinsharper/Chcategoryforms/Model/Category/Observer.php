<?php


class Collinsharper_Chcategoryforms_Model_Category_Observer {

    public function getParentCategory($category)
    {
        $path = explode("/",$category->getPath());
        $pCategory = false;

        if($path && isset($path[1])) {
            $pCategory = Mage::getModel('catalog/category')->load($path[1]);
        }
        return $pCategory;
    }

    public function isCategoryForm($category)
    {
        $isParentAForm = false;
        if($pCategory = $this->getParentCategory($category)) {
            $isParentAForm = $pCategory->getCategoryForm();
        }
        return $category->getCategoryForm() || $isParentAForm;
    }

    public function getHelp()
    {
        return Mage::helper('collinsharper_chcategoryforms');
    }

    public function addCategoryTab($observer)
    {
        $tabs = $observer->getEvent()->getTabs();
        mage::log(__METHOD__ . __LINE__);

        if(!$this->isCategoryForm($tabs->getCategory())) {
            $content = 'Please update parent category to be a form category';
        } else {
            // TODO
            // get all options for this level so we can denote wich are already used

            $currentOptions = explode(",", $tabs->getCategory()->getData('category_form_bundle_options'));
            $currentData = unserialize($tabs->getCategory()->getData('category_form_data'));
            $currentProdOptions = isset($currentData['form_product_options']) ? explode(",", $currentData['form_product_options']) : array();
            $parentCategory = $this->getParentCategory($tabs->getCategory());

            $product = false;
            $category = $this->getParentCategory($tabs->getCategory());
            if($category) {
                mage::log(__METHOD__ . __LINE__ );
                $product = $category->getProductCollection()->getFirstItem();
                $product = Mage::getModel('catalog/product')->load($product->getId());

                $opts = $this->getHelp()->getOptions($product);
                $customOptions = $product->getOptions();


            }
//$currentProdOptions
            $content = '';

            if($customOptions) {

                //mage::log(__METHOD__  . __LINE__ . " we have OPT " . print_r($product->getOptions(),1));
                mage::log(__METHOD__  . __LINE__ . " we have OPT " );

                $content .= '<label for="unique_identifier">Unique Id for this step / elements</label><input type="text" name="form_step_unique_id" id="unique_identifier" value="'.                          (isset($currentData['form_step_unique_id']) ? $currentData['form_step_unique_id'] : '')
                .'"> ';

                $content .= ' <br /> <hr>';

                $content .= '<label for="custom_options">Custom Options on this step</label><select name="form_product_options[]" id="custom_options" multiple size="10">';

                foreach($customOptions as $option) {
                    $selected = '';
                    if(in_array($option->getId(), $currentProdOptions)) {
                        $selected = " selected ";
                    }
                    //  mage::log(__METHOD__ . __LINE__ . " "  . $option->position);

                    $content .= '<option value=' . $option->getId() . $selected . '>' . $option->getTitle() . '</option>';
                }

                $content .= '</select> ';
                $content .= ' <br /> <hr>';

            }


            $content .= '<label for="bundle_options">Bundle Options on this step</label><select name="form_bundle_options[]" id="bundle_options" multiple size="10">';

            foreach($opts as $option) {
                $selected = '';
                if(in_array($option->getOptionId(), $currentOptions)) {
                    $selected = " selected ";
                }
              //  mage::log(__METHOD__ . __LINE__ . " "  . $option->position);

                $content .= '<option value=' . $option->getOptionId() . $selected . '>' . $option->getDefaultTitle() . '</option>';
            }

            $content .= '</select> ';
            $content .= ' <br /> <hr>';
            $content .= '<label for="step_js">Js to execute on loading of this step</label><textarea id="step_js" name="form_step_js" cols="50" rows="12">';

            $content .= (isset($currentData['form_step_js']) ? $currentData['form_step_js'] : '');
            $content .= '</textarea>';
            $content .= ' <br /> <hr>';

            $content .= '<label for="stepout_js">Js to execute on leaving step</label><textarea id="stepout_js" name="form_stepout_js" cols="50" rows="12">';

            $content .= (isset($currentData['form_stepout_js']) ? $currentData['form_stepout_js'] : '');
            $content .= '</textarea>';
            $content .= ' <br /> <hr>';




            $content .= '<label for="step_buttons">Button code to override default back continue skip buttons</label><textarea id="button_code" name="form_button_code" cols="50" rows="12">';
            $content .= (isset($currentData['form_button_code']) ? $currentData['form_button_code'] : '');
            $content .= '</textarea>';

            mage::log(__METHOD__ . __LINE__ . " CURR DATA " .  print_r($currentData,1));

        }


        $tabs->addTab('category_forms', array(
            'label'     => Mage::helper('catalog')->__('Form Tab Data'),
            'content'   => $content
        ));

        return $this;
    }

    public function categorySave($observer)
    {
        $category = $observer->getEvent()->getCategory();
        if($this->isCategoryForm($category)) {
            $data = array();
            $data['form_product_options'] = Mage::app()->getRequest()->getParam('form_product_options', false);
            $data['form_step_unique_id'] = Mage::app()->getRequest()->getParam('form_step_unique_id', false);

            if(is_array($data['form_product_options'])) {
                $data['form_product_options'] = implode(",", $data['form_product_options']);
            }

            $data['form_step_js'] = Mage::app()->getRequest()->getParam('form_step_js', false);
            $data['form_stepout_js'] = Mage::app()->getRequest()->getParam('form_stepout_js', false);
            $data['form_button_code'] = Mage::app()->getRequest()->getParam('form_button_code', false);
            $category->setData('category_form_data', serialize($data));

            $data = Mage::app()->getRequest()->getParam('form_bundle_options', false);
            mage::log(__METHOD__ . __LINE__ . print_r($data,1));
            $saveData = '';
            if(is_array($data)) {
                $saveData = implode(",", $data);
            }

            $category->setData('category_form_bundle_options', $saveData);

        } else {
            mage::log(__METHOD__ . __LINE__ . "not category form ");
        }

        return $this;
    }
}