<?php


class Collinsharper_Reels_Model_Verify
{

    const FILE_GENERATION_LOCK = 'var/locks/ch_frame_generation.lock';



    function verifyReelTransfersOrNotify()
    {
        $maxAge = 90 * 60;

        $lastChecked = (int)mage::getSingleton('adminhtml/session')->getReelTested();
        if(!$lastChecked || (time() - $lastChecked > $maxAge)) {
            $file = BP . DS . Collinsharper_Reels_Model_Printer::LOCK_FILE;
            clearstatcache (true, $file);

            // one for printing
            $filemtime = @filemtime($file);

            if(!$filemtime || (time() - $filemtime > $maxAge)) {
                Mage::getModel('adminnotification/inbox')
                    ->setSeverity(4)
                    ->setTitle('Reel Transfer is not running!')
                    ->setDescription('The lock file to indicate the reel transfer has run is missing or > 90 minutes old. Please contact Collins Harper for support.')
                    ->save();
            }

            $maxAge = 5 * 60;
            $file = BP . DS . self::FILE_GENERATION_LOCK;
            clearstatcache (true, $file);

            $filemtime = @filemtime($file);

            // one for frame geenration
            if(!$filemtime || (time() - $filemtime > $maxAge)) {
                Mage::getModel('adminnotification/inbox')
                    ->setSeverity(4)
                    ->setTitle('Frame Generation is not be running!')
                    ->setDescription('The frmae generation service has stopped. Please contact Collins Harper ASAP.')
                    ->save();
            }


        }
        mage::getSingleton('adminhtml/session')->setReelTested(time());
    }


}