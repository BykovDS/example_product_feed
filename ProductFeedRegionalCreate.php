<?php

namespace Yagooru\ProductFeedSearch;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Sotbit\Regions\Internals\FieldsTable;
use Yagooru\Entity\RegionRunFeedTable;

class ProductFeedRegionalCreate extends BaseProductFeed
{
    const TIME_NEXT_RUN = 86400;
    private $writer;
    public function __construct()
    {
        parent::__construct();
        $this->writer = new Writer($this->getXml(), $this->arSite);
    }

    public function generateProductFeed()
    {
        $timeRun = \COption::GetOptionInt("main", "search_feed_run_time");
        if($timeRun && time() < $timeRun){
            return;
        }
        $runFeeds = RegionRunFeedTable::getList()->fetchAll();
        $runIds = [];
        foreach ($runFeeds as $row){
            $runIds[] = $row['ID'];
        }
        $region = \Sotbit\Regions\Internals\RegionsTable::getList([
            'filter' => [
                '!@ID' => empty($runIds) ? [0] : $runIds,
                'FIELDS.CODE' => 'UF_REGION_CODE_IN',
                '!=FIELDS.VALUE' => '_'
            ],
            'select' => [
                'ID',
                'NAME'
            ],
            'limit' => 1,
            'runtime' => [
                (new Reference(
                    'FIELDS',
                    FieldsTable::class,
                    Join::on('this.ID', 'ref.ID_REGION')
                ))->configureJoinType('inner'),
            ],
        ])->fetch();
        if(!$region){
            $this->connection->queryExecute("TRUNCATE TABLE `".RegionRunFeedTable::getTableName()."`");
            \COption::SetOptionInt("main", "search_feed_run_time", time() + self::TIME_NEXT_RUN);
            return;
        }

        $this->xml->openURI($_SERVER['DOCUMENT_ROOT'] . "/products_search_feed_region_2{$region['ID']}." . self::FORMAT_FILE); // создаёт YML в корне
        $this->xml->startDocument('1.0', 'utf-8');
        $this->xml->writeDtd('yml_catalog',null,'shops.dtd');
        $this->xml->startElement('yml_catalog');
        $this->xml->writeAttribute('date', date('Y-m-d H:i'));
        $this->xml->startElement('shop');
        $this->xml->writeElement('name', $this->arSite['SITE_NAME']);
        $this->xml->writeElement('company', $this->arSite['SITE_NAME']);
        $this->xml->writeElement('url', self::PROTOCOL . $this->arSite['SERVER_NAME']);
        $this->writer->writeCategories();
        $this->xml->startElement('offers');
        $this->generateProducts($region);
        $this->xml->endElement(); // offers
        $this->xml->endElement(); // shop
        $this->xml->endElement(); // yml_catalog
        $this->xml->endDocument();
        $this->xml->flush();
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/products_search_feed_region_2{$region['ID']}." . self::FORMAT_FILE;
        $this->moveCategoriesBeforeOffers($filePath);
        $file = new \Bitrix\Main\IO\File($filePath);
        $fileOld = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'] . "/products_search_feed_region_{$region['ID']}." . self::FORMAT_FILE);
        $fileOld->delete();
        $file->rename($_SERVER['DOCUMENT_ROOT'] . "/products_search_feed_region_{$region['ID']}." . self::FORMAT_FILE);

        $result = RegionRunFeedTable::add([
            'ID' => $region['ID']
        ]);
        if(!$result->isSuccess()){
            throw new \ErrorException(json_encode($result->getErrorMessages()));
        }
    }

    public function generateProducts(array $region)
    {
        $priceTypeId = getPriceId($region['ID']);
        $arFilter = array(
            "IBLOCK_CODE" => ['sunglasses','accessories','glasses'],
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            ">PRICE_" . $priceTypeId => 0,
            "!PREVIEW_PICTURE" => false,
            'SECTION_GLOBAL_ACTIVE' => 'Y'
        );
        $select = [ // получаем аксессуары и сзо
            "ID",
            "IBLOCK_ID",
            "PRICE_" . $priceTypeId,
            "IBLOCK_SECTION_ID",
            "IBLOCK_CODE"
        ];
        $dbRes = \CIBlockElement::GetList([], $arFilter, false, [], $select);
        while ($row = $dbRes->Fetch()) {
            $row['AMOUNT'] = \Yagooru\Facade\Catalog\Product::getAmountProduct($row['ID'],$region['NAME']);
            $row['PRICE'] = $row['PRICE_' . $priceTypeId];
            $this->generateOffer($row);
        }

        $arFilter = array(
            "IBLOCK_CODE" => 'contact_lenses',
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            "!PREVIEW_PICTURE" => false,
        );
        $productsObParent = \CIBlockElement::GetList([], $arFilter, false, [], $select);
        $lensesIds = [];
        while ($row = $productsObParent->GetNext()) {
            $lensesIds[] = $row['ID'];
        }

        $arFilter = array(
            "IBLOCK_CODE" => 'mkl',
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            ">PRICE_" . $priceTypeId => 0,
            "PROPERTY_CML2_LINK" => $lensesIds
        );
        $dbRes = \CIBlockElement::GetList([], $arFilter, false, [], $select);
        while ($row = $dbRes->Fetch()) {
            $row['AMOUNT'] = 1;
            $row['PRICE'] = $row['PRICE_' . $priceTypeId];
            $this->generateOffer($row);
        }
    }

    protected function generateOffer(array $row){
        $this->xml->startElement('offer');
        $this->xml->writeAttribute('id', $row['ID']);
        $this->xml->writeAttribute('available', $row['AMOUNT'] > 0 ? 'true' : 'false');
        $this->xml->startElement('price');
        $this->xml->text($row['PRICE']);
        $this->xml->endElement(); // price
        $this->xml->startElement('categoryId');
        $this->xml->text($row['IBLOCK_ID'] == 9 ? 18 : $row['IBLOCK_ID']);
        $this->xml->endElement();
        $oldPrice = findoldprice($row['ID']);
        if ($oldPrice && $oldPrice > (double)$row['PRICE']) {
            $this->xml->startElement('oldprice');
            $this->xml->text($oldPrice);
            $this->xml->endElement(); // price
        }
        $this->xml->endElement();
    }
}
