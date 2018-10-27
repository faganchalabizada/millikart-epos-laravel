<?php

namespace App\Http\Controllers;

use FaganChalabizada\EPOS\EPOS;
use FaganChalabizada\Wallet\WalletApi;
use Illuminate\Http\Request;
use Redirect;


class EPOSController extends Controller
{
    public function result()
    {
        $EPOS = new EPOS();

        $local_transaction_id = \Request::segment(4);

        //here you have to get EPOS transaction id via local transaction id.
        $Wallet = new WalletApi();
        $transaction_info = $Wallet->getUserTransaction(0, $local_transaction_id);

        if (empty($transaction_info)) {
            $errors[] = "Transaction not found.";
            return redirect("topUp/EPOS")->withErrors($errors);
        }


        if ($transaction_info[0]['transaction_status'] == 1) {
            $errors[] = "You balance has already been replenished.";
            return redirect("topUp/EPOS")->withErrors($errors);
        }

        $response = $EPOS->checkStatus($transaction_info[0]['transaction_code']);

        if ($EPOS->getResult($response)) {//request valid
            //change local transaction status to purchased, and also top up user balance
            $Wallet->activeInactiveTransaction($local_transaction_id, 1);
            return redirect("topUp/EPOS")->with(['success' => true]);
        }

        $errors[] = "Something went wrong. Please try again or contact with admin.";
        return redirect("topUp/EPOS")->withErrors($errors);

    }


    /**
     * Create transaction
     * @param $request
     * @return
     */
    public function createTransaction(Request $request)
    {

        $this->validate($request, [
            'amount' => 'required|numeric',
            'card_type' => 'required|numeric|max:3',
            'phone_number' => 'phone_number',
        ]);

        $amount = $request->input("amount");
        $card_type = $request->input("card_type");
        $phone_number = $request->input("phone_number") ?: "994044444444";

        $EPOS = new EPOS();

        $amount = round($amount, 2);

        $card_type = ($card_type == 0) ? $EPOS::VISA : $EPOS::MASTERCARD;

        //here you have to save transaction and get id. After send id in URL for result page.
        $Wallet = new WalletApi();

        $local_transaction_id = $Wallet->createTransaction(
            2,
            1,
            $amount,
            null,
            \Auth::user()->id,
            0
        );


        if ($local_transaction_id === false) {
            $errors[] = "Something went wrong. Please try again.";
            return Redirect::back()->withErrors($errors);
        }

        $response = $EPOS->getPaymentData(
            $amount,
            $phone_number,
            $card_type,
            url(str_replace("{id?}", $local_transaction_id, config("EPOS.result_url"))),
            url(config("EPOS.failed_url")),
            config("EPOS.form_type"),
            config("EPOS.currency"),
            ""
        );

        $result = $EPOS->getResult($response);
        if ($result == "success") {
            $payment_id = $EPOS->getPaymentID($response);
            $Wallet->editTransactionCode($local_transaction_id, $payment_id);

            return Redirect::to($EPOS->getPaymentUrl($response));
        }

        $errors[] = $EPOS->getErrorInfo($response);

        return Redirect::back()->withErrors($errors);
    }


    /**
     * EPOS top up balance page
     * @param $type
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex()
    {
        return view('topUp.EPOS.index');
    }


}
