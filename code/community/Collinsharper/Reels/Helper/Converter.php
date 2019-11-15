<?php

class Collinsharper_Reels_Helper_Converter extends Collinsharper_Reels_Helper_Data
{

    const COMPLETE_PATH = '/media/reel_builder/complete/';
    const FRAME_PATH = '/media/reel_builder/uploads/users/';
    const IMAGE3D_CUSTOMER_SITE_PATH_PREFIX = '/retroviewer';

    // NOTE : this has comstants in it for text, widht, heigth and img src

const  DEFAULT_FRAME = '{"objects":[{"type":"image","originX":"center","originY":"center","left":293,"top":266,"width":@IMAGE_WIDTH@,"height":@IMAGE_HEIGHT@,"fill":"rgb(0,0,0)","stroke":null,"strokeWidth":1,"strokeDashArray":null,"strokeLineCap":"butt","strokeLineJoin":"miter","strokeMiterLimit":10,"scaleX":0.5,"scaleY":0.5,"angle":0,"flipX":false,"flipY":false,"opacity":1,"shadow":null,"visible":true,"clipTo":null,"backgroundColor":"","fillRule":"nonzero","globalCompositeOperation":"source-over","src":"@RAW_IMAGE_PART@","filters":[],"crossOrigin":"","alignX":"none","alignY":"none","meetOrSlice":"meet"},{"type":"text","originX":"center","originY":"center","left":285,"top":164,"width":302.52,"height":58.99,"fill":"#ffffff","stroke":null,"strokeWidth":1,"strokeDashArray":null,"strokeLineCap":"butt","strokeLineJoin":"miter","strokeMiterLimit":10,"scaleX":1,"scaleY":1,"angle":0,"flipX":false,"flipY":false,"opacity":1,"shadow":{"color":"rgba(45,45,45,0.5)","blur":4,"offsetX":2,"offsetY":2},"visible":true,"clipTo":null,"backgroundColor":"","fillRule":"nonzero","globalCompositeOperation":"source-over","text":"@SAMPLE_TEXT@","fontSize":"45","fontWeight":"bold","fontFamily":"arial","fontStyle":"","lineHeight":1.16,"textDecoration":"","textAlign":"left","textBackgroundColor":""}],"background":"black","overlayImage":{"type":"image","originX":"center","originY":"center","left":293,"top":266,"width":586,"height":532,"fill":"rgb(0,0,0)","stroke":null,"strokeWidth":1,"strokeDashArray":null,"strokeLineCap":"butt","strokeLineJoin":"miter","strokeMiterLimit":10,"scaleX":1,"scaleY":1,"angle":0,"flipX":false,"flipY":false,"opacity":1,"shadow":null,"visible":true,"clipTo":null,"backgroundColor":"","fillRule":"nonzero","globalCompositeOperation":"source-over","src":"/retroviewer/media/reel_builder_templates/overlay.png","filters":[],"crossOrigin":"","alignX":"none","alignY":"none","meetOrSlice":"meet"}}';


