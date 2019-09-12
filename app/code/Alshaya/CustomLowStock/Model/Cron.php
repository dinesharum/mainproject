<?php
/**
 * Cron.php
 *
 * @package    Alshaya
 * @module     CustomLowStock
 * @copyright  © Alshaya 2016
 * @license    PHP License 5.0
 * @version    1.0.0
 * @since      File available with Release 1.0.0
 * @author     Dinesh Arumugam <dinesh.arumugam@alshaya.com>
 */
namespace Alshaya\CustomLowStock\Model;

/**
 * Using use
 * @description  Adding the required classes and interfaces
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ImportExport\Model\Export\Adapter\Csv;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Phpexcel_PHPExcel;
use Phpexcel_PHPExcel_IOFactory;



/**
 * Class Cron
 * @description  Defining the methods which will generate the reports and e-mail Notifications.
 */
class Cron extends \Magento\Framework\Model\AbstractModel
{
    // Defining the required constants for file path and name
    const FEED_PATH_PER_HOUR =  BP."/var/lowstockxlsxperhour/";
    const FEED_PATH_PER_DAY =   BP."/var/lowstockxlsxperday/";
    const FILE_NAME_PER_HOUR = "NotifyLowStockPerHour_"; 
    const FILE_NAME_PER_DAY = "NotifyLowStockPerDay_";
    const FILE_EXTENTION_CSV = '.csv';
    const FILE_EXTENTION_XLSX = '.xlsx';
    
    
    // Defining the required variable for the cron class
    protected $_datetime;
    protected $_modelItemFactory;
    protected $_modelproductFactory;
    protected $_logger;
    protected $csv;
    protected $_objectManager;
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $timezone;
    protected $_helper;
  
    /**
     * @var WriteInterface
     */
    protected $directory;
    
    /**
     * @var int
     */
    protected $_lineLength = 0;

    /**
     * @var string
     */
    protected $_delimiter = ',';

    /**
     * @var string
     */
    protected $_enclosure = '"';

    /**
     * @var File
     */
    protected $file;

    /**
     * @var Object
     */
    protected $_xlsx;

    /**
     * @var Object
     */
    protected $_phpmailer;
    
    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;
    
    
    /**
     * @description initializing the construct
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Alshaya\CustomLowStock\Model\ItemFactory $modelItemFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Csv $csv
     * @param File $file
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Alshaya\CustomLowStock\Helper\Data $helper,
        Phpexcel_PHPExcel $xlsx,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Alshaya\CustomLowStock\Model\ItemFactory $modelItemFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        Csv $csv,
        File $file
    ) 
    {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_xlsx = $xlsx;
        $this->_objectManager = $objectManager;
        $this->_modelItemFactory = $modelItemFactory;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_datetime = $dateTime;
        $this->timezone = $timezone;
        $this->_storeManager = $storeManager;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->csv = $csv;       
        $this->file = $file;
    }
          
    /**
     * @description creates the report and sends the e-mail Alert 
     *
     * @return object
     */
    public function exportLowStockPerHourXLSX()
    {   

        $this->directory->create('lowstockxlsxperhour');
        $items = $this->getProducts();  
        $datetimeName = $this->_datetime->date('Y_m_d_H_i_s');
        $mailAttachement = $this->createXLSX(self::FEED_PATH_PER_HOUR.self::FILE_NAME_PER_HOUR.$datetimeName.Self::FILE_EXTENTION_XLSX,$items);
        return $this;
    }
    
    /**
     * @description creates the report and sends the e-mail Alert 
     *
     * @return object
     */
    public function exportLowStockPerDayXLSX()
    {   
        $this->directory->create('lowstockxlsxperday');     
        $items = $this->getProducts();   
        $datetimeName = $this->_datetime->date('Y_m_d');
        $mailAttachement = $this->createXLSX(self::FEED_PATH_PER_DAY.self::FILE_NAME_PER_DAY.$datetimeName.Self::FILE_EXTENTION_XLSX,$items);
        $this->deleteData();
        return $this;
    }

    /**
     * @description creates the report and sends the e-mail Alert 
     *
     * @return object
     */
    public function exportLowStockPerHour()
    {   
        $items = $this->getProducts();   
        $datetimeName = $this->_datetime->date('Y_m_d_H_i_s');
        $mailAttachement = $this->createCSV(self::FEED_PATH_PER_HOUR.self::FILE_NAME_PER_HOUR.$datetimeName.Self::FILE_EXTENTION_CSV,$items);
        return $this;
    }

    /**
     * @description creates the report and sends the e-mail Alert 
     *
     * @return object
     */
    public function exportLowStockPerDay()
    {        
        $items = $this->getProducts();   
        $datetimeName = $this->_datetime->date('Y_m_d');
        $mailAttachement = $this->createCSV(self::FEED_PATH_PER_DAY.self::FILE_NAME_PER_DAY.$datetimeName.Self::FILE_EXTENTION_CSV,$items);
        $this->deleteData();
        return $this;
    }

