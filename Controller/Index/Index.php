<?php
/**
 * Created by PhpStorm.
 * User: Dennis van Schaik
 * Date: 15-3-2016
 * Time: 15:34
 */

namespace CrossMotion\RmaForm\Controller\Index;
/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultForwardFactory;
    protected $resultPageFactory;
    protected $_scopeConfig;
    protected $_countryFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Directory\Model\Config\Source\Country $countryFactory

    )
    {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->_countryFactory = $countryFactory;

        parent::__construct($context);
    }

    /**
     * Load the page defined in view/frontend/layout/samplenewpage_index_index.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {

        $active = $this->_objectManager->get('CrossMotion\RmaForm\Helper\Email')->getConfigValue(
            "crossmotion_rmaform/general/enable"
        );

        if (!$active) { //Module not active, show page not found error
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        } else {
            if (count($this->getRequest()->getPost()) > 0) {

                $form = $this->getRequest()->getPost();
                $files = $this->getRequest()->getFiles()['attachments'];

                $totalFileSize = 0;

                foreach ($files as $file) {
                    $fileInfo = pathinfo($file['name']);

                    $totalFileSize += $file['size'];

                    if (!in_array($fileInfo['extension'], ['jpg', 'jpeg'])) {
                        $this->messageManager->addError(__('Only files with jpg extension are allowed.'));
                        $error = true;
                        break;

                    }

                    if ($file['type'] != 'image/jpeg') {
                        $this->messageManager->addError(__('Only files with jpg extension are allowed.'));
                        $error = true;
                        break;
                    }
                }

                if ($totalFileSize > 10000000) {
                    $this->messageManager->addError(__('You can only upload files to a maximum of 10 MB.'));
                    $error = true;
                }


                if ($error != true) {


                    /* Sender Detail  */
                    $senderInfo = [
                        'email' => $form['email'],
                        'name' => $form['name'],
                    ];

                    $countryHelper = $this->_objectManager->get('Magento\Directory\Model\Config\Source\Country');
                    $countries = $countryHelper->toOptionArray();


                    $countryArray = [];

                    foreach ($countries as $country) {
                        $countryArray[$country['value']] = $country['label'];
                    }

                    /* Assign values for your template variables  */
                    $emailVars = [
                        'subject' => 'Return form',
                        'name' => $form['name'],
                        'email' => $form['email'],
                        'telephone' => $form['telephone'],
                        'ordernumber' => $form['ordernumber'],
                        'orderdate' => $form['orderdate'],
                        'return-reason' => $form['return-reason'],
                        'additional' => $form['additional'],
                        'bankaccountnumber' => $form['bankaccountnumber'],
                        'bankaccountholder' => $form['bankaccountholder'],
                        'country' => $countryArray[$form['telephonecountry']],
                        'returnproduct' => $form['returnproduct'],
                        'productnumber' => $form['productnumber'],
                        'productquantity' => $form['productquantity']
                    ];

                    /* We write send mail function in helper because if we want to
                       use same in other action then we can call it directly from helper */

                    /* call send mail method from helper or where you define it*/
                    $this->_objectManager->get('CrossMotion\RmaForm\Helper\Email')->sendRmaForm(
                        $emailVars,
                        $senderInfo,
                        $files
                    );

                    $this->messageManager->addSuccess(__('The return form is send. We will contact you shortly.'));
                }
            }

            return $this->resultPageFactory->create();
        }
    }
}