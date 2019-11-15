<?php

/**
 * @category   Bronto
 * @package    Bronto_Common
 */
class Bronto_Common_Model_System_Config_Source_Message
{
    /**
     * @var array
     */
    protected $_options = array();

    /**
     * Get Messages as Array of Labels and Values for Select Fields
     *
     * @param null $token
     *
     * @return array
     */
    public function toOptionArray($token = null)
    {
        if (!empty($this->_options)) {
            return $this->_options;
        }

        try {
            if ($api = Mage::helper('bronto_common')->getApi($token)) {
                /* @var $messageObject Bronto_Api_Message */
                $messageObject = $api->getMessageObject();
                foreach ($messageObject->readAll()->iterate() as $message/* @var $message Bronto_Api_Message_Row */) {
                    $_option = array(
                        'label' => $message->name,
                        'value' => $message->id,
                    );

                    if ($message->status != 'active') {
                        $_option['disabled'] = true;
                    }

                    $this->_options[] = $_option;
                }
            }
        } catch (Exception $e) {
            Mage::helper('bronto_common')->writeError($e);
        }

        array_unshift($this->_options, array(
            'label' => '-- None Selected --',
            'value' => '',
        ));

        return $this->_options;
    }
}
