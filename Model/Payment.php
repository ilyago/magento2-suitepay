<?php
/**
 * Platform payment method model
 *
 * @category    Suitepay
 * @package     Suitepay_Platform
 * @author      Ilya Gokadze
 * @copyright   Suitepay (http://suitepay.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Suitepay\Platform\Model;

class Payment extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'suitepay_platform';

    protected $_code = self::CODE;

    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = false;
    protected $_canRefundInvoicePartial     = false;

    protected $_countryFactory;

    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = array('USD');

    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            $data
        );

        $this->_countryFactory = $countryFactory;
        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
    }





    //right now this function has sample code only, you need put code here as per your api.
    private function callApi(Varien_Object $payment, $amount, $type){
 
        //call your authorize api here, incase of error throw exception.
        //only example code written below to show flow of code

        /*
        $order = $payment->getOrder();
        $billingaddress = $order->getBillingAddress();
        $totals = number_format($amount, 2, '.', '');
        $orderId = $order->getIncrementId();
        $currencyDesc = $order->getBaseCurrencyCode();
        $url = $this->getConfigData('gateway_url');
        $fields = array(
                'api_username'=> $this->getConfigData('api_username'),
                'api_password'=> $this->getConfigData('api_password'),
                'customer_firstname'=> $billingaddress->getData('firstname'),
                'customer_lastname'=> $billingaddress->getData('lastname'),
                'customer_phone'=> $billingaddress->getData('telephone'),
                'customer_email'=> $billingaddress->getData('email'),
                'customer_ipaddress'=> $_SERVER['REMOTE_ADDR'],
                'bill_firstname'=> $billingaddress->getData('firstname'),
                'bill_lastname'=> $billingaddress->getData('lastname'),
                'Bill_address1'=> $billingaddress->getData('street'),
                'bill_city'=> $billingaddress->getData('city'),
                'bill_country'=> $billingaddress->getData('country_id'),
                'bill_state'=> $billingaddress->getData('region'),
                'bill_zip'=> $billingaddress->getData('postcode'),
                'customer_cc_expmo'=> $payment->getCcExpMonth(),
                'customer_cc_expyr'=> $payment->getCcExpYear(),
                'customer_cc_number'=> $payment->getCcNumber(),

                'customer_cc_cvc'=> $payment->getCcCid(),
                'merchant_ref_number'=> $order->getIncrementId(),
                'currencydesc'=>$currencyDesc,
                'amount'=>$totals
        );
 
        $fields_string="";
        foreach($fields as $key=>$value) {
        $fields_string .= $key.'='.$value.'&';
        }
        $fields_string = substr($fields_string,0,-1);
        //open connection
        $ch = curl_init($url);
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,1);
        curl_setopt($ch, CURLOPT_HEADER ,0); // DO NOT RETURN HTTP HEADERS
        curl_setopt($ch, CURLOPT_RETURNTRANSFER ,1); // RETURN THE CONTENTS OF THE CALL
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // Timeout on connect (2 minutes)
        //execute post
        $result = curl_exec($ch);
        curl_close($ch);
        */
 
        return array('status'=>1,'transaction_id' => time() , 'fraud' => rand(0,1));
    }








    /**
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //throw new \Magento\Framework\Validator\Exception(__('Inside Platform, throwing donuts :]'));

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();

        try {
            $requestData = [
                'amount'        => $amount * 100,
                'currency'      => strtolower($order->getBaseCurrencyCode()),
                'description'   => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
                'card'          => [
                    'number'            => $payment->getCcNumber(),
                    'exp_month'         => sprintf('%02d',$payment->getCcExpMonth()),
                    'exp_year'          => $payment->getCcExpYear(),
                    'cvc'               => $payment->getCcCid(),
                    'name'              => $billing->getName(),
                    'address_line1'     => $billing->getStreetLine(1),
                    'address_line2'     => $billing->getStreetLine(2),
                    'address_city'      => $billing->getCity(),
                    'address_zip'       => $billing->getPostcode(),
                    'address_state'     => $billing->getRegion(),
                    'address_country'   => $billing->getCountryId(),
                    // To get full localized country name, use this instead:
                    // 'address_country'   => $this->_countryFactory->create()->loadByCode($billing->getCountryId())->getName(),
                ]
            ];

            $charge = $this->callApi($payment,$amount,'authorize'); //$requestData


            if($charge === false) {

            } else {
     
                if($charge['status'] == 1){
                    $payment->setTransactionId($charge['transaction_id']);
                    $payment->setIsTransactionClosed(1);
                }else{

                }
     
                // Add the comment and save the order
            }

        } catch (\Exception $e) {
            $this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment capturing error.'));
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
        }


        return $this;
    }











    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && (
            $quote->getBaseGrandTotal() < $this->_minAmount
            || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        if (!$this->getConfigData('api_key')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Availability for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }
}