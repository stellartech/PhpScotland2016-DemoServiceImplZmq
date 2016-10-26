<?php

namespace PhpScotland2016\Demo\Service\Impl\Zmq;

use PhpScotland2016\Demo\Service\Interfaces\DemoServiceRequest;
use PhpScotland2016\Demo\Service\Interfaces\DemoServiceResponse;
use PhpScotland2016\Demo\Service\Interfaces\DemoServiceInterface;

class DemoServiceZmqProducer implements DemoServiceInterface
{
	public function handleRequest(DemoServiceRequest $request) {
		$context = new \ZMQContext();
		try {
			$session_id = $request->getParam("session_id", null);
			if(is_null($session_id)) {
				throw new \Exception("No sessionid provided");
			}
			$conn = "tcp://".$_ENV["ZMQ_BROKER"].":" . $_ENV["ZMQ_BROKER_FRONT_PORT"];
			$zmq = $context->getSocket(\ZMQ::SOCKET_REQ, null);
			$zmq->connect($conn);
			$zmq->send($request->get(), \ZMQ::MODE_NOBLOCK);
			$res = $zmq->recv();
			$times--;
			$counter++;
		}
		catch(\Exception $e) {
			error_log("Exception: ". $e->getMessage());
		}
		
		$message = $request->getAsArray();
		$message['result'] = 0;
		$message['msg'] = 'See websocket response';
		return new DemoServiceResponse($message);
	}
}

