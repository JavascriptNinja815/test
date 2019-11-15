<?php


class Collinsharper_Reels_Model_Printer
{


    protected $_overage_amounts = array(

        1 => 2,
        2 => 4,
        3 => 5,
        4 => 7,
        5 => 8,
        6 => 9,
        7 => 10,
        8 => 11,
        9 => 12,
        10 => 14,
        11 => 15,
        12 => 16,
        13 => 17,
        14 => 18,
        15 => 19,

    );

    const LOCK_FILE = 'var/locks/ch_transfer_reels';

//const FTP_PATH = 'c3d';
    const FTP_PATH = 'c3d';
    const FTP_IP = "173.12.168.149";
    const FTP_USER = 'ftpuser';
    const FTP_PASS = 'ftp1i3d';

    const TRANSFERRED_SUCCESS_STATUS = 8;
    const TRANSFERRED_FAILED_STATUS = 9;

    const MAX_PRINTABLE  = 999;


    //TODO setup cron!
    //TODO setup observer for admin predispatch and test a var/locks file to ensure the print queue is running
    function processQueueAndTransfer()
    {
        $addNotice = "";

        file_put_contents(BP . DS . self::LOCK_FILE, date('c'));

        $resource = Mage::getSingleton("core/resource");
        $write = $resource->getConnection("core_write");
        $read = $resource->getConnection("core_read");
        $table = $resource->getTablename('ch_reel_print_queue');
        $sql = "select * from {$table} where status = 0";
        $time = time();

        $todayFolder = date('Ymd', $time - 28800);

        $printableReels = $read->fetchAll($sql);
        if(count($printableReels)) {
            $connection = ftp_connect(self::FTP_IP);
            $login = ftp_login($connection, self::FTP_USER, self::FTP_PASS);

            if(!$connection || !$login) {
                $addNotice .= "Failed to connect to FTP, no reels have been transferred.";
                $this->_postAdminNotice($addNotice);
                return;
            }

            ftp_chdir($connection, self::FTP_PATH);
            @ftp_mkdir($connection, $todayFolder);
            ftp_chdir($connection, $todayFolder);

            foreach($printableReels as $queueRow) {

                $rowStatus = self::TRANSFERRED_SUCCESS_STATUS;
		$file = BP . DS . 'media' . DS . $queueRow['final_reel_filename'];

                try {
                    if(!file_exists($file)) {
                        $addNotice .= "Reel  ({$queueRow['reel_id']}) missing source reel file {$file} \n";
                        continue;
                    }

			$fp = fopen($file, 'r');
                    $upload = ftp_fput($connection,
                        $queueRow['print_filename'],
                        $fp,
                        FTP_BINARY);



                    if (!$upload) {
                        $addNotice .= "Reel  ({$queueRow['reel_id']}) Failed to transfer \n";
                        $rowStatus = self::TRANSFERRED_FAILED_STATUS;
                    }


                } catch (Exception $e) {
                    $addNotice .= "Reel ({$queueRow['reel_id']}) Failed to transfer: " . $e->getMessage() . "\n";
                        $rowStatus = self::TRANSFERRED_FAILED_STATUS;
                }
                    $sql = "update {$table} set status = {$rowStatus} where entity_id = ". $queueRow['entity_id'];

                    $write->query($sql);
		continue;

            }

            ftp_close($connection);
        }


        if($addNotice != "") {
            //$this->_postAdminNotice($addNotice);
	Mage::logException(new Exception($addNotice));
        }
    }

    public function queueReel($reel, $qty = 1, $orderId = false, $itemId = false)
    {
	//Create random values if orderId and itemId are not set
        if(!$orderId) {
	    $orderId = rand(1000, 9999);
	}
	if(!$itemId) {
	    $itemId = rand(100, 999);
	}
        // TODO Build objects and use them
        $resource = Mage::getSingleton("core/resource");
        $write = $resource->getConnection("core_write");
        $table = $resource->getTablename('ch_reel_print_queue');
        $sql = "insert into {$table} (reel_id, print_filename, final_reel_filename, qty, customer_id, order_id, order_item_id,created_at ) values (:reel_id, :print_filename, :final_reel_filename, :qty, :customer_id, :order_id, :item_id, now())";

        $fileToPrint = BP . DS. 'media'. DS . $reel->getData('final_reel_file');
        if(!file_exists($fileToPrint)) {
            $x = new Exception("Reel is not complete or missing file to print:  " . print_r($reel,1) );
            mage::logException($x);
            throw $x;
        }

        $originalQty = $overageQty = $this->getOverageQty($qty);
        $rounds = 1;
        // CPY directive for the printer is limited to 3 characters... so we cannot queue > 999 at a time
        while($overageQty > 0) {
            $thisQueueQty = self::MAX_PRINTABLE;
            if($overageQty < self::MAX_PRINTABLE) {
                $thisQueueQty = $overageQty;
            }
            $overageQty -= $thisQueueQty;

            $uniqueFile = '';

            if($originalQty > self::MAX_PRINTABLE) {
                $uniqueFile = '-L' . $rounds;
            }

            $filename = $this->getPrintingFilename($reel, $thisQueueQty, $orderId, (string)$itemId . $uniqueFile);
            $bound =  array(
                'reel_id' => $reel->getId(),
                'print_filename' => $filename,
                'final_reel_filename' => $reel->getData('final_reel_file'),
                'qty' => $thisQueueQty,
                'customer_id' => $reel->getCustomerId(),
                'order_id' => $orderId,
                'item_id' => $itemId
            );

            mage::log(__METHOD__ . __LINE__ . " we jhave $sql " . print_r($bound ,1));
            $write->query($sql, $bound);
            $rounds++;
        }


    }


    public function getPrintingFilename($reel, $qty, $orderId, $itemId)
    {
        if(!$itemId) {
            $itemId = rand(9,9999);
        }
	$qty = ceil($qty);
        $cleanReelName = preg_replace('/CPY\d{3}/i', '',  $reel->getReelName());
        $cleanReelName = preg_replace('/[^a-z0-9\-\_]+/i', '-', $cleanReelName);
        $cleanReelName = str_replace('--', '-', $cleanReelName);
        $copyDirective = "CPY" . str_pad($qty,3,'0',STR_PAD_LEFT);
        $reelId = str_pad( $reel->getId(), 8, '0', STR_PAD_LEFT);
        $customerId = str_pad( $reel->getCustomerId(), 8, '0', STR_PAD_LEFT);
        $orderId = str_pad($orderId, 8, '0', STR_PAD_LEFT);
        $itemId = str_pad($itemId, 6, '0', STR_PAD_LEFT);
        return str_replace('--', '-', "{$orderId}-{$itemId}-{$customerId}-{$reelId}_{$cleanReelName}_{$copyDirective}.jpeg");
    }

    public function getOverageQty($qty)
    {
        $overageQty = $qty;
        if($qty <= key( array_slice( $this->_overage_amounts, -1, 1, TRUE ) )) {
            $overageQty = $this->_overage_amounts[$qty];
        } else if($qty < 100) {
            $overageQty *= 1.25;
        } else if($qty < 200) {
            $overageQty *= 1.18;
        } else if($qty < 300) {
            $overageQty *= 1.12;
        } else {
            $overageQty *= 1.1;
        }
        return ceil($overageQty);
    }


    function _postAdminNotice($addNotice)
    {
        Mage::getModel('adminnotification/inbox')
            ->setSeverity(4)
            ->setTitle('Some reels failed to transfer for printing, please check the image transfer queue')
            ->setDescription('Details: ' . $addNotice)
            ->save();
    }


}
