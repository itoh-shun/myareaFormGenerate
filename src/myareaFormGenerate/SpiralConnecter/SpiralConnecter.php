<?php

namespace SiLibrary\SpiralConnecter;

use Exception;

class SpiralConnecter implements SpiralConnecterInterface
{
    private $apiCommunicator;

    public function __construct($spiral)
    {
        $this->apiCommunicator = $spiral->getSpiralApiCommunicator();
    }

    public function request(
        XSpiralApiHeaderObject $header,
        HttpRequestParameter $httpRequestParameter,
        array $files = []
    ) {
        if(!class_exists('SpiralApiRequest')) {
            throw new \LogicException('Not SpiralApiRequest Class');
        }

        $request = new \SpiralApiRequest();

        foreach ($httpRequestParameter->toArray() as $key => $val) {
            $request->put($key, $val);
        }

        $response = $this->apiCommunicator->request(
            $header->func(),
            $header->method(),
            $request
        );

        if ($response->getResultCode() != 0) {
            throw new Exception(
                $response->getMessage(),
                $response->getResultCode()
            );
        }
        return $response->entrySet();
    }

    public function bulkRequest(
        XSpiralApiHeaderObject $header,
        array $httpRequestParameters
    ) {
        $result = [];
        $log = [];
        foreach ($httpRequestParameters as $key => $httpRequestParameter) {
            if ($httpRequestParameter instanceof HttpRequestParameter) {
                $res = $this->request($header, $httpRequestParameter);
                array_merge($result, $res);
            }
        }

        return $result;
    }
}
