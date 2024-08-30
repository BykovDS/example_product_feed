<?php

namespace Yagooru\ProductFeedSearch;

use Bitrix\Main\ORM\Query\Join;
use Sotbit\Regions\Internals\FieldsTable;
use Bitrix\Main\ORM\Fields\Relations\Reference;

class ProductFeedCreate extends BaseProductFeed
{
    private $defaultRegion = [];
    private $writer;

    public function __construct()
    {
        parent::__construct();
        $region = \Sotbit\Regions\Internals\RegionsTable::getList([
            'filter' => [
                'FIELDS.CODE' => 'UF_REGION_CODE_IN','=FIELDS.VALUE' => '_'
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
        $this->defaultRegion = $region;
        $this->writer = new Writer($this->getXml(), $this->arSite);
    }

    public function generateProductFeed()
    {
        $this->xml->openURI($_SERVER['DOCUMENT_ROOT'] . "/products_search_feed_region_2{$this->defaultRegion['ID']}." . self::FORMAT_FILE); // создаёт YML в корне
        $this->xml->startDocument('1.0', 'utf-8');
        $this->xml->startElement('yml_catalog');
        $this->xml->writeAttribute('date', date('Y-m-d H:i'));
        $this->xml->startElement('shop');

        $this->xml->writeElement('name', $this->arSite['SITE_NAME']);
        $this->xml->writeElement('company', $this->arSite['SITE_NAME']);
        $this->xml->writeElement('url', self::PROTOCOL . $this->arSite['SERVER_NAME']);
        $this->xml->startElement('offers');
        $this->writeSunglasses();
        $this->writeGlasses();
        $this->writeAccessories();
        $this->writeMkl();
        $this->xml->endElement(); // offers
        $this->writer->writeCategories();
        $this->xml->endElement(); // shop
        $this->xml->endElement(); // yml_catalog
        $this->xml->endDocument();
        $this->xml->flush();
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/products_search_feed_region_2{$this->defaultRegion['ID']}." . self::FORMAT_FILE;
        $this->moveCategoriesBeforeOffers($filePath);
        $file = new \Bitrix\Main\IO\File($filePath);
        $fileOld = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'] . "/products_search_feed_region_{$this->defaultRegion['ID']}." . self::FORMAT_FILE);
        $fileOld->delete();
        $file->rename($_SERVER['DOCUMENT_ROOT'] . "/products_search_feed_region_{$this->defaultRegion['ID']}." . self::FORMAT_FILE);
    }

    protected function writeSunglasses()
    {
        $propSelect = ['BREND', 'CML2_ARTICLE', 'MATERIAL_OPRAVY', 'TSVET_OPRAVY', 'FORMA', 'KONSTRUKTSIYA', 'RAZMER_OPRAVY', 'PROIZVODITEL', 'GENDER', 'STRANA_PROIZVODSTVA', 'TSVET_LINZY_SZ', 'MATERIAL_LINZ_SZ', 'DLINA_ZAUSHNIKA_MM', 'SHIRINA_PERENOSITSY_MM', 'NALICHIE_FUTLYARA'];
        $objectProps = $this->selectProps('sunglasses', $propSelect);
        $productsOb = self::getProducts('sunglasses', $propSelect);
        $iblockId = getIblockId('sunglasses');

        while ($row = $productsOb->GetNext()) {
            $row = $this->prepareProps($objectProps, $row);
            $amount = \Yagooru\Facade\Catalog\Product::getAmountProduct($row['ID'], $this->defaultRegion['NAME']);

            $this->xml->startElement('offer');

            $this->xml->writeAttribute('id', $row['ID']);
            $this->xml->writeAttribute('available', $amount > 0 ? 'true' : 'false');

            $this->writer->writeOfferName($row['NAME']);

            $this->writer->writeOfferPicture($row['PREVIEW_PICTURE']);

            $this->writer->writeOfferPrice($row['PRICE_7'], $row['ID']);

            $this->writer->writeCurrencyId();

            $this->writer->writeOfferUrl($row['DETAIL_PAGE_URL']);

            $this->writer->writeGlassesOfferProperties($row['PROPERTIES'], $iblockId);

            $this->writer->writeOfferDescription($iblockId);

            $this->xml->endElement(); //offer
        }

    }

    protected function writeGlasses()
    {
        $propSelect = ['MATERIAL_OPRAVY', 'BREND', 'CML2_ARTICLE', 'TSVET_OPRAVY', 'FORMA', 'KONSTRUKTSIYA', 'RAZMER_OPRAVY', 'PROIZVODITEL', 'GENDER', 'STRANA_BRENDA', 'DLINA_ZAUSHNIKA_MM', 'SHIRINA_PERENOSITSY_MM', 'NALICHIE_FUTLYARA'];
        $objectProps = $this->selectProps('glasses', $propSelect);
        $productsOb = self::getProducts('glasses', $propSelect);
        $iblockId = getIblockId('glasses');

        while ($row = $productsOb->GetNext()) {
            $row = $this->prepareProps($objectProps, $row);
            $amount = \Yagooru\Facade\Catalog\Product::getAmountProduct($row['ID'], $this->defaultRegion['NAME']);

            $this->xml->startElement('offer');

            $this->xml->writeAttribute('id', $row['ID']);
            $this->xml->writeAttribute('available', $amount > 0 ? 'true' : 'false');

            $this->writer->writeOfferName($row['NAME']);

            $this->writer->writeOfferPicture($row['PREVIEW_PICTURE']);

            $this->writer->writeOfferPrice($row['PRICE_7'], $row['ID']);

            $this->writer->writeCurrencyId();

            $this->writer->writeOfferUrl($row['DETAIL_PAGE_URL']);

            $this->writer->writeGlassesOfferProperties($row['PROPERTIES'], $iblockId);

            $this->writer->writeOfferDescription($iblockId);

            $this->xml->endElement(); //offer
        }
    }

    protected function writeAccessories()
    {
        $propSelect = ['CML2_ARTICLE', 'BREND'];
        $objectProps = $this->selectProps('accessories', $propSelect);
        $productsOb = self::getProducts('accessories', $propSelect, ['SECTION_GLOBAL_ACTIVE' => 'Y']);
        $iblockId = getIblockId('accessories');
        while ($row = $productsOb->GetNext()) {
            $row = $this->prepareProps($objectProps, $row);
            $amount = \Yagooru\Facade\Catalog\Product::getAmountProduct($row['ID'], $this->defaultRegion['NAME']);

            $this->xml->startElement('offer');

            $this->xml->writeAttribute('id', $row['ID']);
            $this->xml->writeAttribute('available', $amount > 0 ? 'true' : 'false');

            $this->writer->writeOfferName($row['NAME']);

            $this->writer->writeOfferPicture($row['PREVIEW_PICTURE']);

            $this->writer->writeOfferPrice($row['PRICE_7'], $row['ID']);

            $this->writer->writeCurrencyId();

            $this->writer->writeOfferUrl($row['DETAIL_PAGE_URL']);

            $this->writer->writeAccessoriesOfferProperties($row['PROPERTIES'], $iblockId);

            $this->writer->writeOfferDescription($iblockId);

            $this->xml->endElement(); //offer
        }
    }

    protected function generateParentMkl()
    {
        $propSelectParent = ['BREND', 'MATERIAL_LINZY_MKL', 'VLAGOSODERZHANIE', 'STRANA_PROIZVODITEL', 'PROIZVODITEL', 'SROK_NOSHENIYA_LINZY', 'TSVETNOST_LINZY_MKL', 'DIZAYN_LINZY', 'KISLORODOPRONITSAEMOST', 'REZHIM_NOSHENIYA', 'GDE_SDELANO', 'KOLICHESTVO_MKL_V_UPAKOVKE', 'PERIOD_NOSHENIYA', 'UF_FILTR'];
        $objectPropsParent = $this->selectProps('contact_lenses', $propSelectParent);
        $arFilter = array(
            "IBLOCK_CODE" => 'contact_lenses', // для сзо
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            "!PREVIEW_PICTURE" => false,
        );
        $propSelectParent = array_map(function ($row) {
            return 'PROPERTY_' . $row;
        }, $propSelectParent);
        $select = array_merge([ // получаем аксессуары и сзо
            "ID",
            "IBLOCK_ID",
            "DETAIL_PAGE_URL",
            "PREVIEW_PICTURE",
            "DETAIL_TEXT",
            "NAME",
        ], $propSelectParent);

        $productsObParent = \CIBlockElement::GetList([], $arFilter, false, [], $select);
        $result = [];
        while ($row = $productsObParent->GetNext()) {
            $row = $this->prepareProps($objectPropsParent, $row);
            $result[$row['ID']] = $row;
        }
        return $result;
    }

    protected function writeMkl()
    {
        $propSelect = ['OPTICHESKAYA_SILA_SPH', 'CML2_ARTICLE', 'RADIUS_KRIVIZNY', 'OPTICHESKAYA_SILA_CYL', 'OS', 'TSVET_LINZY_MKL', 'ADDIDATSIYA_MKL', 'CML2_LINK'];

        $objectProps = $this->selectProps('mkl', $propSelect);
        $parentList = $this->generateParentMkl();
        $iblockId = getIblockId('contact_lenses');
        $arFilter = array(
            "IBLOCK_CODE" => 'mkl',
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            ">PRICE_7" => 0,
            "PROPERTY_CML2_LINK" => array_keys($parentList)
        );
        $propSelect = array_map(function ($item) {
            return 'PROPERTY_' . $item;
        }, $propSelect);
        $select = array_merge([ // получаем аксессуары и сзо
            "ID",
            "IBLOCK_ID",
            "PRICE_7",
            "NAME",
        ], $propSelect);

        $childProductOb = \CIBlockElement::GetList([], $arFilter, false, [], $select);
        $addedOffers = [];
        while ($row = $childProductOb->Fetch()) {
            if (in_array($row['ID'], $addedOffers)) {
                continue;
            }
            $row = $this->prepareProps($objectProps, $row);
            $parentId = $row['PROPERTIES']['CML2_LINK']['VALUE'];
            unset($row['PROPERTIES']['CML2_LINK']);
            $this->xml->startElement('offer');

            $this->xml->writeAttribute('id', $row['ID']);
            $this->xml->writeAttribute('available', 'true');
            $this->xml->writeAttribute('group_id', $parentId);

            $this->writer->writeOfferName($row['NAME']);

            $this->writer->writeOfferPicture($parentList[$parentId]['PREVIEW_PICTURE']);

            $this->writer->writeOfferPrice($row['PRICE_7'], $row['ID']);

            $this->writer->writeCurrencyId();

            $this->writer->writeOfferUrl($parentList[$parentId]['DETAIL_PAGE_URL']);

            $this->writer->writeMklOffersProperties($parentList, $parentId, $row['PROPERTIES'], $iblockId);

            $this->writer->writeOfferDescription($iblockId);

            $this->xml->endElement(); //offer
            $addedOffers[] = $row['ID'];
        }
    }

    protected function selectProps(string $code, array $propSelect)
    {
        $whereIn = [];
        foreach ($propSelect as $select) {
            $whereIn[] = "'$select'";
        }
        $propsObj = $this->connection->query("SELECT bp.CODE,bp.PROPERTY_TYPE,bp.USER_TYPE_SETTINGS,bp.NAME FROM `b_iblock_property` AS bp INNER JOIN `b_iblock` AS ib ON ib.ID=bp.IBLOCK_ID WHERE ib.CODE='$code' AND bp.CODE IN(" . implode(',', $whereIn) . ")");
        return $propsObj;
    }

    protected function prepareProps($objectProps, $product)
    {

        $product['PROPERTIES'] = [];
        foreach ($objectProps->getResource() as $row) {
            $a[] = $row;
        }
        foreach ($objectProps->getResource() as $row) {
            $value = $product["PROPERTY_" . $row['CODE'] . "_VALUE"];
            $settings = unserialize($row['USER_TYPE_SETTINGS']);
            if ($row['PROPERTY_TYPE'] == 'S' && isset($settings['TABLE_NAME'])) {

                $prop = $this->connection->query("SELECT UF_NAME FROM {$settings['TABLE_NAME']} WHERE UF_XML_ID='$value';")->fetch();
                $product['PROPERTIES'][$row['CODE']] = [
                    'NAME' => $row['NAME'],
                    'VALUE' => htmlspecialchars($prop['UF_NAME'])
                ];
            } else {
                $product['PROPERTIES'][$row['CODE']] = [
                    'NAME' => $row['NAME'],
                    'VALUE' => htmlspecialchars($value)
                ];
            }
        }
        return $product;
    }

    protected function getProducts(string $iblockCode, array $propSelect, array $filter = [])
    {

        $arFilter = array(
            "IBLOCK_CODE" => $iblockCode, // для сзо
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            ">PRICE_7" => 0,
            "!PREVIEW_PICTURE" => false,
        );
        $arFilter = array_merge($arFilter, $filter);
        $propSelect = array_map(function ($row) {
            return 'PROPERTY_' . $row;
        }, $propSelect);
        $select = array_merge([ // получаем аксессуары и сзо
            "ID",
            "IBLOCK_ID",
            "PRICE_7",
            "DETAIL_PAGE_URL",
            "PREVIEW_PICTURE",
            "DETAIL_TEXT",
            "NAME",
            "IBLOCK_SECTION_ID"
        ], $propSelect);
        return \CIBlockElement::GetList([], $arFilter, false, [], $select);
    }
}
