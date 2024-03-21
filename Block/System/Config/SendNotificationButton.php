<?php

namespace Bluethinkinc\LowInventoryNotification\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SendNotificationButton extends Field
{
    /**
     * @var _template
     */
    protected $_template = 'Bluethinkinc_LowInventoryNotification::system/config/send_notification_button.phtml';
    /**
     * Render Element and Abstractelement
     *
     * @param element $element
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }
    /**
     * Get Element Html
     *
     * @param _getElementHtml $element
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
    /**
     * Get Ajax url this function
     *
     * @var getAjaxUrl
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('admin-low-inventory-notification/notification/updateadminlowstock');
    }
    /**
     * Get Button Html
     *
     * @return getButtonHtml
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)->setData(
            [
                'id' => 'send_notification',
                'label' => __('Send Notification'),
            ]
        );

        return $button->toHtml();
    }
}
