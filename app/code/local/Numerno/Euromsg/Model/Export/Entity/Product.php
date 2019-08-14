<?php
/**
 * euro.message Personalized Omni-channel Marketing Automation
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the "NUMERNO EUROMESSAGE MAGENTO EXTENSION License", which extends the Open Software
 * License (OSL 3.0).
 * The "NUMERNO EUROMESSAGE MAGENTO EXTENSION License" is available at this URL:
 *  http://www.numerno.com/licenses/euromsg-ce.txt
 * The Open Software License (OSL 3.0) is available at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Numerno is not held liable for any inconsistencies or
 * abnormalities in the behaviour of this code. By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Numerno, outlined in the provided License.
 *
 * Upon discovery of modified code in the process of support, the Licensee is still held accountable for any and all
 * billable time Numerno spent during the support process. Numerno does not guarantee compatibility with any other
 * Magento extension. Numerno is not responsbile for any inconsistencies or abnormalities in the behaviour of this
 * code if caused by other Magento extension.
 *
 * If you did not receive a copy of the license, please send an email to info@numerno.com or call +90-212-223-5093,
 * so we can send you a copy immediately.
 *
 * @category   [Numerno]
 * @package    [Numerno_Euromsg]
 * @copyright  Copyright (c) 2016 Numerno Bilisim Hiz. Tic. Ltd. Sti. (http://www.numerno.com/)
 * @license    http://numerno.com/licenses/euromsg-ce.txt NUMERNO EUROMESSAGE MAGENTO EXTENSION License
 */

/**
 * Product Export Entity
 *
 * @category   Numerno
 * @package    Numerno_Euromsg
 * @author     Numerno Bilisim Hiz. Tic. Ltd. Sti. <info@numerno.com>
 */
class Numerno_Euromsg_Model_Export_Entity_Product extends Mage_ImportExport_Model_Export_Entity_Product
{
    /**
     * Permanent column names.
     */
    const COL_PRODUCT_ID    = 'PRODUCT_ID';

    /**
     * Export Filename
     *
     * @var string
     */
    protected $_filename;

