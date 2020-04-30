<?php

namespace App\CoreModule\Model;

use Nette;
use Nette\Database\Context;
use App\CoreModule\Model\SensorManager;
use DateInterval;
use DateTimeZone;
use Nette\Utils\DateTime;
//use DateTime;
use DateTimeImmutable;
use Nette\Database\UniqueConstraintViolationException;
use App\Utils\Pretty;

class ThisSensorManager
{
    use Nette\SmartObject;


	public const
        START = "2000-01-01 00:00:00",
        MINUTE = 60;

    public const
        OLDWORK = '1',	// Machine is working
        OLDSTOP = "0",	// Machine is not working
        OLDREWORK = "2"; 	//State after end of stoji

    public const
        FINISHED = 'FINISHED',	    // Machine is working
        STOP = "STOP",	    // Machine is not working
        REWORK = "REWORK", 	// State after end of STOP
        ON = 'ON',          // ON machine
        OFF = 'OFF';        // OFF machine



    private $database;
    private $defaultMsgLanguage;
    private $defaultAPILanguage;
    private $sensorManager;

    public function __construct($defaultMsgLanguage,$defaultAPILanguage, Context $database, SensorManager $sensorManager)
    {
        $this->database = $database;
        $this->defaultMsgLanguage = $defaultMsgLanguage;
        $this->defaultAPILanguage = $defaultAPILanguage;
        $this->sensorManager = $sensorManager;

    }

    public function pretty($state = true, $main, $englishMsg = "", $czechMsg = "" )
    {
        return( array($state, $main, $englishMsg, $czechMsg));
    }  

    /**
     * Save sensor status to database
     * @param string $sName
     * @param mixed $state
     */
    public function addEvent($sName, $state)
    {
        if(!$this->sensorManager->sensorIsExist($sName))
        {
            // return array(false, "Sensor with name ".$sName." delete does not exist", "Senzor s názvem".$sName." neexistuje");
            return $this->pretty(0,"","Sensor with name ".$sName." does not exist", "Senzor s názvem".$sName." neexistuje");
        }

        if($succes = $this->database->table($sName)->insert([
            'state' => $state,
        ]))
        {
            return $this->pretty(true, "Event created", "Záznam byl vytvořen", $sName, $state);
        }
        else
        {
            return $this->pretty(false, "ERROR!!!", "ERROR!!!");
        }
    }
    //BY NAME//

    public function isEmpty($array)
    {
        if(count($array)<1)
        {
            return true;
        }
        return false;
    }


    //////////////
    // Gets
    //////////////

    public function getAllEvents($sName)
    {
        return $this->database->table($sName)->fetchAll();
    }

    public function getAllEventsState($sName, $state)
    {
        return $this->database->table($sName)->where("state", $state)->fetchAll();
    }

    public function getAllEventsOlder($sName, $time)
    {
        return $this->database->table($sName)->where("time >=?", $time)->fetchAll();
    }

    public function getAllEventsYounger($sName, $time)
    {
        return $this->database->table($sName)->where("time <=?", $time)->fetchAll();
    }

    //////////////
    // Counts
    //////////////

    public function countAllEvents($sName)
    {
        return $this->database->table($sName)->count();
    }

    public function countAllEventsState($sName, $state)
    {
        return $this->database->table($sName)->where("state", $state)->count();
    }



    //////////////
    // ID
    //////////////

    public function getFirstIdState($sName, $time, $state)
    {
        return $this->database->table($sName)->where("time >=? AND state=?", $time, $state)->fetch()->id;
    }

    public function getLastIdState($sName, $time, $state)
    {
        return $this->database->table($sName)->where("time <=? AND state=?", $time, $state)->order("id DESC")->fetch()->id;
    }

    // public function getAllIdState($sName, $from , $to, $state = self::FINISHED)
    // {
    //     $ids = $this->database->table($sName)->where("time >=? AND time <=? AND state=?", $from, $to, $state)->fetchAll();

    //     $out = array();
    //     foreach($ids as $id)
    //     {
    //         $out[] = $id->id;
    //     }
    //     return $out;
    // }


    public function getAllIdState($sName, $from , $to, $state = self::FINISHED)
    {
        $ids = $this->database->table($sName)->where("time >=? AND time <=? AND state=?", $from, $to, $state)->fetchAll();

        $out = array();
        foreach($ids as $id)
        {
            // $out[] = $id->id => $id->state;
            array_push($out, array($id->id => $id->state));
        }
        return $out;
    }    

