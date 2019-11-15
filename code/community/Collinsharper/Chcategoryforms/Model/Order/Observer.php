<?php
/**
 * Collinsharper/Chcategoryforms/Model/Order/Observer.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcategoryforms
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper Chcategoryforms Order Observer Model
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcategoryforms
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Chcategoryforms_Model_Order_Observer
{
    /**
     * Function tag import order
     *
     * @param object|null $observer Observer Object
     *
     * @return  void
     */
    public function saveOrder($observer)
    {
        Mage::log(__FILE__ . ' ' . __LINE__ . ' ', null, 'kit.log');
        $order_id = $observer->getEvent()->getOrder()->getId();
        $_order = Mage::getModel('sales/order')->load($order_id);

        // Tag the order IF there is any imprint product
        $items = $_order->getAllItems();
        $tag_imprint = false;

        foreach ($items as $itemId => $item)
        {
            if (substr($item->getSku(), 0, 7) == 'imprint') {
                $tag_imprint = true;
                break;
            } else {
                $options = $item->getProductOptions();
                $customOptions = isset($options['options']) ? $options['options'] : false;
                if(!empty($customOptions))
                {
                    foreach ($customOptions as $option)
                    {
                        $optionTitle = $option['label'];
                   //     $optionId = $option['option_id'];
                     //   $optionType = $option['type'];
                        $optionValue = $option['value'];

                        if (substr(strtolower($optionTitle), 0, 7) == 'imprint' && !strstr(strtolower($optionTitle), 'imprint color')) {
                            Mage::log(__METHOD__ . ' ' . __LINE__ . " found .. Title  $optionTitle val  $optionValue");
                            $tag_imprint = true;
                            break;
                        }
                    }
                }

                if ($tag_imprint) {
                    break;
                }
            }

        }

        //Mage::log(__FILE__ . ' ' . __LINE__ . ' ' . print_r($tag_imprint, true) . ":" . $optionValue, null, 'kit.log');
        if ($tag_imprint) {
            $data['tag_select'] = array(1);
            Mage::helper('collinsharper_chcategoryforms')->setOrderTag($data, (string) $order_id);
        }
    }
}