<?php

class TBT_Rewards_Block_Adminhtml_Points_Import extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_addButtonLabel = 'New Import';
	
    public function __construct()
    {
        $this->_controller = 'adminhtml_points_import';
        $this->_blockGroup = 'rewards';
        $this->_headerText = Mage::helper('rewards')->__('Point Imports');
        parent::__construct();
    }
    
    /**
     * Reload the grid 5 seconds after each refresh
     * @see Mage_Adminhtml_Block_Template::_toHtml()
     */
    protected function _toHtml()
    {
    	$gridId = $this->getLayout()->getBlockSingleton('rewards/adminhtml_points_import_grid')->getId();
    	$jsObject = $gridId . "JsObject";
    	$html = parent::_toHtml();
    	$html .= "
            <br/>
            <p style='color: gray;'>".
                Mage::helper('rewards')->__('This list will automatically refresh every 5 seconds')
            ."</p>
            <script type='text/javascript'>
                window.setInterval(function(){
                    if ({$jsObject}){
                        {$jsObject}.reload();
                    }
                }, 5000);
            </script>";
    	return $html;
    }
}