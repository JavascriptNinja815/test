<?php

class Collinsharper_Image3d_Helper_Generation
{

    const FRAME_WIDTH = 1132; // frame + shift + 6 bleed
    const FRAME_HEIGHT = 1018;
    const REEL_WIDTH = 8735;
    const FRAME_OFFSET = 720;
    const POSITIONS = 7;
    const PI = M_PI;

    var $_frame_map = array(
        1 => 8,
        2 => 5,
        3 => 9,
        4 => 6,
        5 => 10,
        6 => 7,
        7 => 11,
        8 => 1,
        9 => 12,
        10 => 2,
        11 => 13,
        12 => 3,
        13 => 14,
        14 => 4,
    );

    var $_others = array(
    );

    const CACHE_KEY = 'ch_frame_placement_generation';
    function getPositions()
    {
        //$cache = Mage::app()->getCache();
	$file = BP . '/var/cache/' . self::CACHE_KEY;
        $data = is_file($file) ? file_get_contents($file) : false;
        if(!$data || !strlen($data)) {

            $centerPointX = self::REEL_WIDTH /2;
            $centerPointY = $centerPointX;
            $this->_others = array(
                'center_point_x' => $centerPointX,
                'center_point_y' => $centerPointY,
            );
            $this->_others['center_top_left_x'] = $centerPointX - .5 * self::FRAME_WIDTH;
            $this->_others['center_top_left_y'] = $centerPointY - .5 * self::FRAME_HEIGHT;
            $this->_others['center_top_right_x'] =  $centerPointX + .5 * self::FRAME_WIDTH;
            $this->_others['center_top_right_y'] = $this->_others['center_top_left_y'];
            $this->_others['center_bottom_left_x'] = $this->_others['center_top_left_x'];
            $this->_others['center_bottom_left_y'] = $centerPointY + .5 * self::FRAME_HEIGHT;
            $this->_others['center_bottom_right_x'] = $this->_others['center_top_right_x'];
            $this->_others['center_bottom_right_y'] = $this->_others['center_bottom_left_y'];
            $this->_others['origin_top_left_x'] = -.5 * self::FRAME_WIDTH;
            $this->_others['origin_top_left_y'] = -.5 * self::FRAME_HEIGHT;
            $this->_others['origin_top_right_x'] = .5 * self::FRAME_WIDTH;
            $this->_others['origin_top_right_y'] = -.5 * self::FRAME_HEIGHT;
            $this->_others['origin_bottom_left_x'] = $this->_others['origin_top_left_x'];
            $this->_others['origin_bottom_left_y'] = .5 * self::FRAME_HEIGHT;
            $this->_others['origin_bottom_right_x'] = $this->_others['origin_top_right_x'];
            $this->_others['origin_bottom_right_y'] =  $this->_others['origin_bottom_left_y'];


            $this->_others["frame_1_rad"] = 0;
            $this->_others["frame_1_x"] = (self::REEL_WIDTH-self::FRAME_WIDTH/2 - self::FRAME_OFFSET);
            $this->_others["frame_1_y"] = self::REEL_WIDTH/2;

            for($i=2;$i<15;$i++) {
                if($i == 3) {
                    $centerPointX -= 2  ;
                    $centerPointY -= 2 ;
                } else if ( $i == 8) {
                    $centerPointX = self::REEL_WIDTH /2;
                    $centerPointX += 2  ;
                }else if ( $i == 14) {
                    $centerPointX -= 1  ;
                    $centerPointY += 2  ;
                }
                $previous = $i-1;
                $thisRad = $previous * self::PI / self::POSITIONS;
                $thisRadCos = cos($thisRad);
                $thisRadSin = sin($thisRad);
                $this->_others["frame_{$i}_rad"] = $thisRad;
                // =$B$8 +(($C29-$B$8)*COS(B30) + ($D29 - $C$8)*SIN(B30))
                $this->_others["frame_{$i}_x"] = $centerPointX +
                    ( ( $this->_others["frame_{$previous}_x"] - $centerPointX )
                        * $thisRadCos) +
                    ($this->_others["frame_{$previous}_y"]  -
                        $centerPointY) *
                    $thisRadSin;
                //  =$C$8+($C29-$B$8)*(-1)*SIN(B30)+($D29-$C$8)*COS(B30)
                $this->_others["frame_{$i}_y"] = $centerPointY +
                    ( ( $this->_others["frame_{$previous}_x"] - $centerPointX )
                        *(-1) * $thisRadSin
                        + ($this->_others["frame_{$previous}_y"]  - $centerPointY)
                        * $thisRadCos);

            }



            $frameX = $this->_others["frame_1_x"];
            $frameY = $this->_others["frame_1_y"];
            $frameRad = $this->_others["frame_1_rad"];
            $data = array(
                'rad' => $frameRad,
                'degrees' => $frameRad * 180 / self::PI,
                'cords' => array(
                    array('x' => $this->_others['origin_bottom_left_x'] + $frameX, 'y' => $this->_others['origin_bottom_left_y'] + $frameY),
                    array('x' => $this->_others['origin_bottom_right_x'] + $frameX, 'y' => $this->_others['origin_bottom_right_y'] + $frameY),
                    array('x' => $this->_others['origin_top_right_x'] + $frameX, 'y' => $this->_others['origin_top_right_y'] + $frameY),
                    array('x' => $this->_others['origin_top_left_x'] + $frameX, 'y' => $this->_others['origin_top_left_y'] + $frameY),
                )
            );

            $indexedFramePositions = array(
                '1' => $data
            );

            for($i=2;$i<15;$i++) {
                $data = array();
                $previous = $i-1;
                $baseData =  $indexedFramePositions[1]['cords'];

                $frameRad = $this->_others["frame_{$i}_rad"];
                $frameRadCos = cos($frameRad);
                $frameRadSin = sin($frameRad);
                $data = array(
                    'rad' => $frameRad,
                    'degrees' => $frameRad * 180 / self::PI,
                    'cords' => array(
                        // b8 = $centerPointX
                        // d 64 is the FIRST  one
                        //=$B$8 +(($D$64-$B$8)*COS(B65) + ($E$64 - $C$8)*SIN(B65))
                        //=$C$8+(F$64-$B$8)*(-1)*SIN($B65)+(G$64-$C$8)*COS($B65)

                        // LAST ONE
                        // =$B$8 +(($D$64-$B$8)*COS(B77) + ($E$64 - $C$8)*SIN(B77))
                        array(
                            'x' => $centerPointX + (($baseData[0]['x'] - $centerPointX) * $frameRadCos + ($baseData[0]['y'] - $centerPointY) * $frameRadSin)  ,
                            'y' =>$centerPointX + (($baseData[0]['x'] - $centerPointX) * -1 *  $frameRadSin + ($baseData[0]['y'] - $centerPointY) * $frameRadCos)),
                        array('x' => $centerPointX + (($baseData[1]['x'] - $centerPointX) * $frameRadCos + ($baseData[1]['y'] - $centerPointY) * $frameRadSin)  ,
                            'y' => $centerPointX + (($baseData[1]['x'] - $centerPointX) * -1 *  $frameRadSin + ($baseData[1]['y'] - $centerPointY) * $frameRadCos)),
                        array('x' => $centerPointX + (($baseData[2]['x'] - $centerPointX) * $frameRadCos + ($baseData[2]['y'] - $centerPointY) * $frameRadSin)  ,
                            'y' => $centerPointX + (($baseData[2]['x'] - $centerPointX) * -1 *  $frameRadSin + ($baseData[2]['y'] - $centerPointY) * $frameRadCos)),
                        array('x' => $centerPointX + (($baseData[3]['x'] - $centerPointX) * $frameRadCos + ($baseData[3]['y'] - $centerPointY) * $frameRadSin)  ,
                            'y' => $centerPointX + (($baseData[3]['x'] - $centerPointX) * -1 *  $frameRadSin + ($baseData[3]['y'] - $centerPointY) * $frameRadCos)),
                    )
                );




                $indexedFramePositions[$i] = $data;
            }
            $this->_others['index_frame_positions'] = $indexedFramePositions;
            $placementData = array();
            for($i=1;$i<15;$i++) {
                $fakePlace = $i < 8 ? $i + 7 : $i-7;
                $tempCoordsData  = $indexedFramePositions[$i]['cords'];

                $placementData[$this->_frame_map[$i]] = array (
                    'rotate' =>  360-(360/14*($fakePlace-1)),
                    'cords' =>
                        array (
                            'x' => min(
                                $tempCoordsData[0]['x'],
                                $tempCoordsData[1]['x'],
                                $tempCoordsData[2]['x'],
                                $tempCoordsData[3]['x']
                            ),
                            'y' => min(
                                $tempCoordsData[0]['y'],
                                $tempCoordsData[1]['y'],
                                $tempCoordsData[2]['y'],
                                $tempCoordsData[3]['y']
                            )
                        )
                );
            }

            $this->_others['frame_placement'] = $placementData;

            $data = serialize($this->_others);
        file_put_contents($file,  $data);
        }

        $data = unserialize($data);
        return $data['frame_placement'];
    }
}
