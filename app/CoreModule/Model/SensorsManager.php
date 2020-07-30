<?php

namespace App\CoreModule\Model;


use Exception;
use Nette;
use Nette\Database\Context;
use App\Utils\Pretty;
// use App\CoreModule\Exceptions;
use App\Exceptions;
use App\Exceptions\MyException;


class SensorsManager
{
	use Nette\SmartObject;

    private $database;
    private $defaultMsgLanguage;
    private $defaultAPILanguage;
    
    public function __construct( Context $database )
    {
        $this->database = $database;
    }


    /**
     * Get all settings from database 
     * @return Exception|\Nette\Database\Table\ActiveRow
     */
    public function getTitleSettings()
    {
        try {
            $ret = $this->database->table("settings")->get(1); //number is ID in table settings
        } catch (Nette\InvalidArgumentException $e) {
            throw new Exceptions\SettingsNotExist;
        }
        return $ret;
    }


    
    /**
     * Get all sensors from database 
     * @return Nette\Database\Table\Selection
     */    
    public function getSensors()
    {
        return $this->database->table("sensors");
    }  

    /**
     * Get sensor with specific number
     * @param string $number
     * @return Exception|\Nette\Database\Table\ActiveRow
     */
    public function getSensorsNumber($number)
    {
        if(($out = $this->database->table("sensors")->where("number", $number )->fetch())==null)
        {
            throw new Exceptions\SensorNotExist;
        }
        return $out;
    }


    /**
     * Get count of rows in table
     * @param        $number
     * @return int count of rows
     */
    public function getCountSensors($number) :int
    {
        //$result =  $this->database->query('SELECT * FROM sensors WHERE ' . $column . ' = ?', $name);
        //return $result->getRowCount();
        return $this->database->table("sensors")->where("number = ?", $number)->count();
    }


    /**
     * Is sensor exist?
     * @param $number
     * @return bool
     */
    public function sensorIsExist($number) :bool
    {
        if($this->getCountSensors($number)>0)
            return 1;
        else
            return 0;
    }

    /**
     * Add new sensor
     * @param string $sensorName
     * @return bool
     */    
    public function addThisSensor($sensorName)
    {
        $this->database->query("CREATE TABLE $sensorName (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            state ENUM('FINISHED','STOP','REWORK', 'ON', 'OFF') NOT NULL DEFAULT 'FINISHED',
            time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
        return true;
    }

    /**
     * Rename sensor table
     * @param string $oldName
     * @param string $sensorName
     * @return bool 
     */ 
    public function renameThisSensor($oldName, $sensorName)
    {

        try{
            $this->database->query("ALTER TABLE $oldName
            RENAME TO $sensorName");    
        } catch (Nette\Database\DriverException $e) {
            //if sensor does no exist add new sensor to DB
            return $this->addThisSensor($sensorName); //
        }
        return true;
    }  
    
    /**
     * Delete sensor table
     * @param string $sensorName
     * @return \Nette\Database\ResultSet
     */     
    public function deleteThisSensor($sensorName)
    {
        return $this->database->query("DROP TABLE $sensorName");
    }


    /**
     * Add new sensor
     * @param int    $number machine number
     * @param string $description machine description (optional)
     * @return array $(bool - STATE,  string - CZ)
     */
    public function addNewSensor($number, $description = "")
    {

        if($this->sensorIsExist($number) )
        {
            return Pretty::return(false, "" , "Senzor s tímto číslem již existuje");
        }

        $this->addThisSensor($number);


        if($succes = $this->database->table("sensors")->insert([
            'number' => $number,
            'description' => $description,
        ]))
        {
            return Pretty::return(true, "" ,"Senzor byl vytvořen");
        }
        else
        {
            return Pretty::return(false, "" , "ERROR!!!");
        }        
        
    }

    /**
     * Delete sensor
     * @param $number
     * @return array $(bool - STATE,  string - CZ)
     */
    public function deleteSensor($number)
    {
        if(!$this->sensorIsExist($number) )
        {
            return Pretty::return(false, "" , "Senzor který chceš smazat neexistuje");
        }

        $count = $this->database->table("sensors")
            ->where('number', $number)
            ->delete();

        $this->deleteThisSensor($number);

        return Pretty::return(true, $count , "Senzor byl smazán");
    }

    /**
     * Edit sensor
     * @param        $oldNumber
     * @param int    $number machine number
     * @param string $description machine description (optional)
     * @return array $(bool - STATE,  string - CZ)
     * @throws Exceptions\SensorNotExist
     */
    public function editSensor($oldNumber, $number, $description = "")
    {
        if(!$this->sensorIsExist($number))
        {
            return Pretty::return(false, "" , "Senzor který chceš upravit neexistuje");
        }

        $oldSen = $this->getSensorsNumber($oldNumber);


        //Is not same?
        if(($oldSen->number!=$number)==true)
        {
            //Is exist?
            if($this->sensorIsExist($number, "number") )
            {
                return Pretty::return(false, "" , "Senzor s tímto číslem již existuje");
            }
        }


       

        $result = $this->database->query('UPDATE sensors  SET', [ 
            'number' => $number,
            'description' => $description,
        ], 'WHERE number = ?', $oldNumber);

        return Pretty::return(false, $result , "Senzor byl upraven");
    }
    


}