    public function getAllId($sName, $from , $to)
    {
        $ids = $this->database->table($sName)->where("time >=? AND time <=?", $from, $to)->fetchAll();

        $out = array();
        foreach($ids as $id)
        {
            // $out[] = $id->id => $id->state;
            array_push($out, array($id->id => $id->state));
        }
        return $out;
    }

    public function getAllTime($sName, $ids)
    {
        //dump($ids);
        //echo "First key".array_key_first($ids);
        //dump( array_key_last($ids)+1);
        $first = $this->database->table($sName)->where("id=?", array_key_first($ids[0]) )->fetch()->time->getTimestamp();
        $last = $this->database->table($sName)->where("id=?", array_key_last($ids)+1)->fetch()->time->getTimestamp();
        $res =  $last-$first;
        return array(new DateInterval("PT".$res."S"), $res);
    }


    public function testPretty()
    {
        // dump($x = Pretty::return(true, array(1,2,3), "IT is ok", "Je to ok"));
        // $y = PrettyReturn::return(true, array(1,2,3), "IT is ok", "Je to ok");
        dump(Pretty::fromParts(2000,1,11,1));
        // dump($y);
        return 1;//$x;
    }

    public function getStopTime($sName, $ids)
    {
        if(!$this->sensorManager->sensorIsExist($sName))
        {
            return $this->pretty(false,"",  "Sensor with name ".$sName." delete does not exist", "V poli neni žádný prvek");
        }

        if($this->isEmpty($ids))
        {
            return $this->pretty(false,"",  "IDs is empty", "V poli neni žádný prvek");
        }
        
        $sState = 0;
        $time = 0;
        // foreach ($variable as $key => $value) {
        //     # code...
        // }

        foreach($ids as $id => $state)
        {
            
            // dump($state);
            //dump($id);
            //dump( $state[key($state)]);
            //echo "ID: ".key($state)."STATE ->".$state[key($state)];
            switch ($sState) {
                case 0:
                    if($state[key($state)] == self::STOP)
                    {
                        $sState++;
                        $start = $this->database->table($sName)->where("id=?", $id+1)->fetch()->time->getTimestamp();
                        //echo " -> STOP -> ".$start;
                    }                    
                    break;
                case 1:
                    if($state[key($state)] == self::REWORK)
                    {
                        $stop = $this->database->table($sName)->where("id=?", $id+1)->fetch()->time->getTimestamp();
                        $sState = 0;
                        $time += $stop-$start;
                        //echo " -> REWORK -> ".$stop."-> ALL: ". $time;
                    }
                    break;

                // default:
                //     # code...
                //     break;
            }
            //echo "<br><br>";
        }        

        // $first = $this->database->table($sName)->where("id=?", $ids[0])->fetch()->time->getTimestamp();
        // $last = $this->database->table($sName)->where("id=?", end($ids))->fetch()->time->getTimestamp();
        // $res =  $last-$first;
        return array(new DateInterval("PT".$time."S"), $time);

    }

    public function getAproxStopTime($stopTimestamp, $stopCount)
    {
        $time = ceil($stopTimestamp/$stopCount);
        return array(new DateInterval("PT".$time."S"), $time);
    }    

    public function getWorkTime($allTimestamp, $stopTimestamp)
    {
        $time = $allTimestamp-$stopTimestamp;
        return array(new DateInterval("PT".$time."S"), $time);
    }



    public function getAproxWorkTime($workTimestamp, $workCount)
    {
        $time = ceil($workTimestamp/$workCount);
        return array(new DateInterval("PT".$time."S"), $time);
    }

