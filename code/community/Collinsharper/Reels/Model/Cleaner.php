<?php

class Collinsharper_Reels_Model_Cleaner extends Mage_Core_Model_Abstract
{

    const DELETE_REEL_DAYS = 90;
    const DAY_SECONDS = 86400;

    var $_fake = false;

    function setFakeRun()
    {
        $this->_fake = true;
    }

    function cleanUp()
    {
        $this->cleanUpThumbs();
        $this->cleanUpAnonymousReels();
        $this->cleanUpActiveReels();
        $this->cleanUpOrphanedReelTree();
    }

    function cleanUpAnonymousReels()
    {
        $part = "uploads/anonymous/";
        $files = $this->getFileList($part);

        foreach ($files as $file) {
            $this->cleanOldFiles( $part . $file);
        }
    }


    function cleanUpThumbs()
    {
        // purge old thumbs
        $part = "uploads/users/thumb/";
        $files = $this->getFileList($part);

        foreach ($files as $file) {
            $this->cleanOldFiles($part . $file);
        }
    }

    function getBasePath()
    {
        return Mage::getBaseDir() . "/media/reel_builder";
    }

    function cleanOldFiles($file)
    {
        $basePath = $this->getBasePath(); // . DS . 'uploads/users/thumb/';
        $rmPath = $basePath . DS . $file;
        if($this->_fake) {
            $this->log(__METHOD__ . __LINE__ . ": did not purge $rmPath " );
        } else {
            $this->log(__METHOD__ . __LINE__ . ": deltree or unlink $rmPath " );

            if(!is_writable($rmPath)) {
                mage::logException(new Exception("unable to modify storage. Correct server permissions $rmPath  "));
            }
            // TODO we could also remove empty directories.

            if(!is_dir($rmPath)) {
                $file_modified = floor((time()-filemtime($rmPath))/self::DAY_SECONDS);
                if($file_modified > self::DELETE_REEL_DAYS) {
                    unlink($rmPath);
                } else {
                    $this->log(__METHOD__ . __LINE__ . ": nothign totdo $rmPath " );
                }
            }
        }
    }

    function getFileList($path)
    {
        $basePath = $this->getBasePath() . DS . $path;
        $files = array_diff(scandir($basePath), array('.','..'));
        return $files;
    }


    function cleanUpActiveReels()
    {
        $reels = $this->getRemovableIncompleteReels();
        $basePath = $this->getBasePath();

        // TODO get reels that have not been updatedin X months.
        foreach($reels as $reel) {
            if(!$reel->getCustomerId() || !$reel->getId()) {
                mage::logException(new Exception("BAD exists..." . print_r($reel->getData(),1)));
                continue;
            }

            $this->cleanReelFiles($reel->getId(), $reel->getCustomerId());

            $reel->delete();
        }
    }

    function cleanReelFiles($reelId, $customerId)
    {
        $basePath = $this->getBasePath();
        $path = "{$basePath}/uploads/users/{$customerId}/{$reelId}/";

        if(is_dir($path)) {

            $this->delTree($path);
        }

        if(is_dir($path)) {
            mage::logException(new Exception("path exists... {$path}"));
        }

	$cpath = "{$basePath}/complete/{$customerId}/";

	if(is_dir($cpath)) {
	    $final_file = "{$cpath}/{$reelId}.jpg";
	    $final_thumb = "{$cpath}/{$reelId}_thumb.jpg";
	    if(file_exists($final_file)) {
	        unlink($final_file);
	    }
	    if(file_exists($final_thumb)) {
                unlink($final_thumb);
            }
	}

        return $this;
    }