    /**
     * @description get the collection from the model 
     *
     * @return object
     */
    public function getProducts()
    {
        $model = $this->_modelItemFactory->create();
        $collection = $model->getCollection();
        return $collection;
    }

    
    /**
     * @description creates the report and sends the e-mail Alert  
     *
     * @param $file file path along with file name
     * @param $data Data to be written to the csv file.
     * @return string
     */
    protected function createCSV($file, $data)
    {
        $headerArr =''; 
        $dataArr ='';
        if (count($data) > 0) 
        {           
            $fh = fopen($file, 'w');
            $headerArr = array('Date Time','Sku','Style Code','Product Name','Size','Threshold','Country');
            $this->file->filePutCsv($fh, $headerArr, $this->_delimiter, $this->_enclosure);
            foreach ($data as $dataRow) 
            {
                $timestamp = $dataRow->getData('timestamp');
                $sku = $dataRow->getData('sku');
                $style_code = $dataRow->getData('style_code');
                $product_name = $dataRow->getData('product_name');
                $size =$dataRow->getData('size');
                $threshold = $dataRow->getData('threshold');
                $country = $dataRow->getData('country');
                $dataArr ='';
                $dataArr = array($timestamp,$sku,$style_code,$product_name,$size,$threshold,$country);
                $this->file->filePutCsv($fh, $dataArr, $this->_delimiter, $this->_enclosure);
            }
            
            fclose($fh);
            $this->sendReportMail($file);
        }
        else
        {
            $this->_logger->addDebug('No Low Stock Records found to Notify Via Mail!!!');
        }
              
        return $file;
    }
    
    /**
     * @description creates the XLSX report and sends the e-mail Alert  
     *
     * @param $file file path along with file name
     * @param $data Data to be written to the xlsx file.
     * @return string
     */
    public function createXLSX($file, $data)
    {
        
        try{
            echo "\n Starting to Build XLSX file.";
            //$objPHPExcel = new Phpexcel_PHPExcel();
            $HeadersArray = array('Interval','Sku','Style Code', 'Product Name','Size','Threshold','Country');
            
            $excelDataArray = '';
            $hPos = 0;
            foreach($HeadersArray as $key =>$value)
            {
                $excelDataArray[$hPos][$key] = $value;
            }
            $sheetTitle = '';
            
            if (count($data) > 0) 
            {           
                     
                $dPos = 1;
                foreach ($data as $dataRow) 
                {       
                        $dateString = $dataRow->getData('timestamp');
                        $default_tz_date = $this->timezone->date($dateString);
                        $timezone = $this->timezone->date($dateString)->getTimezone();
                        $format_date = $this->timezone->formatDateTime($default_tz_date,null,null,'en_US',$timezone,'Y-MM-d HH:mm:ss');
                        
                        $excelDataArray[$dPos]['timestamp'] = $format_date;
                        $excelDataArray[$dPos]['sku'] = $dataRow->getData('sku');
                        $excelDataArray[$dPos]['style_code'] = $dataRow->getData('style_code');
                        $excelDataArray[$dPos]['product_name'] = $dataRow->getData('product_name');
                        $excelDataArray[$dPos]['size'] = $dataRow->getData('size');
                        $excelDataArray[$dPos]['threshold'] = $dataRow->getData('threshold');
                        $excelDataArray[$dPos]['country'] = $dataRow->getData('country');
                        $dPos++;
                }
              
                
            }
            
            $this->_xlsx->getActiveSheet()->fromArray($excelDataArray, null, 'A1' ); 
            //$objPHPExcel->getActiveSheet()->getStyle('A1:Z1')->getFont()->setBold(true);
            
            $this->_xlsx->getActiveSheet()->setTitle($sheetTitle);
            $this->_xlsx->setActiveSheetIndex(0);
                        
            // Rename worksheet
            $this->_xlsx->getActiveSheet()->setTitle('Notify Low Stock Report');
                       
            // Save Excel 2007 file
            $objWriter = Phpexcel_PHPExcel_IOFactory::createWriter($this->_xlsx, 'Excel2007');
            $objWriter->save($file);
                       
            echo "\n Starting to Sending Mail.";
            $this->sendReportMail($file); 
        }
        catch (Exception $e) {
            $this->_logger->addDebug($e);
        }
    }
    
    
    /**
     * @description prepares the mail  
     *
     * @param $fileAttachment file path along with file name to attach the file with mail
     * @param $errorCod mail error code
     * @param $errorMsg mail error message
     * @return string
     */
    function sendReportMail($file){
                        
            $templateParams['test']='test test';
            $store = $this->_storeManager->getStore();
            $storeId= $store->getId();
            $string=file_get_contents($file); 
                        
            $customVariableObj = $this->_objectManager->create('Magento\Variable\Model\Variable');
            $configuredRecipients = $customVariableObj->loadByCode('send_notify_lowstock_email')->getValue('text');
            echo "\n";
            echo $configuredRecipients;

            if (!strlen($configuredRecipients)) {
                 $recipients = array(
                  'dinesh.arumugam@alshaya.com',
                  'chitranjan.singh@alshaya.com',
                  'karan.dhingra@alshaya.com',
                  'vaibhav.nagar@alshaya.com'
                ); 
                
            } else {
                $recipients = explode(",", $configuredRecipients);
            }
            
            $datetimeName = $this->_datetime->date('Y_m_d_H_i_s');
            $fileName = self::FILE_NAME_PER_HOUR.$datetimeName.Self::FILE_EXTENTION_XLSX;
                
            try {       
                    var_dump($recipients);
                    foreach($recipients as $email)
                    {   
                        if(!empty($email))
                        {
                            $transport = $this->transportBuilder->setTemplateIdentifier('alshaya_customlowstock_email_template')
                            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                            ->setTemplateVars($templateParams)
                            ->setFrom(array('email'=>'no-reply@mothercare.com.kw', 'name'=>'MothercareMena'))
                            ->addTo($email) 
                            ->attachFile($string, $file)          
                            ->getTransport();
                            $transport->sendMessage();
                        }
                        
                    }
                } catch (Exception $e) {
                    $this->_logger->addDebug($e);
                    $this->_logger->addDebug($e->getMessage());
            }
            echo "\n Mail Sent Successfully. ";
    }
     
            
    /**
     * @description truncating the main table
     *
     */
    public function deleteData()
    {
        $collection = $this->getProducts();
        $tableName = $collection->getResource()->getMainTable();
        $connection = $collection->getConnection();
        $connection->truncateTable($tableName);
    }
    
}
