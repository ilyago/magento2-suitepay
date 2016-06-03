<?php
/**
 * Payment CC Types Source Model
 *
 * @category    Suitepay
 * @package     Suitepay_Platform
 * @author      Ilya Gokadze
 * @copyright   Suitepay (http://suitepay.com)
 */

namespace Suitepay\Platform\Model\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB', 'OT');
    }
}
