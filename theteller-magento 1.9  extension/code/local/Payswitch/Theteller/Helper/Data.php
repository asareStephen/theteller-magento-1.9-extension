<?php
class Payswitch_Theteller_Helper_Data extends Mage_Core_Helper_Abstract
{

//for converting float to minor amounts
   public function floatToMinor($amount)
    {
        $amount= (float)$amount;
        $minor = "The number should be of type float";
        if(is_float($amount) || is_double($amount)) {
            $number = $amount * 100;
            $zeros = 12 - strlen($number);
            $padding = '';
            for($i=0; $i<$zeros; $i++) {
                $padding .= '0';
            }
            $minor = $padding.$number;
        }elseif (strlen($amount)==12) {

            $minor = $amount;
        }
        return $minor;
    }


   public function minorToFloat($amount)
    {
        $number = "The minor units should be numeric of type string";
        if (is_string($amount)) {
            $number = ( (int)$amount/100);
            //$number = round((float) $number, 2);
            $number = number_format( (float) $number, 2, '.','' );
            return $number;
        }

        return $number;
    }



    public function apimode($mode){

        if ($mode=='1'){
            $initialURL = 'https://test.theteller.net/checkout/initiate';
            return $initialURL;
        }elseif ($mode=='0'){
            $initialURL='https://prod.theteller.net/checkout/initiate';
            return $initialURL;
        }

    }



}


