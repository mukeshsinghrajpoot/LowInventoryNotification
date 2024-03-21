<?php

namespace Bluethinkinc\LowInventoryNotification\Cron;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Bluethinkinc\LowInventoryNotification\Helper\Data;

class Synclowinventorynotification
{
    /**
     * This is a helperData
     *
     * @var helperData $helperData
     */
    private $helperData;
    /**
     * This is a construct
     *
     * @param Data $helperData
     */
    public function __construct(
        Data $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * This Is Execute function
     *
     * @return execute
     */
    public function execute()
    {
        try {
            $data = $this->helperData->synclowinventorynotification();
        } catch (Exception  $e) {
            $message =$e->getMessage();
            $error = __('true');
            $data=['response' => $message, 'error'=>$error];
        }
        return $data;
    }
}
