<?php

namespace PhpScotland2016\Demo\Service\Impls\Zmq;

use PhpScotland2016\Demo\Service\Interfaces\DemoServiceRequest;
use PhpScotland2016\Demo\Service\Interfaces\DemoServiceResponse;

class DemoServiceZmqConsumer 
{
	protected $_context = null;
	protected $_push = null;
	protected $_pull = null;
	protected $_run  = true;

	public function __construct(/* ToDo, DI here. PRs gladly accepted :) */) {
		$this->_context = new \ZMQContext(1, true);
		$conn = "tcp://" .  $_ENV["ZMQ_BROKER"] .":". $_ENV["ZMQ_BROKER_BACK_PORT"];
		$this->log("Connecting to $conn");
		$this->_pull = $context->getSocket(\ZMQ::SOCKET_PULL, null);
		$this->_pull->connect($conn);
		$conn = "tcp://" .  $_ENV["CROSSBAR_HOST"] .":". $_ENV["CROSSBAR_ZMQ_PULL_PORT"];
		$this->log("Connecting to $conn");
		$this->_push = $context->getSocket(\ZMQ::SOCKET_PUSH, null);
		$this->_push->connect($conn);
		if(extension_loaded("pcntl")) {
			pcntl_signal(SIGTERM, function($signo) {
				$this->_run = false;
			});
		}
	}

	public function execute() {
		while($this->_run) {
			try {
				$request = new DemoServiceRequest($this->recv());
				$response = $this->handleRequest($request);
				$this->send($response);
			}
			catch(\Exception $e) {
				$this->log($e->getMessage());
			}
		}
		$this->log("Terminating");
	}

	private function handleRequest(DemoServiceRequest $request) {
		$service = new DemoServiceLocal;
		return $service->handleRequest($request);
	}

	private function recv() {
		$json = $this->_pull->recv(\ZMQ::MODE_NOBLOCK);
		if(!is_string($json)) {
			usleep(10); // No CPU 100% please.
		}
		else {
			$this->log("RX:".$json);
		}
		return $json;
	}

	private function send(DemoServiceResponse $response) {
		$this->log("TX:".$response->getJson());
		$this->_push->send($response->getJson(), \ZMQ::MODE_NOBLOCK);
	}

	private function log($in) {
		if(isset($_ENV["VERBOSE"]) && (int)$_ENV["VERBOSE"] == 1) {
			error_log($in);
		}
	}
}

