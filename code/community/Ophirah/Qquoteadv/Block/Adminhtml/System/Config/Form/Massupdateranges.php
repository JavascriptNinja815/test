<?php

class Ophirah_Qquoteadv_Block_Adminhtml_System_Config_Form_Massupdateranges extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $id = $element->getHtmlId();

        $html = '<td class="label"><label for="' . $id . '">' . $element->getLabel() . '</label></td>';

        //$isDefault = !$this->getRequest()->getParam('website') && !$this->getRequest()->getParam('store');
        $isMultiple = $element->getExtType() === 'multiple';

        // replace [value] with [inherit]
        $namePrefix = preg_replace('#\[value\](\[\])?$#', '', $element->getName());

        $options = $element->getValues();

        $addInheritCheckbox = false;
        if ($element->getCanUseWebsiteValue()) {
            $addInheritCheckbox = true;
            $checkboxLabel = Mage::helper('adminhtml')->__('Use Website');
        } elseif ($element->getCanUseDefaultValue()) {
            $addInheritCheckbox = true;
            $checkboxLabel = Mage::helper('adminhtml')->__('Use Default');
        }

        if ($addInheritCheckbox) {
            $inherit = $element->getInherit() == 1 ? 'checked="checked"' : '';
            if ($inherit) {
                $element->setDisabled(true);
            }
        }
        if (!$this->versionCheck()) {
            $element->setTooltip(null);
        }

        if ($element->getTooltip()) {
            $html .= '<td class="value with-tooltip">';
            $html .= $this->_getElementHtml($element);
            $html .= '<div class="field-tooltip"><div>' . $element->getTooltip() . '</div></div>';
        } else {
            $html .= '<td class="value">';
            $html .= $this->_getElementHtml($element);
        };

        /*
        * ADDED mass update button to system config page
        * */
        $html .= '<button id="mass_update_button" type="button" url="' . Mage::helper("adminhtml")->getUrl("adminhtml/qquoteadv/massupdate") . '">Update</button>';

        if ($element->getComment()) {
            $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
        }
        $html .= '</td>';


        if ($addInheritCheckbox) {

            $defText = $element->getDefaultValue();
            if ($options) {
                $defTextArr = array();
                foreach ($options as $k => $v) {
                    if ($isMultiple) {
                        if (is_array($v['value']) && in_array($k, $v['value'])) {
                            $defTextArr[] = $v['label'];
                        }
                    } elseif ($v['value'] == $defText) {
                        $defTextArr[] = $v['label'];
                        break;
                    }
                }
                $defText = join(', ', $defTextArr);
            }

            // default value
            $html .= '<td class="use-default">';
            $html .= '<input id="' . $id . '_inherit" name="'
                . $namePrefix . '[inherit]" type="checkbox" value="1" class="checkbox config-inherit" '
                . $inherit . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" /> ';
            $html .= '<label for="' . $id . '_inherit" class="inherit" title="'
                . htmlspecialchars($defText) . '">' . $checkboxLabel . '</label>';
            $html .= '</td>';
        }

        $html .= '<td class="scope-label">';
        if ($element->getScope()) {
            $html .= $element->getScopeLabel();
        }
        $html .= '</td>';

        $html .= '<td class="">';
        if ($element->getHint()) {
            $html .= '<div class="hint" >';
            $html .= '<div style="display: none;">' . $element->getHint() . '</div>';
            $html .= '</div>';
        }
        $html .= '</td>';

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * Decorate field row html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml($element, $html)
    {
        if (!$this->versionCheck()) {
            return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>';
        } else {
            return parent::_decorateRowHtml($element, $html);
        }
    }

    private function versionCheck()
    {
        return version_compare(Mage::getVersion(), '1.7.0.0') <= 0 ? false : true;
    }
}