    const CENTER_FRAME_CANVAS = '{"objects":[{"type":"image","originX":"center","originY":"center","left":229.5,"top":229.5,"width":@IMAGE_WIDTH@,"height":@IMAGE_HEIGHT@,"fill":"rgb(0,0,0)","stroke":null,"strokeWidth":1,"strokeDashArray":null,"strokeLineCap":"butt","strokeLineJoin":"miter","strokeMiterLimit":10,"scaleX":0.43,"scaleY":0.43,"angle":0,"flipX":false,"flipY":false,"opacity":1,"shadow":null,"visible":true,"clipTo":null,"backgroundColor":"","fillRule":"nonzero","globalCompositeOperation":"source-over","src":"@RAW_IMAGE_PART@","filters":[],"crossOrigin":"","alignX":"none","alignY":"none","meetOrSlice":"meet"},{"type":"text","originX":"center","originY":"center","left":229.5,"top":229.5,"width":302.52,"height":58.99,"fill":"#ffffff","stroke":null,"strokeWidth":1,"strokeDashArray":null,"strokeLineCap":"butt","strokeLineJoin":"miter","strokeMiterLimit":10,"scaleX":1,"scaleY":1,"angle":0,"flipX":false,"flipY":false,"opacity":1,"shadow":{"color":"rgba(45,45,45,0.5)","blur":4,"offsetX":2,"offsetY":2},"visible":true,"clipTo":null,"backgroundColor":"","fillRule":"nonzero","globalCompositeOperation":"source-over","text":"@SAMPLE_TEXT@","fontSize":"45","fontWeight":"bold","fontFamily":"arial","fontStyle":"","lineHeight":1.16,"textDecoration":"","textAlign":"left","textBackgroundColor":""}],"background":"black","overlayImage":{"type":"image","originX":"center","originY":"center","left":229.5,"top":229.5,"width":459,"height":459,"fill":"rgb(0,0,0)","stroke":null,"strokeWidth":1,"strokeDashArray":null,"strokeLineCap":"butt","strokeLineJoin":"miter","strokeMiterLimit":10,"scaleX":1,"scaleY":1,"angle":0,"flipX":false,"flipY":false,"opacity":1,"shadow":null,"visible":true,"clipTo":null,"backgroundColor":"","fillRule":"nonzero","globalCompositeOperation":"source-over","src":"/retroviewer/media/reel_builder_templates/center-canvas-overlay.png","filters":[],"crossOrigin":"","alignX":"none","alignY":"none","meetOrSlice":"meet"}}';


    function log($x)
    {
        mage::log($x, null, "ch_reel_converter.log");
    }


    //TODO this kills all active reels!!
    function importCompleteLegacyReels()
    {

        $write = Mage::getSingleton("core/resource")->getConnection("core_write");

        //die("this kills all active reels!! please comment out to use");

        // first we purge all pending reels; bring all old completed reels in
        $sql = " delete from ch_reels where (status = 9 and file_status = 9) or imported_id != 0";
        $sql = " delete from ch_reels ";
        $rows = $write->query($sql);
        // now we import all reels as status file_status 9 9 - pending
        $sql = "  insert into ch_reels (entity_id, customer_id, imported_id, reel_name, status, final_reel_file, file_status, created_at, updated_at, viewed_at) select r.id, u.user_id, r.id,    r.name, 9, preview_path, 9, from_unixtime(created_at), from_unixtime(modified_at), from_unixtime(modified_at) from imagethr_live.reels r, imagethr_live.user_reels u where u.reel_id = r.id  and preview_path != '' ;";
        $rows = $write->query($sql);

        //   $sql = "select * from ch_reels where status = 9 and file_status = 9 ";



        //  $rows = $write->fetchAll($sql);
        $rows = Mage::getModel('chreels/reels')->getCollection();
        $rows->addFieldToFilter('status', 9);
        $rows->addFieldToFilter('file_status', 9);

        $this->log(__METHOD__ . " full  count " . $rows->count());

        foreach($rows as $importCompleteReel) {

            try {

                $this->log(__METHOD__ . " "  . $importCompleteReel->getId());

                if ($webPath = $this->moveOldCompleteImage($importCompleteReel->getData('customer_id'), $importCompleteReel->getData('final_reel_file'))) {
                    $importCompleteReel->setData('final_reel_file', $webPath);
                    $importCompleteReel->setData('thumb', $webPath);
                    $importCompleteReel->setData('file_status', 11); // imported successful
                    $importCompleteReel->save();
                    continue;

                }

            } catch (Exception $e) {
                $importCompleteReel->delete();
                Mage::logException($e);
                Mage::logException(new Exception("Failed to import reel " . print_r($importCompleteReel->getData(), 1)));
            }
        }

        $rows = Mage::getModel('chreels/reels')->getCollection();
        $rows->addFieldToFilter('status', 9);
        $rows->addFieldToFilter('file_status', 9);

        $this->log(__METHOD__ . " after   count " . $rows->count());


        $rows = Mage::getModel('chreels/reels')->getCollection();
        $rows->addFieldToFilter('status', 9);
        $rows->addFieldToFilter('file_status', 11);

        $this->log(__METHOD__ . " SUCCESS   count " . $rows->count());
    }


