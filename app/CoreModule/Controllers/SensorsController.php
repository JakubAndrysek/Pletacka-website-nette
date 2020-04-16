<?php declare(strict_types = 1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\CoreModule\Model\SensorManager;

use Apitte\Core\Exception\Api\MessageException;

/**
 * @ControllerPath("/sensors")
 */
final class SensorsController extends BaseV1Controller
{
	private $sensorManager;

	private $defaultArticleUrl;
	
	public function __construct(SensorManager $sensorManager)
	{
		$this->sensorManager = $sensorManager;
	}


	/**
	 * @Path("/")
	 * @Method("GET")
	 */
	public function sensors(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$aSensors = array();
        $sensors = $this->sensorManager->getSensors();
        foreach($sensors as $sensor)
        {
            $aSensors[] = array('number'=>$sensor->number, 'name'=>$sensor->name, 'description'=>$sensor->description);
        }

        //$xout = array('sensors'=>$aSensors);
		return $response->writeJsonBody($aSensors);
	}

	/**
	 * @Path("/number/{number}")
	 * @Method("GET")
	 */
	public function sensorsNumber(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$number = $request->getParameter('number');
		if($this->sensorManager->sensorIsExist($number, 'number'))
		{
			$sensor = $this->sensorManager->getSensorsNumber($number);
			return $response->writeJsonBody(array('number'=>$sensor->number, 'name'=>$sensor->name, 'description'=>$sensor->description));
		}
		else
		{
			$error = [
				'status' => 'error',
				'code' => 404,
				'message' => 'Sensor with number '. $number .' does not exist.',
			];
			return $response->writeJsonBody($error);
		}
		

	}



	/**
	 * @Path("/name/{name}")
	 * @Method("GET")
	 */
	public function sensorsName(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		
		$name = $request->getParameter('name');
		dump($request->getParameters());
		if($this->sensorManager->sensorIsExist($name, 'name'))
		{
			$sensor = $this->sensorManager->getSensorsName($name);
			return $response->writeJsonBody(array('number'=>$sensor->number, 'name'=>$sensor->name, 'description'=>$sensor->description));
		}
		else
		{
			throw MessageException::create()
			->withCode(405)
			->withMessage("Sensor with name ". $name . " does not exist.");
		}
	}
	
	
	/**
	 * @Path("/create")
	 * @Method("POST")
	 */
	public function create(ApiRequest $request): array
	{
		$post = $request->getJsonBody();
		dump($post);
		echo $post['number'];
		$returnMessage = $this->sensorManager->addNewSensor($post['number'], $post['name'], $post['description']);
		if($returnMessage[0])
		{
			$returnMessage[2];

		}
		else
		{
			
			throw MessageException::create()
			->withCode(405)
			->withMessage("Sensor with name ". $name . " does not exist.");
		}  
		return ['data' => [1,2,3], "asd"=>55];
	}	


	/**
	 * @Path("/ping")
	 * @Method("GET")
	 */
	public function scalar(): string
	{
		return strval( $this->defaultArticleUrl);//'pong';
	}

	/**
	 * @Path("/create2")
	 * @Method("POST")
	 */
	public function create2(ApiRequest $request): array
	{
		$post = $request->getJsonBody();
		dump($post);
		echo $post['number'];

		return ['data' => [
			// 'raw' => $request->getContentsCopy(),
			// 'parsed' => $request->getParsedBody(),
			// 'sen' => $request->withParsedBody(""),
			'jsonbody' => $request->getJsonBody(),
			'asd' => $this->defaultArticleUrl
		]];
	}	

	


}