    /**
     * File list to zip
     *
     * @var array
     */
    protected $_wrap = array();

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->_writer       = Mage::getSingleton('euromsg/export_adapter_csv');
    }

    /**
     * Get attributes array which are appropriate for export.
     *
     * @return array
     */
    protected function _getExportAttributes()
    {
        $exportAttributes = Mage::helper('euromsg')->getConfigData('general/attributes', 'catalog');
        if (!is_array($exportAttributes)) {
            $exportAttributes = empty($exportAttributes) ? array() : unserialize($exportAttributes);
        }

        $codes   = array();
        foreach($exportAttributes as $exportAttribute) {
            $codes[] = $exportAttribute['attribute'];
        }

        $dataTypes = Mage::helper('euromsg')->getProductEntityDataTypes($codes);

        foreach($exportAttributes as $key => $exportAttribute) {
            if(isset($dataTypes[$exportAttribute['attribute']])) {
                $exportAttributes[$key]['col_name'] = $exportAttribute['col_name'] . '[' .
                    $dataTypes[$exportAttribute['attribute']] . ']';
            }
        }

        return $exportAttributes;
    }

    /**
     * Get attributes codes which are appropriate for export.
     *
     * @return array
     */
    protected function _getExportAttrCodes()
    {
        if (null === self::$attrCodes) {
            $attributes = $this->_getExportAttributes();
            self::$attrCodes = array_keys($attributes);
        }
        return self::$attrCodes;

    }

    /**
     * Prepare catalog inventory
     *
     * @param  array $productIds
     * @return array
     */
    protected function _prepareCatalogInventory(array $productIds)
    {
        if (empty($productIds)) {
            return array();
        }

        $attributes = $this->_getExportAttributes();

        $selectColumns = array('product_id');
        if (isset($attributes['qty'])) {
            $selectColumns[] = 'qty AS ' . $attributes['qty']['col_name'];
        }
        if (isset($attributes['is_in_stock'])) {
            $selectColumns[] = 'is_in_stock AS ' . $attributes['is_in_stock']['col_name'];
        }

        $select = $this->_connection->select()
            ->from(Mage::getResourceModel('cataloginventory/stock_item')->getMainTable(), $selectColumns)
            ->where('product_id IN (?)', $productIds);

        $stmt = $this->_connection->query($select);
        $stockItemRows = array();
        while ($stockItemRow = $stmt->fetch()) {
            $productId = $stockItemRow['product_id'];
            unset($stockItemRow['product_id']);

            if (isset($attributes['qty'])) {
                if ($stockItemRow[$attributes['qty']['col_name']] < 0) {
                    $stockItemRow[$attributes['qty']['col_name']] = 0;
                }
                $stockItemRow[$attributes['qty']['col_name']] *= 1;
            }
            $stockItemRows[$productId] = $stockItemRow;
        }

        return $stockItemRows;
    }

    /**
     * Update data row with information about categories. Return true, if data row was updated
     *
     * @param array $dataRow
     * @param array $rowCategories
     * @param int $productId
     * @return bool
     */
    protected function _updateDataWithCategoryColumns(&$dataRow, &$rowCategories, $productId)
    {

        if (!isset($rowCategories[$productId])) {
            return false;
        }

        $attributes = $this->_getExportAttributes();
        $categoryId = array_shift($rowCategories[$productId]);

        if (isset($attributes[self::COL_ROOT_CATEGORY])) {
            if (isset($this->_rootCategories[$categoryId])) {
                $dataRow[$attributes[self::COL_ROOT_CATEGORY]['col_name']] = $this->_rootCategories[$categoryId];
            } else {
                $dataRow[$attributes[self::COL_ROOT_CATEGORY]['col_name']] = '';
            }
        }

        if (isset($attributes[self::COL_CATEGORY])) {
            if (isset($this->_categories[$categoryId])) {

                $dataRow[$attributes[self::COL_CATEGORY]['col_name']] = $this->_categories[$categoryId];
            } else {
                $dataRow[$attributes[self::COL_CATEGORY]['col_name']] = '';
            }
        }

        return true;
    }

    /**
     * Calculate product limit per export process
     *
     * @return int
     */
    public function _getLimitProducts()
    {
        $helper = Mage::helper('euromsg');
        $defaultMemoryLimit = $helper->getConfigData('advanced/memory_limit', 'catalog');
        if (!$defaultMemoryLimit) {
            $defaultMemoryLimit = 250000000;
        }

        $memoryLimit = trim(ini_get('memory_limit'));
        $lastMemoryLimitLetter = strtolower($memoryLimit[strlen($memoryLimit)-1]);
        switch ($lastMemoryLimitLetter) {
            case 'g':
                $memoryLimit *= 1024;
            case 'm':
                $memoryLimit *= 1024;
            case 'k':
                $memoryLimit *= 1024;
                break;
            default:
                // minimum memory required by Magento
                $memoryLimit = $defaultMemoryLimit;
        }

        // Tested one product to have up to such size
        $memoryPerProduct = $helper->getConfigData('advanced/memory_pp', 'catalog');
        if (!$memoryPerProduct) {
            $memoryPerProduct = 100000;
        }

        // Decrease memory limit to have supply
        $memoryUsagePercent = $helper->getConfigData('advanced/memory_percent', 'catalog') / 100;
        if (!$memoryUsagePercent || $memoryUsagePercent > 0.8) {
            $memoryUsagePercent = 0.8;
        }

        // Minimum Products limit
        $minProductsLimit = $helper->getConfigData('advanced/minimum_products', 'catalog');
        if (!$minProductsLimit) {
            $minProductsLimit = 500;
        }
        $limitProducts = intval(($memoryLimit  * $memoryUsagePercent - memory_get_usage(true)) / $memoryPerProduct);
        if ($limitProducts < $minProductsLimit) {
            $limitProducts = $minProductsLimit;
        }

        return $limitProducts;
    }

    /**
     * Apply filter to collection and add not skipped attributes to select.
     *
     * @param Mage_Eav_Model_Entity_Collection_Abstract $collection
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _prepareEntityCollection(Mage_Eav_Model_Entity_Collection_Abstract $collection)
    {
        $exportOnlyVisible = Mage::helper('euromsg')->getConfigData('general/export', 'catalog');
        $collection = parent::_prepareEntityCollection($collection);
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');

        if ($exportOnlyVisible) {
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        }

        return $collection;

    }

    /**
     * Set Export Filename
     *
     * @param string
     * @return Numerno_Euromsg_Model_Export_Entity_Product
     */
    public function setFilename($filename)
    {
        $writer = $this->getWriter();
        $destination = tempnam(sys_get_temp_dir(), $filename . '.' . $writer->getFileExtension());

        $this->_wrap[$filename . '.' . $this->_writer->getFileExtension()] = $destination;
        $this->_filename = $filename;
        $writer->setDestination($destination);

        $this->setWriter($writer);

        return $this;
    }

    /**
     * Retrieve export filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     * Export process.
     *
     * @return Numerno_Euromsg_Model_Export_Entity_Product
     */
    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $attributes      = $this->_getExportAttributes();
        $validAttrCodes  = $this->_getExportAttrCodes();
        $writer          = $this->getWriter();
        $limitProducts   = $this->_getLimitProducts();
        $offsetProducts  = 0;

        while (true) {

            ++$offsetProducts;

            $dataRows        = array();
            $rowCategories   = array();
            $rowMultiselects = array();

            /** @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
            $collection = Mage::getResourceModel('catalog/product_collection');
            $collection->addAttributeToSelect(array_merge($validAttrCodes, array('url_key', 'url_path')));
            $collection
                ->setPage($offsetProducts, $limitProducts);

            if ($collection->getCurPage() < $offsetProducts) {
                break;
            }

            $collection->load();
            if ($collection->count() == 0) {
                break;
            }

            foreach ($collection as $itemId => $item) { // go through all products

                foreach ($validAttrCodes as &$attrCode) { // go through all valid attribute codes
                    $attrValue = $item->getData($attrCode);

                    if (Zend_Date::isDate($attrValue, Zend_Date::ISO_8601)) {
                        $attrValue = date("Y-m-d", strtotime($attrValue));
                    }

                    if (!empty($this->_attributeValues[$attrCode])) {
                        if ($this->_attributeTypes[$attrCode] == 'multiselect') {
                            $attrValue = explode(',', $attrValue);
                            $attrValue = array_intersect_key(
                                $this->_attributeValues[$attrCode],
                                array_flip($attrValue)
                            );
                            $rowMultiselects[$itemId][$attrCode] = $attrValue;
                        } else if (isset($this->_attributeValues[$attrCode][$attrValue])) {
                            $attrValue = $this->_attributeValues[$attrCode][$attrValue];
                        } else {
                            $attrValue = null;
                        }
                    }

                    if (in_array($attrCode, array('image', 'small_image', 'thumbnail'))) {
                        $attrValue = (
                            ($attrValue == 'no_selection' || $attrValue == '') ?
                                '' :
                                Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $attrValue
                        );
                    }

                    if (is_scalar($attrValue) && isset($attributes[$attrCode])) {
                        $dataRows[$itemId][$attributes[$attrCode]['col_name']] = $attrValue;
                    }
                }

                $attrSetId = $item->getAttributeSetId();
                if (isset($attributes[self::COL_ATTR_SET])) {
                    $dataRows[$itemId][$attributes[self::COL_ATTR_SET]['col_name']]
                        = $this->_attrSetIdToName[$attrSetId];
                }

                if (isset($attributes[self::COL_TYPE])) {
                    $dataRows[$itemId][$attributes[self::COL_TYPE]['col_name']] = $item->getTypeId();
                }

                if(isset($attributes['_url'])) {
                    if ($item->isVisibleInCatalog() && $item->isVisibleInSiteVisibility()) {
                        $url = Zend_Uri::factory($item->getProductUrl());
                        $url->removeQueryParameters();
                        $dataRows[$itemId][$attributes['_url']['col_name']] = $url->getUri();
                    } else {
                        $dataRows[$itemId][$attributes['_url']['col_name']] = '';
                    }
                }

                if (isset($attributes['_category']) || isset($attributes['_root_category'])) {
                    $rowCategories[$itemId] = $item->getCategoryIds();
                }
                $item = null;
            }
            $collection->clear();

            if ($collection->getCurPage() < $offsetProducts) {

                break;
            }

            // remove unused categories
            $allCategoriesIds = array_merge(array_keys($this->_categories), array_keys($this->_rootCategories));
            foreach ($rowCategories as &$categories) {
                $categories = array_intersect($categories, $allCategoriesIds);
            }

            // prepare catalog inventory information
            if (isset($attributes['qty']) || isset($attributes['is_in_stock'])) {
                $productIds = array_keys($dataRows);
                $stockItemRows = $this->_prepareCatalogInventory($productIds);
            }

            foreach ($dataRows as $productId => &$dataRow) {

                if (isset($stockItemRows[$productId])) {
                    $dataRow += $stockItemRows[$productId];
                }

                if (isset($rowCategories[$productId])) {
                    $this->_updateDataWithCategoryColumns($dataRow, $rowCategories, $productId);
                }

                if (!empty($rowMultiselects[$productId])) {
                    foreach ($rowMultiselects[$productId] as $attrKey => $attrVal) {
                        if (!empty($rowMultiselects[$productId][$attrKey])) {
                            $dataRow[$attrKey] = implode(",", $attrVal);
                        }
                    }
                }

                $writer->writeRow($dataRow);
            }
        }

        $notification = Mage::helper('euromsg')->getConfigData('general/notify', 'catalog');
        if ($notification) {
            $destination = tempnam(sys_get_temp_dir(), $this->getFilename() . '.xml');
            $this->_wrap[$this->getFilename() . '.xml'] = $destination;
            $xml = Mage::helper('core')->assocToXml(array('NOTIFICATION_EMAIL' => $notification), 'euro.message');
            file_put_contents($destination, explode("\n", $xml->asXML(), 2)[1]);
        }

        Mage::helper('euromsg')->wrapFiles($this->getFilename(), $this->_wrap);

        return $this;
    }

}