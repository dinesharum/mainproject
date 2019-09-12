<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Alshaya\Phpexcel\Model\Export;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\MetadataProvider;
use Magento\Ui\Model\Export\SearchResultIteratorFactory; 
use Psr\Logger\LoggerInterface;
use Phpexcel_PHPExcel;
use Phpexcel_PHPExcel_IOFactory;

/**
 * Class ConvertToXml
 */
class ConvertToXlsx extends Phpexcel_PHPExcel
{
    /**
     * @var WriteInterface
     */
    protected $directory;

    /**
     * @var MetadataProvider
     */
    protected $metadataProvider;

    /**
     * @var ExcelFactory
     */
    protected $excelFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var SearchResultIteratorFactory
     */
    protected $iteratorFactory;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var Object
     */
    protected $_Xlsx;

  
    /**
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param MetadataProvider $metadataProvider
     * @param ExcelFactory $excelFactory
     * @param SearchResultIteratorFactory $iteratorFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        Filesystem $filesystem,
        Filter $filter,
        Phpexcel_PHPExcel $xlsx,
        MetadataProvider $metadataProvider,
        ExcelFactory $excelFactory,
        SearchResultIteratorFactory $iteratorFactory
    ) {
        $this->filter = $filter;
        $this->_Xlsx = $xlsx;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->metadataProvider = $metadataProvider;
        $this->excelFactory = $excelFactory;
        $this->_logger = $logger;
        $this->iteratorFactory = $iteratorFactory;
    }

    /**
     * Returns Filters with options
     *
     * @return array
     */
    protected function getOptions()
    {
        if (!$this->options) {
            $this->options = $this->metadataProvider->getOptions();
        }
        return $this->options;
    }

    /**
     * Returns DB fields list
     *
     * @return array
     */
    protected function getFields()
    {
        if (!$this->fields) {
            $component = $this->filter->getComponent();
            $this->fields = $this->metadataProvider->getFields($component);
        }
        return $this->fields;
    }

    /**
     * Returns row data
     *
     * @param DocumentInterface $document
     * @return array
     */
    public function getRowData(DocumentInterface $document)
    {
        return $this->metadataProvider->getRowData($document, $this->getFields(), $this->getOptions());
    }

    /**
     * Returns XML file
     *
     * @return array
     * @throws LocalizedException
     */
    public function getXlsxFile()
    {

        $component = $this->filter->getComponent();

        $name = md5(microtime());
        $file = 'export/'. $component->getName() . $name . '.xml';

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();

        /** @var SearchResultInterface $searchResult */
		$component->getContext()->getDataProvider()->setLimit(0, 999999);
        $searchResult = $component->getContext()->getDataProvider()->getSearchResult();

        $this->prepareItems($component->getName(), $searchResult->getItems());
        
        /** @var SearchResultIterator $searchResultIterator */
        $searchResultIterator = $this->iteratorFactory->create(['items' => $searchResult->getItems()]);
        $filterDataArr = '';
        foreach($searchResultIterator as $dataRow){
            $filterDataArr[]= $dataRow->getData();
        }
        
        $excelDataArray = '';
        $sheetTitle = 'export';
        $HeadersArray = $this->metadataProvider->getHeaders($component);     
        $FieldsArray = $this->metadataProvider->getFields($component);
        if(($key = array_search('actions', $FieldsArray)) !== false) {
            unset($FieldsArray[$key]);
        }
       
        if($component->getData('worksheetlabel')){
             $sheetTitle = $component->getData('worksheetlabel');
        }
        
        $hPos = 0;
        foreach($HeadersArray as $key =>$value){
            $excelDataArray[$hPos][$key] = $value;
        }
            
        $dPos = 1;
        if(count($filterDataArr) > 1){
            foreach($filterDataArr as $Filterdata){
                foreach($FieldsArray as $key=>$value){
                        $excelDataArray[$dPos][$key] = $Filterdata[$value];
                }
                $dPos++;
            }
        }

        $this->_Xlsx->getActiveSheet()->fromArray($excelDataArray, null, 'A1' );

        $this->_Xlsx->getActiveSheet()->setTitle($sheetTitle);
        $this->_Xlsx->setActiveSheetIndex(0);
        $callStartTime = microtime(true);

        $name = md5(microtime());
        $file = '/export/'. $component->getName() . $name . '.xlsx';

        $this->directory->create('export');
        $objWriter = Phpexcel_PHPExcel_IOFactory::createWriter($this->_Xlsx, 'Excel2007');
        $filepath = $this->directory->getAbsolutePath($file);
        $objWriter->save($filepath);
        
        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ];
    }

    /**
     * @param string $componentName
     * @param array $items
     * @return void
     */
    protected function prepareItems($componentName, array $items = [])
    {
        foreach ($items as $document) {
            $this->metadataProvider->convertDate($document, $componentName);
        }
    }
}