    public function getWorkTimeOld($sName, $ids): DateInterval
    {
        $last = $this->database->table($sName)->where("id=?", 1)->fetch();
        //echo $out =  $last->time->getTimestamp();
        //dump($last->time);
        $workTime = 0;

        // $next = $this->database->table($sName)->where("id=?", 2)->fetch();
        // //echo $next->time->getTimestamp();
        // dump($next->time);


        // $start = DateTime::from($out);

        // //echo $start;


        foreach($ids as $id)
        {
            $actual = $this->database->table($sName)->where("id=?", $id)->fetch();

            //dump($last->actWork);

            //  First loop
            if($id < $ids[1])
            {
                $actWork=3*self::MINUTE; // 3 Minutes
                // $workTime+=$actWork;
                $lastWork = $actWork;
                // $this->database->table($sName)->where("id=?", $id)->update(['work'=>$actWork, 'time'=>$actual->time]);
                //echo "FIRST";

                //echo " -ID:".$id." Last: ".$last->time." Actual: ".$actual->time."<br>";
                //echo "Add:".$actWork."->T:".gmdate("H:i:s",$actWork)." -> result: ".$workTime."->T:".gmdate("H:i:s",$workTime)."<br><br><br>";


            }
            // Next loops
            else if($id >= $ids[1])
            {
                $actWork=$actual->time->getTimestamp()-$last->time->getTimestamp();
                if($actWork>$lastWork+1*self::MINUTE)
                {
                    //echo "NEXT - BIG";
                    $actWork = $lastWork;
                    $workTime+=$actWork;

                }
                if($actWork<$lastWork-1.5*self::MINUTE)
                {
                    //echo "NEXT - SMALL";
                    $actWork = 0;
                    $workTime+=$actWork;

                }
                else
                {
                    $workTime+=$actWork;
                    //echo "NEXT - NORMAL";

                }
                $this->database->table($sName)->where("id=?", $id)->update(['work'=>$actWork, 'time'=>$actual->time]);

                //echo " -ID:".$id." Last: ".$last->time." Actual: ".$actual->time."<br>";
                //echo "Add:".$actWork."->T:".gmdate("H:i:s",$actWork)." -> result: ".$workTime."->T:".gmdate("H:i:s",$workTime)."<br><br><br>";

                $last = $actual;
                $lastWork = $actWork;

            }

        }
        $this->database->table($sName)->where("id=?", 10)->update(['work'=>$workTime]);
        return new DateInterval("PT".$workTime."S");
    }


    public function dateIntervalToSeconds($dateInterval)
    {
        $reference = new DateTimeImmutable;
        $endTime = $reference->add($dateInterval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }



    ////////////////////
    // Time counting
    ////////////////////


    //////////////////////////////

    public function getRunTime2($sName, $ids)
    {
        $start = DateTime::from(self::START);
        $work = DateTime::from("2000-01-01 00:03:00");
        $workTime = date_diff($start, $work);
        //echo $start;
        //dump($ids);
        ////echo "x".$ids[1];
        $last = $this->database->table($sName)->where("id=?", $ids[0])->fetch();
        //echo "First: ".$last->time ;

        $workTime = $last->work;
        foreach($ids as $id)
        {


            //dump($last->work);
            if($id < $ids[1])
            {
                //echo "<br>Add: ".$start->add( $workTime);
                $this->database->table($sName)->where("id=?", $id)->insert(['work'=>$workTime]);

            }
            else if($id >= $ids[1])
            {
                $actual = $this->database->table($sName)->where("id=?", $id)->fetch();

                //echo "<br>ID:".$id."->";

                //echo $last->time." - ".$actual->time;
                $add = date_diff(DateTime::from($last->time), DateTime::from($actual->time));
                dump($add);
                if(($add->i)>($actual->work->i))
                {
                    //echo"  *BIG: ".$add->i." ->".$start->add( $workTime);
                }
                else if(($add->i)<=($actual->work->i))
                {
                    //echo " *NORMAL*".$add->i." ->".$start->add($add);
                    $this->database->table($sName)->where("id=?", $id)->insert(['work'=>$add]);
                }

                $last = $actual;
            }

        }
        $out = date_diff(DateTime::from(self::START),$start);
        return $out;
    }

    public function resetDB($sName)
    {
        for ($i = 0;$i<=8;$i++ )
        {
            $this->database->table($sName)->where("id = ?", $i)->update([ "work"=>"0"]);
        }

        $this->database->table($sName)->where("id = 1")->update(["time"=>"2020-04-24 22:03:00", "work"=>"0"]);
        $this->database->table($sName)->where("id = 2")->update(["time"=>"2020-04-24 22:06:00"]);
        $this->database->table($sName)->where("id = 3")->update(["time"=>"2020-04-24 22:08:00"]);
        $this->database->table($sName)->where("id = 4")->update(["time"=>"2020-04-24 22:13:00"]);
        $this->database->table($sName)->where("id = 5")->update(["time"=>"2020-04-24 22:16:00"]);
        $this->database->table($sName)->where("id = 6")->update(["time"=>"2020-04-24 22:19:00"]);
        $this->database->table($sName)->where("id = 7")->update(["time"=>"2020-04-24 22:21:00"]);
        $this->database->table($sName)->where("id = 8")->update(["time"=>"2020-04-24 22:22:50"]);

        $this->database->table($sName)->where("id = ?", 10)->update([ "work"=>"0"]);



    }
}