    function framesForNinetyDayLegacyReels()
    {

        try {

            // die("this replaces reels with matching Ids");

            $write = Mage::getSingleton("core/resource")->getConnection("core_write");

            $sql = "  select u.user_id, r.id, r.frames, if(r.name !='', r.name , concat('unamed_reel',  u.user_id, '_', r.id )) name, 9, reel_file, 0, from_unixtime(created_at) created_at, from_unixtime(modified_at) updated_at, from_unixtime(modified_at) viewed_at from imagethr_live.reels r, imagethr_live.user_reels u where u.reel_id = r.id  and (from_unixtime(created_at) >= date_sub(now(), interval 92 day) or from_unixtime(modified_at) >=date_sub(now(), interval 92 day)) order by r.id ";
            $rows = $write->fetchAll($sql);


            $this->log(__METHOD__ . __LINE__ );
            $this->log(__METHOD__ . " full  count " . count($rows));

            foreach($rows as $newlyFormattedOldReel) {
                try {
                    $frameData = $newlyFormattedOldReel['frames'];
                    if(!$frameData) {
                        // we skip reel no data..
                        $this->log(__METHOD__ . __LINE__  . " skipping no data " . print_r($newlyFormattedOldReel,1));
                        continue;
                    }

                    $frameData = json_decode($frameData);

                    if(!$frameData || (!$frameData->images && !$frameData->frames && !$frameData->center)) {
                        // nothintg
                        $this->log(__METHOD__ . __LINE__  . " skipping no data " . print_r($newlyFormattedOldReel,1));

                        continue;
                    }

                    $priority = array(
                        //'center',
                        'frames',
                        //  'images_text',
                        'images');
                    $oldFrameData = array();
                    $currentFrameIndex = 1;


                    $newFrames = array(
                        0 => false,
                        1 => false,
                        2 => false,
                        3 => false,
                        4 => false,
                        5 => false,
                        6 => false,
                        7 => false,
                    );

                    // get all ids for SQL
                    $frameIds = array();
                    if(property_exists($frameData, 'center') && $frameData->center) {
                        $frameIds[] = $frameData->center;
                    }

                    foreach($priority as $x) {
                        if(property_exists($frameData, $x) && $frameData->$x && is_array($frameData->$x)) {
                            $frameIds = array_merge($frameIds, array_values($frameData->$x));
                        }
                    }

                    $frameIds = array_unique($frameIds);

                    // go get all frame data
                    $sqlList = implode(",",$frameIds);
                    $sql = "    select i.image_path, f.*  from  imagethr_live.frames f, imagethr_live.images i where f.image_id = i.id  and f.id in ({$sqlList})";
                    $frameImagesData = $write->fetchAll($sql);
                    // build searchable array
                    foreach($frameImagesData as $sfd) {
                        $oldFrameData[$sfd['id']] = $sfd;
                    }
                    unset($frameImagesData);

                    // center is always zero if we have
                    if(property_exists($frameData, 'center') && $imagePath = $this->oldFrameImageExists($oldFrameData[$frameData->center])) {
                        if($webPath = $this->moveOldFrameImage($newlyFormattedOldReel['user_id'], $imagePath, $oldFrameData[$frameData->center])) {
                            $newFrames[0] = $oldFrameData[$frameData->center];
                            $newFrames[0]['reel_frame_path'] = $webPath;
                        }

                    }

                    // get frames first...
                    // TODO we SHOULD get all images that have text first ideally...
                    if(is_array($frameData->frames)) {
                        foreach($frameData->frames as $fid) {
                            if($fid && $imagePath = $this->oldFrameImageExists($oldFrameData[$fid])) {
                                if($webPath = $this->moveOldFrameImage($newlyFormattedOldReel['user_id'], $imagePath, $oldFrameData[$fid])) {
                                    $newFrames[$currentFrameIndex] = $oldFrameData[$fid];
                                    $newFrames[$currentFrameIndex]['reel_frame_path'] = $webPath;
                                    $currentFrameIndex++;

                                }
                            }
                        }
                    }

                    if(is_array($frameData->images)) {
                        foreach($frameData->images as $fid) {
                            if($fid && $imagePath = $this->oldFrameImageExists($oldFrameData[$fid]) && $oldFrameData[$fid]['text'] != '[]') {
                                if($webPath = $this->moveOldFrameImage($newlyFormattedOldReel['user_id'], $imagePath, $oldFrameData[$fid])) {
                                    $newFrames[$currentFrameIndex] = $oldFrameData[$fid];
                                    $newFrames[$currentFrameIndex]['reel_frame_path'] = $webPath;
                                    $currentFrameIndex++;

                                }
                            }
                        }

                        foreach($frameData->images as $fid) {
                            if($fid && $imagePath = $this->oldFrameImageExists($oldFrameData[$fid])) {
                                if($webPath = $this->moveOldFrameImage($newlyFormattedOldReel['user_id'], $imagePath, $oldFrameData[$fid])) {
                                    $newFrames[$currentFrameIndex] = $oldFrameData[$fid];
                                    $newFrames[$currentFrameIndex]['reel_frame_path'] = $webPath;
                                    $currentFrameIndex++;
                                }
                            }
                        }

                    }

                    $this->log(__METHOD__ . __LINE__  . " frames dat a" . print_r($newFrames,1));

		    //Any frame data?
		    $allEmpty = true;
                    foreach($newFrames as $k => $x) {
                        if($x) {
		            $allEmpty = false;
                        }
                    }

		    if($allEmpty) {
                        $this->log(__METHOD__ . __LINE__  . " skipping no frames " . print_r($newFrames,1));
                        continue;
                    }

                    $sql = " delete from  ch_frames where reel_id = {$newlyFormattedOldReel['id']}";

                    $write->query($sql);

                    foreach($newFrames as $k => $legacyFrame) {
			//Make sure there are only 8 frames
			if($k > 7) {
			    break;
			}

			//Populate some data, even if the frame is empty
			$frame = Mage::getModel('chframes/frames');
                        //$frame->setReelId($reel->getEntityId());
                        $frame->setReelId($newlyFormattedOldReel['id']);
                        $frame->setData('frame_index', $k);
			$frame->save();

                        if ($legacyFrame && $legacyFrame['reel_frame_path']) {


                            $y = self::DEFAULT_FRAME;
                            if($k == 0) {
                                $y = self::CENTER_FRAME_CANVAS;
                            }

                            $this->log(__METHOD__ . __LINE__  . " REELy ?"  . print_r($frame->getData(), 1) );

                            $this->log(__METHOD__ . __LINE__  . " SAVED");

                            $path = BP . DS . 'media' . DS . $legacyFrame['reel_frame_path'];

                            $imageDetails = getimagesize($path);

                            $y = str_replace('@IMAGE_WIDTH@', (int)$imageDetails[0], $y);
                            $y = str_replace('@IMAGE_HEIGHT@', (int)$imageDetails[1], $y);

                            $newImagePath = '\\'.self::IMAGE3D_CUSTOMER_SITE_PATH_PREFIX.'\/reelbuilderCb\/?i3d_image=frame&reel_id=' .
                                $newlyFormattedOldReel['id'] . '&frame_id=' .
                                $frame->getId() .
                                '&jpg=true&sf_hash=' . rand();
                            $y = str_replace('@RAW_IMAGE_PART@', $newImagePath, $y);


                            $sampleTextOne = '';
                            $textData = json_decode($legacyFrame['text']);

                            $y = str_replace('@SAMPLE_TEXT@', (string)$sampleTextOne, $y);
                            $fme = json_decode($y);
                            $x = new stdClass;
                            $x->canvas_json = $fme;

                            if(!$x || !is_object($x)  || !is_object($x->canvas_json)) {
                                $x = "failed to decode  $y \n";
                                $this->log(__METHOD__ . " DFAILED DIE and " . $x);
                                print_r($legacyFrame);
                                die($x);
                            }

                            if(count($textData)) {
                                foreach($textData as $text) {
                                    foreach($text as $tk => $tv) {
                                        $tk = trim($tk);
                                        if($tk == 'value') {
                                            $this->log(__METHOD__ . " we  clone  to find " . print_r($text,1));

                                            //$canvasTextObject = clone   $x->canvas_json->objects[1];
                                            $canvasTextObject = (object) (array) $x->canvas_json->objects[1];
                                            $canvasTextObject->text = (string)$tv;
                                            $canvasTextObject->left += 20;
                                            $canvasTextObject->top += 20;

                                            $x->canvas_json->objects[] = $canvasTextObject;
                                        }

                                    }
                                }


                                $x->canvas_json =  json_encode($x->canvas_json);

                                $y = json_encode($x);
                                unset($x);
                            }

                            $frame->setData('source_file', $legacyFrame['reel_frame_path']);
                            $frame->setData('frame_data', $y);

                            //die(" shane work " . print_r($y,1));

                            $frame->setData('reel_id', $newlyFormattedOldReel['id']);
                            $frame->setData('created_at', $newlyFormattedOldReel['created_at']);
                            $frame->setData('updated_at', $newlyFormattedOldReel['updated_at']);
                            $frame->setData('forced_updated_at', true);
                            $frame->setData('legacy_frame_id', $legacyFrame['id']);
                            $frame->setData('legacy_frame_data', json_encode($legacyFrame));

                            $this->log(__METHOD__ . __LINE__  . " frame data " . print_r($frame->getData(), 1));

                            $frame->save();

                            $this->log(__METHOD__ . __LINE__  . " Frame saved");

                        }

                    }

                } catch (Exception $e) {
                    $this->log(__METHOD__ . " failed to import reel " . $e->GetMessage());
                    Mage::logException($e);
                }
            }

        } catch (Exception $e) {
            $this->log(__METHOD__ . "we have " . $e->GetMessage());
            Mage::logException($e);
        }
    }

