<?php

/*
 * Copyright (c) 2016 ricky <https://github.com/rickyepoderi/runnerupweb>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace runnerupweb\data;

/**
 * General response for json encoding.
 *
 * @author ricky
 */
class LoginResponse implements \JsonSerializable {
    
    private $status;
    private $errorCode;
    private $errorMesg;
    private $extra;
    
    protected function __construct($status, $errorCode = null, $errorMesg = null) {
        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->errorMesg = $errorMesg;
    }
    
    /**
     * Constructor for a OK response.
     * @return LoginResponse
     */
    static public function responseOk() {
        $response = new LoginResponse(true);
        return $response;
    }
    
    /**
     * Constructor of a response reading json data.
     * @param string $json
     * @return LoginResponse
     */
    public static function responseWithJson($json) {
        $val = json_decode($json, true);
        $response = new LoginResponse(false);
        $response->fromJson($val);
        return $response;
    }
    
    /**
     * Method that read the values from assoc
     * @param mixed $val 
     * @return void 
     */
    public function fromJson($val) {
        $this->status = $val['status'] == 'SUCCESS';
        $this->errorCode = isset($val['errorCode'])? $val['errorCode']:null;
        $this->errorMesg = isset($val['errorMessage'])? $val['errorMessage']:null;
    }
    
    /**
     * Constructor for a response that is a KO.
     * @param string $errorCode The error code
     * @param string $errorMesg The error message
     * @return LoginResponse
     */
    static public function responseKo($errorCode, $errorMesg) {
        $response = new LoginResponse(false, $errorCode, $errorMesg);
        return $response;
    }
    
    /**
     * Wheteher the response is OK or KO.
     * @return bool true if ok, false otherwise
     */
    public function isSuccess() {
        return $this->status;
    }
    
    /**
     * Getter for the error code.
     * @return int
     */
    public function getErrorCode() {
        return $this->errorCode;
    }
    
    /**
     * Getter for the message.
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMesg;
    }

    /**
     * Setter for extra
     * @param type $extra
     * @return void
     */
    public function setExtra($extra): void {
        $this->extra = $extra;
    }

    /**
     * Getter for extra
     * @return type
     */
    public function getExtra() {
        return $this->extra;
    }
    
    /**
     * return the XML representation for runnerup.
     * @return string
     */
    public function toXml() {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startElement("response");
        if ($this->status) {
            $writer->writeElement("result", "success");
        } else {
            $writer->startElement("error");
            $writer->writeAttribute("code", $this->errorCode);
            $writer->writeAttribute("message", $this->errorMesg);
            $writer->endElement();
        }
        $writer->endElement(); // response
        return $writer->outputMemory();
    }

    /**
     * 
     * @return mixed
     */
    public function jsonSerialize() {
        $res = ['status' => $this->status? 'SUCCESS':'FAILURE'];
        if (!$this->status) {
            $res['errorCode'] = $this->errorCode;
            $res['errorMessage'] = $this->errorMesg;
        }
        return $res;
    }

}
