<?php
/**
 * Created by PhpStorm.
 * User: Dennis van Schaik
 * Date: 17-3-2016
 * Time: 15:46
 */

namespace CrossMotion\RmaForm\Helper;

/**
 * Custom Module Email helper
 */
class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_EMAIL_TEMPLATE_FIELD = 'crossmotion_rmaform/general/email_template';

    const XML_PATH_EMAIL_RECIEVER = 'crossmotion_rmaform/general/email_reciever';


    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var string
     */
    protected $tempId;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \CrossMotion\RmaForm\Helper\UploadTransportBuilder $transportBuilder
    )
    {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
    }

    /**
     * [sendInvoicedOrderEmail description]
     *
     * @param  Mixed $emailTemplateVariables
     * @param  Mixed $senderInfo
     *
     * @return void
     */
    public function sendRmaForm($emailTemplateVariables, $senderInfo, $files)
    {
        $receiver = $this->getEmailReciever(self::XML_PATH_EMAIL_RECIEVER);

        $receiverInfo = [
            'email' => $this->getConfigValue('trans_email/ident_' . $receiver . '/email'),
            'name' => $this->getConfigValue('trans_email/ident_' . $receiver . '/name')
        ];

        $this->tempId = $this->getTemplateId(self::XML_PATH_EMAIL_TEMPLATE_FIELD);
        $this->inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);

        foreach ($files as $file) {
            $this->_transportBuilder->attachFile($file['tmp_name'], $file['name']);
        }

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();

    }

    /**
     * Return reciever name / email according to store
     *
     * @return mixed
     */
    public function getEmailReciever($xmlPath)
    {
        return $this->getConfigValue($xmlPath);
    }

    /**
     * Return store configuration value of your template field that which id you set for template
     *
     * @param string $path
     *
     * @return mixed
     */
    public function getConfigValue($path)
    {

        return $this->_scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );

    }

    /**
     * Return store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    /**
     * Return template id according to store
     *
     * @return mixed
     */
    public function getTemplateId($xmlPath)
    {
        return $this->getConfigValue($xmlPath);
    }

    /**
     * [generateTemplate description]  with template file and tempaltes variables values
     *
     * @param  Mixed $emailTemplateVariables
     * @param  Mixed $senderInfo
     * @param  Mixed $receiverInfo
     *
     * @return void
     */
    public function generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $this->_transportBuilder->setTemplateIdentifier($this->tempId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($receiverInfo['email'], $receiverInfo['name']);

    }

    /**
     * [Add file to the transactional email]
     *
     * @param array $file
     *
     * @return $this
     */
    public function attachFile($file, $name)
    {
        if (!empty($file) && file_exists($file)) {
            $this->message
                ->createAttachment(
                    file_get_contents($file),
                    \Zend_Mime::TYPE_OCTETSTREAM,
                    \Zend_Mime::DISPOSITION_ATTACHMENT,
                    \Zend_Mime::ENCODING_BASE64,
                    basename($name)
                );
        }

        return $this;
    }

}