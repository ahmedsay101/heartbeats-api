<?php
class Res {
    private $success, $statusCode, $data;
    private $msg = "";
    private $response = array();

    public function __construct($success, $statusCode, $msg = "", $data = "") {
        $this->setSuccess($success);
        $this->setStatusCode($statusCode);
        $this->addMsg($msg);
        $this->setData($data);
        $this->send();
    }

    public function setSuccess($success) {
        $this->success = $success;
    }
    public function setStatusCode($code) {
        $this->statusCode = $code;
    }
    public function addMsg($msg) {
        $this->msg = $msg;
    }
    public function setData($data) {
        $this->data = $data;
    }

    public function send() {
        header('content-type: application/json;charset=utf-8');
        http_response_code($this->statusCode);
        $this->response['statusCode'] = $this->statusCode;
        $this->response['success'] = $this->success;
        $this->response['msg'] = $this->msg;
        $this->response['data'] = $this->data;

        echo json_encode($this->response);
        exit;
    }
}
?>
