<?php

namespace Mobbex\Webpay\Model\Resource;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * CustomField Resource Model
 * @package Mobbex\Webpay\Model\Resource
 */
class CustomField extends AbstractDb
{
    /**
     * Initialize resource
     */
    public function _construct()
    {
        $this->_init('mobbex_customfield', 'customfield_id');
    }
}