    /**
     * @param $dir
     * @param $deleteRootToo
     */
    function cleanUpOrphanedReelTree($dir, $deleteRootToo)
    {
        return;
        $deletionDays = self::DELETE_REEL_DAYS;

        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {

            $currentPath = $dir . DS . $file;
            if(!is_dir($currentPath)) {
                // Check if image is attached to a reel,if not delete it.
                $path = pathinfo($currentPath);
                // TODO have to check frame files.
                $collection = Mage::getModel('chreels/reels')->getCollection()
                    ->addFieldToFilter('final_reel_file', array('like' => '%'.$path['basename'] ))
                    ->load();
                if(count($collection) == 0) {
                    if(!is_writable($currentPath)) {
                        mage::logException(new Exception("unable to modify storage. Correct server permissions ".$file));
                    } else {
                        if(unlink($currentPath)) {
                            mage::logException(new Exception("File deleted - ".$file));
                            /* if($deleteRootToo)
                            {
                                $this->DeleteDirectory($path['dirname']);
                            } */
                        } else {
                            mage::logException(new Exception("unable to delete file. Correct server permissions ".$file));
                        }
                    }
                } else {
                    // Check if image is not modified for DELETE_REEL_DAYS.If not delete the image.
                    $file_modified = floor((time()-filemtime($file))/self::DAY_SECONDS);

                    if($file_modified > $deletionDays) {
                        if(!is_writable($currentPath)) {
                            mage::logException(new Exception("unable to modify storage. Correct server permissions ".$file));
                        } else {
                            if(unlink($currentPath)) {
                                mage::logException(new Exception("File deleted - ".$file));
                                /* if($deleteRootToo)
                                {
                                    $this->DeleteDirectory($path['dirname']);
                                } */
                            } else {
                                mage::logException(new Exception("unable to delete file. Correct server permissions ".$file));
                            }
                        }
                    }
                }
            } else {
                $this->cleanUpReelTree($currentPath, true);
            }
        }

        return;
    }

    function hasPotentialDeletableReels($customerId = false, $partialWarn = true)
    {
        if(!$customerId) {
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        }

        $reels = $this->getRemovableIncompleteReels($customerId, $partialWarn);

        return $reels->count() > 0 ? $reels->getAllIds() : false;
    }

    function getRemovableIncompleteReels($customerId = false, $partialWarn = false)
    {
        $reels = Mage::getModel('chreels/reels')->getCollection();
        //$reels->addFieldToFilter('final_reel_file',  array('null' => true));
        $reels->addFieldToFilter('status',  array('neq' =>  Collinsharper_Reels_Model_Reels::COMPLETE_STATUS));

        if($customerId) {
            $reels->addFieldToFilter('customer_id', $customerId);
        }

        $date = strtotime(Mage::getModel('core/date')->gmtDate());
        $subtract = self::DELETE_REEL_DAYS * self::DAY_SECONDS;
        if($partialWarn !== false) {
            $subtract = (self::DELETE_REEL_DAYS/$partialWarn) * self::DAY_SECONDS;
        }
        $date -= $subtract;

        $reels->addfieldtofilter('updated_at',
                array('lteq' => date("Y-m-d H:i:s", $date))
            );

        $this->log(__METHOD__ . __LINE__ . " we have sel" . $reels->getSelect()->__toString() );
        return $reels;
    }

    public function delTree($dir) {

        $files = array_diff(scandir($dir), array('.','..'));

        $this->log(__METHOD__ . __LINE__ . ": did not purge $dir " . print_r($files,1) );

        foreach ($files as $file) {
            if($this->_fake) {
                $this->log(__METHOD__ . __LINE__ . ": did not purge $dir/$file " );
            } else {
                $this->log(__METHOD__ . __LINE__ . ": deltree or unlink " . "$dir/$file" );

                if(!is_writable("$dir/$file")) {
                    throw new Exception("unable to modify storage. Correct server permissions");
                }

                $return = (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
            }
        }

        if($this->_fake) {
            $this->log(__METHOD__ . __LINE__ . ": did not purge $dir " );
            return true;
        }
        return rmdir($dir);
    }

    public function log($_message, $_level = null)
    {
        Mage::log($_message, $_level, "ch_reels.log");
    }

}
