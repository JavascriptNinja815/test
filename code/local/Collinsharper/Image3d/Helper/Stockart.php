<?php

class Collinsharper_Image3d_Helper_Stockart extends Mage_Core_Helper_Data
{

    const STOCK_ART_PATH = 'wysiwyg/RBStockArt';

    protected $tree = false;
    protected $_image_helper = false;
    protected $_keyed_tree = false;
    protected $_file_skip_list = array(".", "..", "Archive", "thumb.jpg", "thumb.png");
    protected $_allowed_file_types = array('png');
    const CACHE_KEY = 'CH_RB_STOCK_ART';
    const CACHE_KEY_MOBILE = 'CH_RB_STOCK_ART_MOBILE';

    public function getStockArt($asJson = true)
    {
        $cache = Mage::app()->getCache();

        $tree = $cache->load(self::CACHE_KEY);
        if(!$tree) {
            //$path = $this->ImageHelper()->getMediaDir() . DS . self::STOCK_ART_PATH . DS;
            $path =  self::STOCK_ART_PATH;
            if(!file_exists($path)) {
                if (!mkdir($path, 0775, true)) {
                    throw new EXception("Please contact support 00055");
                }
            }
            $tree = array();
            $tree['children'] = $this->dirToArray();
            $tree = json_encode($tree);
            $cache->save($tree, self::CACHE_KEY, array("ch_rb_cache", \Mage_Core_Model_Config::CACHE_TAG), 24*60*60);
        }

        return $asJson ? $tree : json_decode($tree);
    }

    /**
     * @return \Collinsharper_Image3d_Helper_Image
     */
    function ImageHelper()
    {
        if(!$this->_image_helper) {
            $this->_image_helper = mage::helper('chimage3d/image');
        }
        return $this->_image_helper;
    }


    public 	function dirToArray($dir = false) {

        if(!$dir) {
            $dir = $this->ImageHelper()->getMediaDir() . DS . self::STOCK_ART_PATH;
        }

        $result = array();

        $cdir = scandir($dir);

        foreach ($cdir as $key => $value) {
            if (!in_array($value, $this->_file_skip_list)) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result[$value] = array(
                        'is_dir' => 1,
                        'thumb' => '',
                        'path' => str_replace($this->ImageHelper()->getMediaDir().DS, '', $dir) . DIRECTORY_SEPARATOR .$value,
                        'children' => $this->dirToArray($dir . DIRECTORY_SEPARATOR . $value)
                    );

                    $replacementThumb = false;
                    if(file_exists($dir  . DS . $value . DS . 'thumb.png')) {
                        $replacementThumb = 'thumb.png';
                    } else if(file_exists($dir  . DS . $value . DS . 'thumb.jpg')) {
                        $replacementThumb = 'thumb.jpg';
                    } else {
                        $h = opendir($dir  . DS . $value . DS); //Open the current directory
                        while (false !== ($entry = readdir($h))) {
                            if (!in_array($entry, $this->_file_skip_list)) {
                                $replacementThumb = $entry;
                                break;
                            }
                        }
                    }
                    if($replacementThumb) {
                        $result[$value]['thumb'] = $this->ImageHelper()->getThumb( str_replace($this->ImageHelper()->getMediaDir() . DS, '', $dir) . DIRECTORY_SEPARATOR .$value . DS . $replacementThumb);
                    }

                } else if (preg_match('/\.(jpg|png|jpeg)$/i', $value)) {
                    $result[$value] = array(
                        'is_dir' => 0,
                        'thumb' => $this->ImageHelper()->getThumb( str_replace($this->ImageHelper()->getMediaDir().DS, '', $dir) . DIRECTORY_SEPARATOR .$value ),
                        'path' => str_replace($this->ImageHelper()->getMediaDir().DS, '', $dir) . DIRECTORY_SEPARATOR .$value,
                    );
                }
            }
        }
        return $result;
    }

    // DEPRECATED
    public function getStockImageTreeLevel($path = false)
    {
        return $this->dirToArray();
    }


    /**
     * Get Stock Images for Mobile
     * @param bool
     * @return false|mixed|string
     * @throws Zend_Cache_Exception
     */
    public function getMobileStockArt($asJson = true)
    {
        $cache = Mage::app()->getCache();

        $tree = $cache->load(self::CACHE_KEY_MOBILE);
        if(!$tree) {
            //$path = $this->ImageHelper()->getMediaDir() . DS . self::STOCK_ART_PATH . DS;
            $path =  self::STOCK_ART_PATH;
            if(!file_exists($path)) {
                if (!mkdir($path, 0775, true)) {
                    throw new EXception("Please contact support 00055");
                }
            }
            $tree = array();
            $tree['children'] = $this->mobileDirToArray();
            $tree = json_encode($tree);
            $cache->save($tree, self::CACHE_KEY_MOBILE, array("ch_rb_cache", \Mage_Core_Model_Config::CACHE_TAG), 24*60*60);
        }

        return $asJson ? $tree : json_decode($tree);
    }


    public function mobileDirToArray($dir = false) {

        if(!$dir) {
            $dir = $this->ImageHelper()->getMediaDir() . DS . self::STOCK_ART_PATH;
        }

        $result = array();

        $cdir = scandir($dir);

        foreach ($cdir as $key => $value) {
            if (!in_array($value, $this->_file_skip_list)) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {

                    $replacementThumb = false;
                    if(file_exists($dir  . DS . $value . DS . 'thumb.png')) {
                        $replacementThumb = 'thumb.png';
                    } else if(file_exists($dir  . DS . $value . DS . 'thumb.jpg')) {
                        $replacementThumb = 'thumb.jpg';
                    } else {
                        $h = opendir($dir  . DS . $value . DS); //Open the current directory
                        while (false !== ($entry = readdir($h))) {
                            if (!in_array($entry, $this->_file_skip_list)) {
                                $replacementThumb = $entry;
                                break;
                            }
                        }
                    }

                    if ($replacementThumb) {
                        $result[] = array(
                            'is_dir' => 1,
                            'thumb' => $this->ImageHelper()->getThumb( str_replace($this->ImageHelper()->getMediaDir() . DS, '', $dir) . DIRECTORY_SEPARATOR .$value . DS . $replacementThumb),
                            'path' => str_replace($this->ImageHelper()->getMediaDir().DS, '', $dir) . DIRECTORY_SEPARATOR .$value,
                            'dir_name' => $value,
                            'children' => $this->mobileDirToArray($dir . DIRECTORY_SEPARATOR . $value)
                        );
                    } else {
                        $result[] = array(
                            'is_dir' => 1,
                            'thumb' => '',
                            'path' => str_replace($this->ImageHelper()->getMediaDir().DS, '', $dir) . DIRECTORY_SEPARATOR .$value,
                            'dir_name' => $value,
                            'children' => $this->mobileDirToArray($dir . DIRECTORY_SEPARATOR . $value)
                        );
                    }

                } else if (preg_match('/\.(jpg|png|jpeg)$/i', $value)) {
                    $result[] = array(
                        'is_dir' => 0,
                        'thumb' => $this->ImageHelper()->getThumb( str_replace($this->ImageHelper()->getMediaDir().DS, '', $dir) . DIRECTORY_SEPARATOR .$value ),
                        'path' => str_replace($this->ImageHelper()->getMediaDir().DS, '', $dir) . DIRECTORY_SEPARATOR .$value,
                    );
                }
            }
        }
        return $result;
    }

}