    function importNOTCompleteLegacyReels()
    {

        try {

            // die("this replaces reels with matching Ids");

            $write = Mage::getSingleton("core/resource")->getConnection("core_write");

            // first we purge all imported reels that are not complete...
            $sql = " delete from ch_reels where (status = 11 and file_status = 0) ";
            $rows = $write->query($sql);

            //$sql = "  select u.user_id, r.id, r.frames,    if(r.name !='', r.name , concat('unamed_reel',  u.user_id, '_', r.id )) name, 11, reel_file, 0, from_unixtime(created_at) created_at, from_unixtime(modified_at) updated_at, from_unixtime(modified_at) viewed_at from imagethr_live.reels r, imagethr_live.user_reels u where u.reel_id = r.id  and preview_path = '' and r.id not in (select imported_id from  ch_reels)  and r.id = 37495 order by r.id desc ";
            $sql = "  select u.user_id, r.id, r.frames,    if(r.name !='', r.name , concat('unamed_reel',  u.user_id, '_', r.id )) name, 9, reel_file, 0, from_unixtime(created_at) created_at, from_unixtime(modified_at) updated_at, from_unixtime(modified_at) viewed_at from imagethr_live.reels r, imagethr_live.user_reels u where u.reel_id = r.id  and preview_path = '' and r.id not in (select imported_id from  ch_reels) order by r.id ";
            $rows = $write->fetchAll($sql);


            $this->log(__METHOD__ . __LINE__ );
            $this->log(__METHOD__ . " full  count " . count($rows));

            foreach($rows as $newlyFormattedOldReel) {
                try {
                    $frameData = $newlyFormattedOldReel['frames'];
                    if(!$frameData) {
                        // we skip reel no data..
                        $this->log(__METHOD__ . __LINE__  . " skipping no data " . print_r($newlyFormattedOldReel,1));
                        continue;
                    }

                    $frameData = json_decode($frameData);

                    if(!$frameData || (!$frameData->images && !$frameData->frames && !$frameData->center)) {
                        // nothintg
                        $this->log(__METHOD__ . __LINE__  . " skipping no data " . print_r($newlyFormattedOldReel,1));

                        continue;
                    }

                    $priority = array(
                        //'center',
                        'frames',
                        //  'images_text',
                        'images');
                    $oldFrameData = array();
                    $currentFrameIndex = 1;


                    $newFrames = array(
                        0 => false,
                        1 => false,
                        2 => false,
                        3 => false,
                        4 => false,
                        5 => false,
                        6 => false,
                        7 => false,
                    );

                    // get all ids for SQL
                    $frameIds = array();
                    if(property_exists($frameData, 'center') && $frameData->center) {
                        $frameIds[] = $frameData->center;
                    }

                    foreach($priority as $x) {
                        if(property_exists($frameData, $x) && $frameData->$x && is_array($frameData->$x)) {
                            $frameIds = array_merge($frameIds, array_values($frameData->$x));
                        }
                    }

                    $frameIds = array_unique($frameIds);

                    // go get all frame data
                    $sqlList = implode(",",$frameIds);
                    $sql = "    select i.image_path, f.*  from  imagethr_live.frames f, imagethr_live.images i where f.image_id = i.id  and f.id in ({$sqlList})";
                    $frameImagesData = $write->fetchAll($sql);
                    // build searchable array
                    foreach($frameImagesData as $sfd) {
                        $oldFrameData[$sfd['id']] = $sfd;
                    }
                    unset($frameImagesData);

                    // center is always zero if we have
                    if(property_exists($frameData, 'center') && $imagePath = $this->oldFrameImageExists($oldFrameData[$frameData->center])) {
                        if($webPath = $this->moveOldFrameImage($newlyFormattedOldReel['user_id'], $imagePath, $oldFrameData[$frameData->center])) {
                            $newFrames[0] = $oldFrameData[$frameData->center];
                            $newFrames[0]['reel_frame_path'] = $webPath;
                        }

                    }

                    // get frames first...
                    // TODO we SHOULD get all images that have text first ideally...
                    if(is_array($frameData->frames)) {
                        foreach($frameData->frames as $fid) {
                            if($fid && $imagePath = $this->oldFrameImageExists($oldFrameData[$fid])) {
                                if($webPath = $this->moveOldFrameImage($newlyFormattedOldReel['user_id'], $imagePath, $oldFrameData[$fid])) {
                                    $newFrames[$currentFrameIndex] = $oldFrameData[$fid];
                                    $newFrames[$currentFrameIndex]['reel_frame_path'] = $webPath;
                                    $currentFrameIndex++;

                                }
                            }
                        }
                    }

                    if(is_array($frameData->images)) {
                        foreach($frameData->images as $fid) {
                            if($fid && $imagePath = $this->oldFrameImageExists($oldFrameData[$fid]) && $oldFrameData[$fid]['text'] != '[]') {
                                if($webPath = $this->moveOldFrameImage($newlyFormattedOldReel['user_id'], $imagePath, $oldFrameData[$fid])) {
                                    $newFrames[$currentFrameIndex] = $oldFrameData[$fid];
                                    $newFrames[$currentFrameIndex]['reel_frame_path'] = $webPath;
                                    $currentFrameIndex++;

                                }
                            }
                        }

                        foreach($frameData->images as $fid) {
                            if($fid && $imagePath = $this->oldFrameImageExists($oldFrameData[$fid])) {
                                if($webPath = $this->moveOldFrameImage($newlyFormattedOldReel['user_id'], $imagePath, $oldFrameData[$fid])) {
                                    $newFrames[$currentFrameIndex] = $oldFrameData[$fid];
                                    $newFrames[$currentFrameIndex]['reel_frame_path'] = $webPath;
                                    $currentFrameIndex++;
                                }
                            }
                        }

                    }

                    $this->log(__METHOD__ . __LINE__  . " frames dat a" . print_r($newFrames,1));

		    //Any frame data?
		    $allEmpty = true;
                    foreach($newFrames as $k => $x) {
                        if($x) {
		            $allEmpty = false;
                        }
                    }

		    if($allEmpty) {
                        $this->log(__METHOD__ . __LINE__  . " skipping no frames " . print_r($newFrames,1));
                        continue;
                    }

                    $sql = " replace into ch_reels (entity_id, customer_id) values ({$newlyFormattedOldReel['id']},{$newlyFormattedOldReel['user_id']})";

                    $write->query($sql);

                    $sql = " delete from  ch_frames where reel_id = {$newlyFormattedOldReel['id']}";

                    $write->query($sql);


                    $reel =  Mage::getModel('chreels/reels')->load($newlyFormattedOldReel['id']);
                    if($reel->getEntityId() != $newlyFormattedOldReel['id']) {
                        die(" where is the reel !!!");

                    }


                    $reel->setCustomerId($newlyFormattedOldReel['user_id']);
                    $reel->setData('imported_id', $newlyFormattedOldReel['id']);
                    $reel->setData('entity_id', $newlyFormattedOldReel['id']);
                    $reel->setData('reel_name', $newlyFormattedOldReel['name']);
                    // IMPORTED_NEW_STATUS
                    $reel->setData('status', 11);
                    $reel->setData('file_status', 0);
                    $reel->setData('created_at',  $newlyFormattedOldReel['created_at']);
                    $reel->setData('updated_at', $newlyFormattedOldReel['updated_at']);
                    $reel->setData('forced_updated_at', true);
                    $reel->setData('viewed_at', $newlyFormattedOldReel['viewed_at']);

                    $this->log(__METHOD__ . __LINE__  . " saving REEL  "  . print_r($reel->getData(),1));

                    $reel->save();

                    // TODO use SQL to frorce created at / updated_at - as it was old and
                    $this->log(__METHOD__ . __LINE__  . " REEL  saved " .  $reel->getEntityId() );

                    $reel =  Mage::getModel('chreels/reels')->load($reel->getEntityId());
                    if($reel->getEntityId() != $newlyFormattedOldReel['id']) {
                        die(" FUCK!!!");

                    }

                    foreach($newFrames as $k => $legacyFrame) {
			//Make sure there are only 8 frames
			if($k > 7) {
			    break;
			}

			//Populate some data, even if the frame is empty
			$frame = Mage::getModel('chframes/frames');
                        $frame->setReelId($reel->getEntityId());
                        $frame->setData('frame_index', $k);
			$frame->save();

                        if ($legacyFrame && $legacyFrame['reel_frame_path']) {


                            $y = self::DEFAULT_FRAME;
                            if($k == 0) {
                                $y = self::CENTER_FRAME_CANVAS;
                            }

                            $this->log(__METHOD__ . __LINE__  . " REELy ?"  . print_r($frame->getData(), 1) );

                            $this->log(__METHOD__ . __LINE__  . " SAVED");

                            $path = BP . DS . 'media' . DS . $legacyFrame['reel_frame_path'];

                            $imageDetails = getimagesize($path);

                            $y = str_replace('@IMAGE_WIDTH@', (int)$imageDetails[0], $y);
                            $y = str_replace('@IMAGE_HEIGHT@', (int)$imageDetails[1], $y);

                            $newImagePath = '\\'.self::IMAGE3D_CUSTOMER_SITE_PATH_PREFIX.'\/reelbuilderCb\/?i3d_image=frame&reel_id=' .
                                $reel->getId() . '&frame_id=' .
                                $frame->getId() .
                                '&jpg=true&sf_hash=' . rand();
                            $y = str_replace('@RAW_IMAGE_PART@', $newImagePath, $y);


                            $sampleTextOne = '';
                            $textData = json_decode($legacyFrame['text']);

//                            if(isset($legacyFrame['text'])) {
//                                $textData = json_decode($legacyFrame['text']);
//                                if($textData && is_array($textData) && count($textData) && is_object($textData[0]) && property_exists($textData[0], 'value')) {
//
//                                    $sampleTextOne = str_replace('"', '\\\\"', (string)$textData[0]->value);
//                                }
//                            }

                            $y = str_replace('@SAMPLE_TEXT@', (string)$sampleTextOne, $y);
			//$y = self::DEFAULT_FRAME;
                            $fme = json_decode($y);
                            $x = new stdClass;
                            $x->canvas_json = $fme;

                            if(!$x || !is_object($x)  || !is_object($x->canvas_json)) {
                                $x = "failed to decode  $y \n";
                                $this->log(__METHOD__ . " DFAILED DIE and " . $x);
                                print_r($legacyFrame);
                                die($x);
                            }


//die();

                            if(count($textData)) {

                                // $x->canvas_json =  json_decode($x->canvas_json);

                                foreach($textData as $text) {
                                    foreach($text as $tk => $tv) {
                                        $tk = trim($tk);
                                        if($tk == 'value') {
                                            // if(is_object($text) && property_exists($text, 'value')) {
                                            $this->log(__METHOD__ . " we  clone  to find " . print_r($text,1));

                                            //$canvasTextObject = clone   $x->canvas_json->objects[1];
                                            $canvasTextObject = (object) (array) $x->canvas_json->objects[1];
                                            $canvasTextObject->text = (string)$tv;
                                            $canvasTextObject->left += 20;
                                            $canvasTextObject->top += 20;

                                            $x->canvas_json->objects[] = $canvasTextObject;
                                        }

                                    }
                                }


                                $x->canvas_json =  json_encode($x->canvas_json);

                                $y = json_encode($x);
                                unset($x);
                            }

                            $frame->setData('source_file', $legacyFrame['reel_frame_path']);
                            $frame->setData('frame_data', $y);

                            //die(" shane work " . print_r($y,1));

                            $frame->setData('reel_id', $reel->getId());
                            $frame->setData('created_at', $newlyFormattedOldReel['created_at']);
                            $frame->setData('updated_at', $newlyFormattedOldReel['updated_at']);
                            $frame->setData('forced_updated_at', true);
                            $frame->setData('legacy_frame_id', $legacyFrame['id']);
                            $frame->setData('legacy_frame_data', json_encode($legacyFrame));

                            $this->log(__METHOD__ . __LINE__  . " frame data " . print_r($frame->getData(), 1));

                            $frame->save();

                            $this->log(__METHOD__ . __LINE__  . " Frame saved");

                        }

                    }

                    $this->log(__METHOD__ . " reelid CONVERTYED " . $newlyFormattedOldReel['id'] . " NEW {$reel->getId()} ");

                } catch (Exception $e) {
                    $this->log(__METHOD__ . " failed to import reel " . $e->GetMessage());
                    Mage::logException($e);
                }
            }

        } catch (Exception $e) {
            $this->log(__METHOD__ . "we have " . $e->GetMessage());
            Mage::logException($e);
        }

    }

