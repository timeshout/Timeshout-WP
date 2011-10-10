<?php
/**
 * This class allows access to the Timeshout (http://timeshout.com) API using the REST interface using CURL
 *
 * Requirements: PHP5 with curl enabled
 * License: MIT
 */
class Timeshout
{

    const VERSION = 0.1;

    const STATUS_INVALID_XML = 206;

    protected $baseUrl = 'http://api.timeshout.com/';

    protected $method;

    protected $query;

    protected $response = false;

    protected $errorMessages = array();

    protected $protocol = 'GET';

    protected $xmlBody;

    protected $xmlObject;

    protected $params = array();

    protected $statusCode;

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setProtocol($protocol)
    {
        $protocol = strtoupper($protocol);
        if(in_array($protocol, array('GET', 'POST', 'PUT', 'DELETE')))
            $this->protocol = $protocol;
    }

    public function setParameters($params)
    {
        $this->params = $params;
    }

    public function setResponse($data)
    {
        $this->response = $data;

        $this->setStatusCode();
        $this->setXmlBody();
        $this->setXmlObject();
    }

    public function processRequest()
    {
        switch($this->protocol)
        {
            case 'GET':
                $session = curl_init($this->baseUrl.$this->method.'?'.$this->getQuery());
                break;

            case 'POST':
                $session = curl_init($this->baseUrl.$this->method);
                curl_setopt ($session, CURLOPT_POST, true);
                curl_setopt ($session, CURLOPT_POSTFIELDS, $this->getQuery());
                break;
        }
        curl_setopt($session, CURLOPT_HEADER, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $this->response = curl_exec($session);
        curl_close($session);
        $this->setStatusCode();
        $this->setXmlBody();
        $this->setXmlObject();
    }

    protected function setQuery()
    {
        $encoded = array();

        foreach ($this->params as $k => $v)
        {
            $value = is_array($v) ? implode(',', $v) : $v;
            $encoded[] = $k.'='.urlencode($value);
        }
        $this->query = implode('&', $encoded);

    }
    public function getQuery()
    {
        if($this->query == null)
            $this->setQuery();
        return $this->query;
    }

    public function setStatusCode()
    {
        $codes = array();
        preg_match('/\d\d\d/', $this->response, $codes);
        $this->statusCode = $codes[0];
    }

    protected function setXmlBody()
    {
        if (!($this->xmlBody = strstr($this->response, '<?xml')))
               $this->xmlBody = null;
    }

    protected function setXmlObject()
    {
        $this->xmlObject = simplexml_load_string($this->xmlBody);
    }

    public function getXmlObject()
    {
        return $this->xmlObject;
    }
    public function getXmlBody()
    {
        return isset($this->xmlBody) ? $this->xmlBody : false;
    }
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    public function hasError()
    {
        return $this->statusCode != '200';
    }

    public function getErrorMessages()
    {
        //if we have xml, return err tags
        if(isset($this->xmlObject))
        {
            if(isset($this->xmlObject->err))
            {
                foreach($this->xmlObject->err as $error)
                    $this->errorMessages[] = $error['msg'];
            }
        }
        return $this->errorMessages;
    }

    public function getResponse()
    {
        return $this->response;
    }



}


?>