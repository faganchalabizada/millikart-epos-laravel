<?php

namespace FaganChalabizada\EPOS;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \Curl\Curl as Curl;

class EPOS
{

    /**
     * KEYS
     */
    private $public_key;
    private $private_key;

    /**
     * URL
     */
    private $epos_base_link;
    private $epay_pay_link;
    private $epay_check_status_link;
    private $epay_reverse_link;

    //CARD TYPES
    const VISA = 0;
    const MASTERCARD = 1;
    const BOLKART = 2;

    private $currency;
    private $form_type;

    //methods
    const PAY_METHOD = "pay";
    const STATUS_METHOD = "status";
    const REVERSAL_METHOD = "pay2me/reversal";


    function __construct()
    {
        $this->public_key = config("EPOS.public_key");
        $this->private_key = config("EPOS.private_key");
        $this->epay_base_link = config("EPOS.epos_base_link");
        $this->currency = config("EPOS.currency");
        $this->form_type = config("EPOS.form_type");

        $this->epay_pay_link = $this->epay_base_link . self::PAY_METHOD;
        $this->epay_check_status_link = $this->epay_base_link . self::STATUS_METHOD;
        $this->epay_reverse_link = $this->epay_base_link . self::REVERSAL_METHOD;


    }

    public function getPaymentData($amount, $phone, $card_type, $success_url, $error_url, $pay_form_type = "MOBILE", $currency = "AZN", $description = "Chalabizada")
    {

        $params = [
            'key' => $this->public_key,
            'amount' => $amount,
            'phone' => $phone,
            'cardType' => $card_type,
            'taksit' => 0,
            'email' => "",
            'description' => $description,
            'successUrl' => $success_url,
            'errorUrl' => $error_url,
            'payFormType' => $pay_form_type,
            'currency' => $currency,
        ];

        $control_sum = $this->getControlSum($params);

        $params['sum'] = $control_sum;

        $curl = new Curl();
        $curl->post($this->epay_pay_link, $params);

        return $curl->response;
    }

    public function getResult($response)
    {
        return $response->result;
    }

    public function getPaymentUrl($response)
    {
        return $response->paymentUrl;
    }

    public function getPaymentID($response)
    {
        return $response->id;
    }

    public function getErrorInfo($response)
    {
        return $response->info;
    }

    public function checkStatus($payment_id)
    {

        $params = [
            "key" => $this->public_key,
            "id" => $payment_id,
        ];

        $sum = $this->getControlSum($params);

        $params['sum'] = $sum;

        $curl = new Curl();
        $curl->post($this->epay_check_status_link, $params);

        return $curl->response;
    }

    private function getControlSum($params)
    {

        ksort($params);
        $sum = '';
        foreach ($params as $k => $v) {
            $sum .= (string)$v;
        }
        $sum .= $this->private_key;
        $control_sum = md5($sum);

        return $control_sum;
    }


}

