<?php

namespace Mobbex\Webpay\Controller\Payment;

use Exception;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Mobbex\Webpay\Helper\Data;
use Mobbex\Webpay\Model\OrderUpdate;
use Psr\Log\LoggerInterface;

/**
 * Class PaymentReturn
 * @package Mobbex\Webpay\Controller\Payment
 */
class PaymentReturn extends Action
{
    /**
     * @var Context
     */
    public $context;

    /**
     * @var InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var Transaction
     */
    protected $_transaction;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var OrderUpdate
     */
    protected $_orderUpdate;

    /**
     * PaymentReturn constructor.
     * @param Context $context
     * @param InvoiceService $_invoiceService
     * @param Order $_order
     * @param Transaction $_transaction
     * @param Cart $cart
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     * @param OrderUpdate $orderUpdate
     */
    public function __construct(
        Context $context,
        InvoiceService $_invoiceService,
        Order $_order,
        Transaction $_transaction,
        Cart $cart,
        Session $checkoutSession,
        LoggerInterface $logger,
        OrderUpdate $orderUpdate
    ) {
        $this->_invoiceService = $_invoiceService;
        $this->_transaction = $_transaction;
        $this->_order = $_order;
        $this->context = $context;
        $this->_orderUpdate = $orderUpdate;

        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;

        $this->log = $logger;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws Exception
     */
    public function execute()
    {
        try {
            // Get data
            $orderId = $this->getRequest()->getParam('order_id');
            $status = $this->getRequest()->getParam('status');

            $this->log->debug('Return Controller > Data', [
                "id" => $orderId,
                "status" => $status,
            ]);

            // if data looks fine
            if (isset($orderId)) {
                // Get Order
                $order = $this->_order->loadByIncrementId($orderId);

                $this->log->debug('Return Controller > Order', $this->_order->debug());

                if ($status == 2 || $status == 3 || $status == 4 || $status == 100 || $status >= 200 && $status < 400) {
                    $this->_redirect('checkout/onepage/success');
                } else {
                    $this->restoreCart($order);
                    $this->_redirect('checkout/cart');
                }

            } else {
                $this->_redirect('home');
                Data::log('Payment Return called without order id', "mobbex_error_" . date('m_Y') . ".log");
            }
        } catch (Exception $e) {
            Data::log($e->getMessage(), "mobbex_error_" . date('m_Y') . ".log");
        }
    }

    /**
     * @param $order
     */
    private function restoreCart($order)
    {
        //Get Object Manager Instance
        $objectManager = ObjectManager::getInstance();

        $quote = $objectManager->create('\Magento\Quote\Model\QuoteFactory')->create()->load($order->getQuoteId());

        $this->log->debug('Return Controller > Quote', $quote->debug());

        $quote->setReservedOrderId(null);
        $quote->setIsActive(true);
        $quote->removePayment();
        $quote->save();

        $this->checkoutSession->replaceQuote($quote);
        $this->cart->setQuote($quote);

        $this->checkoutSession->restoreQuote();
    }
}