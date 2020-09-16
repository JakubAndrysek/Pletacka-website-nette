<?php

namespace App\CoreModule\Model;


use Exception;
use Nette;
use Nette\Database\Context;
use App\Utils\Pretty;
// use App\CoreModule\Exceptions;
use App\Exceptions;
use App\Exceptions\MyException;

/**
 * @brief Manage sensor with name
 */
class SensorsManager
{
	use Nette\SmartObject;

    private $database;
    
    public function __construct( Context $database )
    {
        $this->database = $database;
    }


    /**
     * @brief Get all settings from database
     * @return Exception|\Nette\Database\Table\ActiveRow
     * @throws Exceptions\SettingsNotExist
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
     * @brief Get all sensors from database
     * @return Nette\Database\Table\Selection
     */    
    public function getSensors()
    {
        return $this->database->table("sensors");
    }

    /**
     * @brief Get sensor with specific number
     * @param int $number machine number
     * @return Exception|\Nette\Database\Table\ActiveRow
     * @throws Exceptions\SensorNotExist
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
     * @brief Get count of rows in table
     * @param $number machine number
     * @return int count of rows
     */
    public function getCountSensors($number) :int
    {
        //$result =  $this->database->query('SELECT * FROM sensors WHERE ' . $column . ' = ?', $name);
        //return $result->getRowCount();
        return $this->database->table("sensors")->where("number = ?", $number)->count();
    }


    /**
     * @brief Is sensor exist?
     * @param $number machine number
     * @return bool
     */
    public function sensorIsExist($number) :bool
    {
        return $this->getCountSensors($number);

    }

    /**
     * @brief Add new sensor
     * @param int $sensorNumber machine number
     * @return bool create status
     */    
    public function addThisSensor($sensorNumber)
    {
        $sensorNumber = "A".$sensorNumber;
        
        $this->database->query("CREATE TABLE $sensorNumber (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            state ENUM('FINISHED','STOP','REWORK', 'ON', 'OFF') NOT NULL DEFAULT 'FINISHED',
            time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
        return true;
    }

    /**
     * @brief Rename sensor table
     * @param int $newNumber new machine number
     * @param int $oldNumber ols machine number
     * @return bool rename status
     */
    public function renameThisSensor($oldNumber, $newNumber)
    {
        $oldNumber = "A".$oldNumber;
        $newNumber = "A".$newNumber;
        try{
            $this->database->query("ALTER TABLE $oldNumber
            RENAME TO $newNumber");
        } catch (Nette\Database\DriverException $e) {
            //if sensor does no exist add new sensor to DB
            return $this->addThisSensor($newNumber); //
        }
        return true;
    }  
    
    /**
     * @brief Delete sensor table
     * @param int $sensorNumber machine number
     * @return \Nette\Database\ResultSet
     */     
    public function deleteThisSensor($sensorNumber)
    {
        $sensorNumber = "A".$sensorNumber;
        return $this->database->query("DROP TABLE $sensorNumber");
    }


    /**
     * @brief Add new sensor
     * @param int $number machine number
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


        if($success = $this->database->table("sensors")->insert([
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
     * @brief Delete sensor
     * @param $number machine number
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
     * @brief Edit sensor
     * @param int $oldNumber machine old number
     * @param int $number machine number
     * @param string $description machine description (optional)
     * @return array $(bool - STATE,  string - CZ)
     * @throws Exceptions\SensorNotExist
     */
    public function editSensor($oldNumber, $number, $description = "")
    {
        if($oldNumber != $number)
        {
            if($this->sensorIsExist($number))
            {
                return Pretty::return(false, "" , "Senzor který chceš upravit neexistuje");
            }

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



        $this->renameThisSensor($oldNumber, $number);


        $result = $this->database->query('UPDATE sensors  SET', [ 
            'number' => $number,
            'description' => $description,
        ], 'WHERE number = ?', $oldNumber);

        return Pretty::return(true, $result , "Senzor byl upraven");
    }
    


}