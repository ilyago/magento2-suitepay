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

    protected $_api_key = null;
    protected $_api_login = null;
    protected $_developerid = null;
    protected $_mid = null;
    protected $_sandbox = null;

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
        array $some = array(),
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
            null,
            $data
        );

        $this->_countryFactory = $countryFactory;
        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
        $this->_api_key = $this->getConfigData('api_key');
        $this->_api_login = $this->getConfigData('api_login');
        $this->_developerid = $this->getConfigData('developerid');
        $this->_mid = $this->getConfigData('mid');
        $this->_sandbox = $this->getConfigData('sandbox');
    }



    //right now this function has sample code only, you need put code here as per your api.
    private function callApi($apidata, $type){



        $json_data = json_encode($apidata);

        //echo "$json_data<br>";
        $sandbox = ($this->_sandbox) ? 'qa':'gateway';
        $curlURL = "https://{$sandbox}.suitepay.com/api/v2/card/{$type}/";    // qa.suitepay.com/parmeters for testing and api.suitepay.com/parameters for the live

        $ch = curl_init($curlURL);
         
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1.2');
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
         
        $response = curl_exec($ch);
        $arresult = json_decode($response,true);

        curl_close($ch);
 
        return $arresult;
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
        //throw new \Magento\Framework\Validator\Exception(__('Inside Platform, throwing coins :]'));

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();

        $apidata = array (
        'user_login' => $this->_api_login,
        'public_key' => $this->_api_key,
        'developerid' => $this->_developerid,
        'transaction_data' => array (
                        'mid' => $this->_mid,
                        'orderid' => $order->getIncrementId(),    /// must be a unique number each time a sale is done
                        'amount' => $amount,     

                        'cardfullname' => $billing->getName(),
                        'creditcard' => $payment->getCcNumber(),
                        'cvv' => $payment->getCcCid(),
                        'month' => sprintf('%02d',$payment->getCcExpMonth()),
                        'year' => $payment->getCcExpYear(),

                        'baddress' => $billing->getStreetLine(1),
                        'baddress2' => $billing->getStreetLine(2),
                        'bcity' => $billing->getCity(),
                        'bstate' => $billing->getRegion(),
                        'bzip' => $billing->getPostcode(),
                        'bcountry' => $billing->getCountryId(),

                        'cemail' => $order->getCustomerEmail(),
                        'cphone' => $billing->getTelephone(),
                        'ipaddress' => $_SERVER['REMOTE_ADDR']
                )
        );

        $this->_logger->addInfo( json_encode($apidata) );

        try {            
            $charge = $this->callApi($apidata,'sale'); 
            //$charge = json_decode('{"status":"approved","message":"Card Sale processed successfully.","transaction_id":"bTUXpn1YgjMT5Cb","customervault_id":null,"code":"[0000]The transaction has been accepted."}',true);

            if($charge["status"] == "approved") {
                
                $payment->setTransactionId($charge['transaction_id']);
                $payment->setIsTransactionClosed(1);

            } else {
                // Add the comment and save the order
                $this->_logger->error(__($charge['code']));
                throw new \Magento\Framework\Validator\Exception(__( $charge['message'] ));
            }
        } catch (\Exception $e) {
            $this->debugData(['request' => $apidata, 'exception' => $e->getMessage()]);
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