    function moveOldFrameImage($cid, $file)
    {
        $path = self::FRAME_PATH;
        return $this->moveOldImage($cid, $file, $path);
    }

    function moveOldCompleteImage($cid, $file)
    {
        $path = self::COMPLETE_PATH;
        return $this->moveOldImage($cid, $file, $path);
    }

    function moveOldImage($cid, $file, $path = false)
    {
       // $this->log(__METHOD__ . __LINE__);
        $baseDestinationPath = BP . DS . $path;
        $destinationPath = $baseDestinationPath . $cid . DS;

        if(!is_dir($destinationPath)) {
            @mkdir($baseDestinationPath, 0775, true);
        }


        if(!is_dir($destinationPath)) {
      //      $this->log(__METHOD__ . " trying to make $destinationPath " );
            mkdir($destinationPath, 0775, true);
        }

        $webPath = $path . DS . $cid . DS . basename($file);

        $middlePath = '../';
        if(!strstr($file, 'includes/media')) {
            $middlePath = '../includes/media/files/reels/';
        }

        $originalFile = BP . DS . $middlePath . $file;
        $newFilePath = $destinationPath . basename($file);

     //   $this->log(__METHOD__ . __LINE__ . " original: "  . $originalFile);
       // $this->log(__METHOD__ . __LINE__ . "dest: "  . $newFilePath);

        if (file_exists($originalFile)) {
            // remove the reel
            @link($originalFile, $newFilePath);

            if (file_exists($newFilePath)) {
                $webPath = str_replace('/media/', '', $webPath);
                $this->log(__METHOD__ . __LINE__ . " sending back " . $webPath);

                return $webPath;
            }
        }
//        $this->log(__METHOD__ . __LINE__);

        return null;
    }

    function oldFrameImageExists($testFrame)
    {
        $actualFile = $testFrame['image_path'];
        $actualFile = str_replace('http://image3dce001.van03.collinsharper.com', '', $actualFile);
        $actualFile = str_replace('https://image3dce001.van03.collinsharper.com', '', $actualFile);
        $actualFile = str_replace('https://image3dce002.van03.collinsharper.com', '', $actualFile);
        $actualFile = str_replace('http://image3dce002.van03.collinsharper.com', '', $actualFile);
        $actualFile = str_replace('http://www.image3d.com', '', $actualFile);
        $actualFile = str_replace('https://www.image3d.com', '', $actualFile);
        $actualFile = preg_replace('|^/~image3d|i', '', $actualFile);
        $this->log(__METHOD__ . __LINE__ . " testing for ". BP . DS . '../' . $actualFile);

        return file_exists(BP . DS . '../' .  $actualFile) ? $actualFile : null;
    }
}
