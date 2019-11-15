<?php
class Collinsharper_Reelbuilder_Helper_Tools extends Mage_Core_Helper_Abstract
{
    // TODO make these admin configurable?
    protected $_font_colors = array(
        "white" => "White",
        "black" => "Black",
        "tan" => "Tan",
        "brown" => "Brown",
        "red" => "Red",
        "orange" => "Orange",
        "yellow" => "Yellow",
        "fuschia" => "Fuschia",
        "purple" => "Purple",
        "darkpurple" => "Dark Purple",
        "navy" => "Navy",
        "blue" => "Blue",
        "green" => "Green",
        "kelly" => "Kelly",
    );
    protected $_font_html = array(
        "white" => "#ffffff",
        "black" => "#000000",
        "tan" => "#d8cfc3",
        "brown" => "#8d4425",
        "red" => "#f24048",
        "orange" => "#f6a24a",
        "yellow" => "#f2eb5d",
        "fuschia" => "#d08aba",
        "purple" => "#814696",
        "darkpurple" => "#802a7a",
        "navy" => "#2b2e78",
        "blue" => "#3b549f",
        "green" => "#2d8148",
        "kelly" => "#4bb957",
    );

    protected $_fonts = array(
        "Fontdiner Swanky" => "Fontdiner Swanky",
        'Architects Daughter' => 'Architects Daughter',
        'Cousine' => 'Cousine',
        'Playfair Display' => 'Playfair Display',
        'Roboto' => 'Roboto',
        'Coda Caption' => 'Coda Caption',

        'Architects Daughter' => 'Architects Daughter',
        'Cousine' => 'Cousine',
        'Playfair Display' => 'Playfair Display',
        'Roboto' => 'Roboto',
        'Coda Caption' => 'Coda Caption',

        'Allerta' => 'Allerta',

        //'Great Vibes' => 'Great Vibes',
        'Marck Script' => 'Marck Script',
        "times" => "Times New Roman",
        'arial' => 'Arial',
    );

    protected $_no_fonts = array(
        "Fontdiner Swanky" => "Fontdiner Swanky",
        "arial_black" => "Arial Black",
        "arial" => "Arial",
        "comic" => "Comic Sans MS",
        "courier" => "Courier New",
        "georgia" => "Georgia",
        "helvetica" => "Helvetica",
        "impact" => "Impact",
        "lucida" => "Lucida Console",
        "palatino" => "Palatino",
        "tahoma" => "Tahoma",
        "times" => "Times New Roman",
        "trebuchet" => "Trebuchet MS",
        "verdana" => "Verdana",
        "symbol" => "Symbol",
        "webdings" => "Webdings",
        "kunstler" => "Kunstler Script",
        "freestyle" => "Freestyle Script",
    );

    public function getFonts()
    {
        return $this->_fonts;
    }

    public function getFontColors()
    {
        return $this->_font_colors;
    }

    public function getFontHtml($x)
    {
        return isset($this->_font_html[$x]) ? $this->_font_html[$x] : '';
    }


    public function getScaleCMS()
    {
	$text = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('mobile_scale_howto')->toHtml();
	return $text;
    }

    public function getRotateCMS()
    {
	$text = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('mobile_rotate_howto')->toHtml();
        return $text;
    }

    public function getBetaScrollCMS()
    {
	$text = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('mobile_beta_scroll')->toHtml();
        return $text;
    }

}
