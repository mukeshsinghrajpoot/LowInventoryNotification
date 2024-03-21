<?php
namespace Bluethinkinc\LowInventoryNotification\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Io\File;
use Laminas\Mime\Mime as MimeType;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const EMAIL_TEMPLATE ='bluethink_admin_low_inventory_notification/email_configuration/email_template';
    public const EMAIL_SENDER ='bluethink_admin_low_inventory_notification/email_configuration/sender';
    /**
     * This is a inlineTranslation
     *
     * @var inlineTranslation $inlineTranslation
     */
    protected $inlineTranslation;
    /**
     * This is a transportBuilder
     *
     * @var transportBuilder $transportBuilder
     */
    protected $transportBuilder;
    /**
     * This is a _scopeConfig
     *
     * @var _scopeConfig $_scopeConfig
     */
    protected $_scopeConfig;
    /**
     * This is a storeManager
     *
     * @var storeManager $storeManager
     */
    private $storeManager;
    /**
     * This is a logger
     *
     * @var logger $logger
     */
    private $logger;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $priceHelper;
    /**
     * This is a directoryList
     *
     * @var logger $directoryList
     */
    private $directoryList;
    /**
     * This is a csvProcessor
     *
     * @var logger $csvProcessor
     */
    private $csvProcessor;
    /**
     * This is a file
     *
     * @var file $file
     */
    protected $file;
    /**
     * This is a construct
     *
     * @param Context $context
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param File $file
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\File\Csv $csvProcessor
     */
    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        File $file,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\File\Csv $csvProcessor
    ) {
        $this->inlineTranslation = $inlineTranslation;
        $this->directoryList = $directoryList;
        $this->csvProcessor = $csvProcessor;
        $this->transportBuilder = $transportBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->priceHelper = $priceHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Pricing\Helper\Data::class);
        $this->file = $file;
    }
    /**
     * This is a sendEmail
     *
     * @param customerdetails $customerdetails
     * @param collection $collection
     * @param collection $storename
     * @param collection $storecode
     */
    public function sendEmail($customerdetails, $collection, $storename, $storecode)
    {
        $fileDirectoryPath = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $fileName = $storename.$storecode.'.csv';
        $filePath =  $fileDirectoryPath . '/' . $fileName;
        $exportFieldsArray[] = [
        'entity_id'          => __('Product Id'),
        'type_id'            => __('Product Type'),
        'name'     => __('Product Nme'),
        'sku'         => __('Sku'),
        'qty'           => __('Qty'),
        'price'           => __('Price'),
        ];
        foreach ($collection as $product) {
            $exportFieldsArray[] = [
                'entity_id'  => $product->getEntityId(),
                'type_id'   => $product->getTypeId(),
                'name'       => $product->getName(),
                'sku'        => $product->getSku(),
                'qty'        => $product->getQty(),
                'price'      => $formattedPrice = $this->priceHelper->currency($product->getPrice(), true, false)
                ];
        }
        $this->csvProcessor
        ->setEnclosure('"')
        ->setDelimiter(',')
        ->saveData($filePath, $exportFieldsArray);
        $this->csvProcessor->getData($filePath);
        $customerName=$customerdetails['name'];
        $emailsubject=$customerdetails['subject'];
        $email1=$customerdetails['email'];
        $emails=explode(',', $email1);
        foreach ($emails as $key => $value) {
            $email=$value;
        
            $this->logger->debug('Low Inventory Notification start');
            $data = [
            'customer_name' => $customerName,
            'email_subject' =>$emailsubject,
            'customer_email' => $email,
            'store' => $this->storeManager->getStore()
            ];
            $this->logger->debug('toEmail == '.$email);
            $this->inlineTranslation->suspend();
            $storeId = $storecode;
            if ($storename=='website') {
                $template = $this->_scopeConfig->getValue(
                    self::EMAIL_TEMPLATE,
                    ScopeInterface::SCOPE_WEBSITE,
                    $storeId
                );
                $sender = $this->_scopeConfig->getValue(
                    self::EMAIL_SENDER,
                    ScopeInterface::SCOPE_WEBSITE,
                    $storeId
                );
            } else {
                $template = $this->_scopeConfig->getValue(
                    self::EMAIL_TEMPLATE,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
                $sender = $this->_scopeConfig->getValue(
                    self::EMAIL_SENDER,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            }
            if (!empty($filePath) && $this->file->fileExists($filePath)) {
                $mimeType = mime_content_type($filePath);
                $transport = $this->transportBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getid()
                    ]
                )
                ->setTemplateVars($data)
                ->setFrom($sender)
                ->addTo($email)
                ->getTransport();
                $body = $transport->getMessage()->getBody();
                if ($body instanceof MimeMessage) {
                    $parts = $body->getParts();

                    $attachmentPart = new MimePart();
                    $attachmentPart->setContent($this->file->read($filePath))
                        ->setType($mimeType)
                        ->setFileName($fileName)
                        ->setDisposition(MimeType::DISPOSITION_ATTACHMENT)
                        ->setEncoding(MimeType::ENCODING_BASE64);
                    $parts[] = $attachmentPart;

                    $message = new MimeMessage();
                    $message->setParts($parts);

                    $transport->getMessage()->setBody($message);
                }
            }
            try {
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
            $this->inlineTranslation->resume();
            $this->logger->debug('Low Inventory Notification sendEmail end');

        }
    }
}
