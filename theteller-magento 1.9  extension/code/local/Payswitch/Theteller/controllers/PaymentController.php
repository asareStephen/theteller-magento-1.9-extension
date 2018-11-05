<?php

class Payswitch_Theteller_PaymentController extends Mage_Core_Controller_Front_Action {
	// The redirect action is triggered when someone places an order
	public function redirectAction() {
        //$configValue = Mage::getStoreConfig('sectionName/groupName/fieldName');
        $live_mode = Mage::getStoreConfig('payment/theteller/live_mode');
        $api_key = Mage::getStoreConfig('payment/theteller/api_key');
        $merchant_id = Mage::getStoreConfig('payment/theteller/merchant_id');
        $api_user = Mage::getStoreConfig('payment/theteller/api_user');
        $redirect_url = Mage::getStoreConfig('payment/theteller/redirect_url');
        $description = Mage::getStoreConfig('payment/theteller/description');


        if ($live_mode=='1'){
            $initialURL = 'https://test.theteller.net/checkout/initiate';
            //return $initialURL;
        }elseif ($live_mode=='0'){
            $initialURL='https://prod.theteller.net/checkout/initiate';
            //return $initialURL;
        }


        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());

        $amount = round($order->getGrandTotal(), 2);
       $minor = '';
        if (is_float($amount) || is_double($amount)) {
            $number = $amount * 100;
            $zeros = 12 - strlen($number);
            $padding = '';
            for ($i = 0; $i < $zeros; $i++) {
                $padding .= '0';
            }
            $minor = $padding . $number;

        }


        $rand = '000';
        $data = array(
            "merchant_id" => $merchant_id,
            "transaction_id" => $rand . $order->getRealOrderId(),
            "desc" => $description,
            "amount" => $minor,
            "email" => $order->getCustomerEmail(),
            "redirect_url" =>$redirect_url .  "/theteller/Payment/response"
        );


        $json_data = json_encode($data);

        $curl = curl_init();

// Definition of request's headers
        curl_setopt_array($curl, array(
            CURLOPT_URL => $initialURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "json",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic ".base64_encode("$api_user:$api_key"),
                "cache-control: no-cache",
                "content-type: application/json"

            ),
            CURLOPT_POSTFIELDS => $json_data,
        ));

// Send request and show response
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


        if ($err) {
            echo "API Error #:" . $err;
        } else {

            //echo $response;
            $response = json_decode($response, true);

            if ($response['code']=='200'){

                $tocheckoutURL = $response['checkout_url'];
                Mage::getSingleton( 'customer/session' )->setData( 'tocheckoutURL', $tocheckoutURL );

                //$this-> _redirect($tocheckoutURL, $arguments=array());
                $this->loadLayout();
                $block = $this->getLayout()->createBlock('Mage_Core_Block_Template','theteller',array('template' => 'theteller/redirect.phtml'));
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();

            }else{
                print_r($response);


            }


        }

	}



    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }







    // The response action is triggered when your gateway sends back a response after processing the customer's payment
	public function responseAction() {
		if($this->getRequest()->isGet()) {
			
			/*
			/* Your gateway's code to make sure the reponse you
			/* just got is from the gatway and not from some weirdo.
			/* This generally has some checksum or other checks,
			/* and is provided by the gateway.
			/* For now, we assume that the gateway's response is valid
			*/



			$code1 = $this->getRequest()->get("code");
			$status =$this->getRequest()->get("status");
			$transaction_id =$this->getRequest()->get("transaction_id");


			$orderId = substr($transaction_id,3); // Generally sent by gateway
            if ($code1=='000'){
                $validated = true;
            }elseif ($code1=='900'){
                $validated = false;
            }else{
                $validated = false;
            }

			if($validated) {
				// Payment was successful, so update the order's state, send order email and move to the success page
				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($orderId);
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');
				
				$order->sendNewOrderEmail();
				$order->setEmailSent(true);
				
				$order->save();
			
				Mage::getSingleton('checkout/session')->unsQuoteId();
				
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));
			}
			else {
				// There is a problem in the response we got
				$this->cancelAction();

                $this->_redirect('checkout/cart');
				//Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
			}
		}
		else
			Mage_Core_Controller_Varien_Action::_redirect('');
	}
	
	// The cancel action is triggered when an order is to be cancelled
	public function cancelAction() {
        if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if($order->getId()) {
				// Flag the order as 'cancelled' and save it
				$order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
			}
        }
	}
}