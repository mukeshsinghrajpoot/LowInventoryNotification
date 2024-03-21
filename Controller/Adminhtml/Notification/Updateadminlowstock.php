<?php

namespace Bluethinkinc\LowInventoryNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Bluethinkinc\LowInventoryNotification\Cron\Synclowinventorynotification;

class Updateadminlowstock extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Synclowinventorynotification
     */
    protected $synclowinventorynotification;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Synclowinventorynotification $synclowinventorynotification
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Synclowinventorynotification $synclowinventorynotification
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->synclowinventorynotification = $synclowinventorynotification;
        parent::__construct($context);
    }

    /**
     * Get message data
     *
     * @return \Magento\Framework\App\ResponseInterface|Json|
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $message=$this->synclowinventorynotification->execute();
        $result = $this->resultJsonFactory->create();
        return $result->setData($message);
    }
}
