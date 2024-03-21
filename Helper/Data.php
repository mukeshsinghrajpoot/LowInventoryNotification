<?php
namespace Bluethinkinc\LowInventoryNotification\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bluethinkinc\LowInventoryNotification\Helper\Email;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as Configurationvalue;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const NOTIFY_ENABLE_DISABLE ='bluethink_admin_low_inventory_notification/general/enable';
    /**
     * This is a storemanager
     *
     * @var storeManager $storeManager
     */
    private $storeManager;
    /**
     * This is a scopeConfig
     *
     * @var scopeConfig $scopeConfig
     */
    protected $scopeConfig;
    /**
     * @var Email
     */
    private $helperEmail;
    /**
     * @var Configurationvalue
     */
    private $Configurationvalue;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;
    /**
     * This is a construct
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Email $helperEmail
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param Configurationvalue $Configurationvalue
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Email $helperEmail,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        Configurationvalue $Configurationvalue
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->helperEmail = $helperEmail;
        $this->collectionFactory = $collectionFactory;
        $this->Configurationvalue = $Configurationvalue;
    }

   /**
    * Generates getAllstore and wbsite email get
    *
    * @param pathVariable $pathVariable
    * @param unique $unique
    */
    public function sendtoemail($pathVariable, $unique = false)
    {
        $configValue=[];
        $configpath='bluethink_admin_low_inventory_notification/'.$pathVariable;
        $storeid='0';
        $scope='default';
        $configValue['default'.'_'.$storeid]=$this->scopeConfig
        ->getValue($configpath, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        foreach ($this->storeManager->getStores() as $store) {
            $storecode=$store->getCode();
            $storeid=$store->getId();
            $scope='stores';
            $data=$this->checkexistconfigdata($configpath, $scope, $storeid);
            if ($data==$storeid) {
                $configValue['store'.'_'.$storeid]=$this->scopeConfig
                ->getValue($configpath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeid);
            }
        }
        foreach ($this->storeManager->getWebsites() as $website) {
            $websitecode=$website->getCode();
            $websiteid=$website->getId();
            $scope='websites';
            $data=$this->checkexistconfigdata($configpath, $scope, $websiteid);
            if ($data==$websiteid) {
                $configValue['website'.'_'.$websiteid]=$this->scopeConfig
                ->getValue($configpath, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websitecode);
            }
        }
        return $configValue;
    }
    /**
     * Generates getAllstore and wbsite scope value
     *
     * @param configpath $configpath
     * @param scope $scope
     * @param storeid $storeid
     */
    public function checkexistconfigdata($configpath, $scope, $storeid)
    {
        $_Config = $this->Configurationvalue->create();
        $_Config->addFieldToFilter('path', ['eq'=>$configpath]);
        $_Config->addFieldToFilter('scope_id', ['eq'=>$storeid]);
        $_Config->addFieldToFilter('scope', ['eq'=>$scope]);
        foreach ($_Config->getData() as $value) {
            $scope_id=$value['scope_id'];
            return $scope_id;
        }
    }
    /**
     * This is a notify enable disable value Get
     *
     * @return NOTIFYENABLEDISABLE
     */
    public function NOTIFYENABLEDISABLE()
    {
        return $this->scopeConfig->getValue(
            self::NOTIFY_ENABLE_DISABLE,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }
    /**
     * This is low inventory notification
     *
     * @return synclowinventorynotification
     */
    public function synclowinventorynotification()
    {
        $notifyenabledisable=$this->NOTIFYENABLEDISABLE();
        if ($notifyenabledisable==1) {
            $sendtoemail=$this->sendtoemail('email_configuration/send_to_email', true);
            $qtyAlert = $this->sendtoemail('general/notify_for_quantity_below');
            $emailSub = $this->sendtoemail('email_configuration/notify_email_subject');
            foreach ($sendtoemail as $key => $value) {
                $email=$value;
                $store=$key;
                $steore1=explode('_', $store);
                $storename=$steore1[0];
                $storecode=$steore1[1];
                $name='Admin';
                $subject=isset($emailSub[$key]) ? $emailSub[$key] : $emailSub['default_0'];
                $notifyforquantitybelow=isset($qtyAlert[$key])?$qtyAlert[$key]:$qtyAlert['default_0'];
                $collection = $this->collectionFactory->create();
                $collection->setFlag('has_stock_status_filter', true);
                if ($storename=='store') {
                    $collection->addStoreFilter($storecode);
                } elseif ($storename=='website') {
                    $collection->addWebsiteFilter($storecode);
                }
                $collection = $collection->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', ['eq' => 'simple'])
                ->setOrder('sort_order', 'ASC')
                ->addAttributeToSort('created_at', 'ASC')
                ->joinField(
                    'qty',
                    'cataloginventory_stock_item',
                    'qty',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                )->joinTable(
                    'cataloginventory_stock_item',
                    'product_id=entity_id',
                    ['stock_status' => 'is_in_stock']
                )
                ->addAttributeToSelect('stock_status')
                ->addFieldToFilter('qty', ['lteq' => $notifyforquantitybelow])
                ->load();
                $customerdetails=['name'=>$name,'email'=>$email,'subject'=>$subject];
                $this->helperEmail->sendEmail($customerdetails, $collection, $storename, $storecode);
            }
            $message=__(
                'Low inventory notification has been Sent to store Owner.
                If you are using localhost server may be not send mail.'
            );
            $error = __('');
            $data=['response' => $message, 'error'=>$error];
            return $data;
        } else {
            $message=__('Please Enable This Module.');
            $error = __('true');
            $data=['response' => $message, 'error'=>$error];
            return $data;
        }
    }
}
