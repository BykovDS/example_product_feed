<?php

namespace Yagooru\ProductFeedSearch;

class Writer
{
    private $usedCategories = [];
    private $categoryCounter = [];
    private $xml;
    const PROTOCOL = 'https://';
    protected $arSite = [];

    public function __construct(\XMLWriter $xml, $arSite)
    {
        $this->xml = $xml;
        $this->arSite = $arSite;
    }

    public function writeOfferName($name)
    {
        $this->xml->startElement('name');
        $this->xml->text($name);
        $this->xml->endElement();
    }

    public function writeOfferPicture($previewPicture)
    {
        $this->xml->startElement('picture');
        $image = \CFile::ResizeImageGet($previewPicture, array('width' => 300, 'height' => 300), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];
        $this->xml->text(self::PROTOCOL . $this->arSite['SERVER_NAME'] . $image);
        $this->xml->endElement(); // picture
    }

    public function writeOfferPrice ($price, $offerId)
    {
        $this->xml->startElement('price');
        $this->xml->text($price);
        $this->xml->endElement(); // price
        $oldPrice = findoldprice($offerId);
        if ($oldPrice && $oldPrice > (double)$price) {
            $this->xml->startElement('oldprice');
            $this->xml->text($oldPrice);
            $this->xml->endElement(); // price
        }
    }

    public function writeCurrencyId()
    {
        $this->xml->startElement('currencyId');
        $this->xml->text('RUB');
        $this->xml->endElement(); // currencyId
    }

    public function writeOfferUrl($url)
    {
        $this->xml->startElement('url');
        $this->xml->text(self::PROTOCOL . $this->arSite['SERVER_NAME'] . $url);
        $this->xml->endElement(); // url
    }

    public function writeOfferDescription($iblockId)
    {
        $description = $this->getMetaDescription($iblockId);
        $this->xml->startElement('description');
        $this->xml->text($description);
        $this->xml->endElement(); // description
    }

    public function writeGlassesOfferProperties($properties, $iblockId)
    {
        foreach ($properties as $key => $value) {
            if (!$value['VALUE']) {
                continue;
            }
            if ($key == 'BREND') {
                $brandName = htmlspecialchars_decode($value['VALUE']);
                $this->xml->startElement('vendor');
                $this->xml->text($brandName);
                $this->xml->endElement(); // vendor
            } else {
                $this->xml->startElement('param');
                $this->xml->writeAttribute('name', $value['NAME']);
                if ($key == 'SHIRINA_PERENOSITSY_MM') {
                    $this->xml->writeAttribute('unit', 'мм');
                }
                if ($key == 'DLINA_ZAUSHNIKA_MM') {
                    $this->xml->writeAttribute('unit', 'мм');
                }
                $decodeValue = htmlspecialchars_decode($value['VALUE']);
                $this->xml->text($decodeValue);
                $this->xml->endElement();
            }
        }
        $this->writeOfferCategoryId($iblockId, $brandName);
    }

    public function writeAccessoriesOfferProperties($properties, $iblockId)
    {
        foreach ($properties as $key => $value) {
            if (!$value['VALUE']) {
                continue;
            }
            if ($key == 'BREND') {
                $brandName = htmlspecialchars_decode($value['VALUE']);
                $this->xml->startElement('vendor');
                $this->xml->text($brandName);
                $this->xml->endElement(); // vendor
            } else {
                $this->xml->startElement('param');
                $this->xml->writeAttribute('name', $value['NAME']);
                $decodeValue = htmlspecialchars_decode($value['VALUE']);
                $this->xml->text($decodeValue);
                $this->xml->endElement();
            }
        }
        $this->writeOfferCategoryId($iblockId, $brandName);
    }

    public function writeMklOffersProperties($parentList, $parentId, $properties, $iblockId)
    {
        foreach ($parentList[$parentId]['PROPERTIES'] as $key => $value) {
            if (!$value['VALUE']) {
                continue;
            }
            if ($key == 'BREND') {
                $brandName = htmlspecialchars_decode($value['VALUE']);
                $this->xml->startElement('vendor');
                $this->xml->text($brandName);
                $this->xml->endElement(); // vendor
            } else {
                $this->xml->startElement('param');
                if ($key == 'STRANA_PROIZVODITEL') {
                    $value['NAME'] = 'Страна бренда';
                }
                if ($key == 'VLAGOSODERZHANIE') {
                    $this->xml->writeAttribute('unit', '%');
                }
                $this->xml->writeAttribute('name', $value['NAME']);
                $decodeValue = htmlspecialchars_decode($value['VALUE']);
                $this->xml->text($decodeValue);
                $this->xml->endElement();
            }
        }
        foreach ($properties as $value) {
            if (!$value['VALUE']) {
                continue;
            }
            $this->xml->startElement('param');
            $this->xml->writeAttribute('name', $value['NAME']);
            $decodeValue = htmlspecialchars_decode($value['VALUE']);
            $this->xml->text($decodeValue);
            $this->xml->endElement();
        }
        $this->writeOfferCategoryId($iblockId, $brandName);
    }

    public function writeOfferCategoryId($iblockId, $brandName)
    {
        // пишем справочник используемых категорий
        if (!isset($this->usedCategories[$iblockId][$brandName])) {
            if (!isset($this->categoryCounter[$iblockId])) {
                $this->categoryCounter[$iblockId] = 0; // счётчик
            }
            $countVar = $this->generateCategoryId($iblockId,  ++$this->categoryCounter[$iblockId]);
            $this->usedCategories[$iblockId][$brandName] = $countVar;
        }

        if (isset($this->usedCategories[$iblockId][$brandName])) {
            $categoryId = $this->usedCategories[$iblockId][$brandName];
            $this->xml->startElement('categoryId');
            $this->xml->text($categoryId);
            $this->xml->endElement(); // categoryId
        }
    }

    public function generateCategoryId($iblockId, $counter)
    {
        return $iblockId .  str_pad($counter, 3, '0', STR_PAD_LEFT);
    }

    public function getMetaDescription($iblockId)
    {
        switch ($iblockId) {
            case 18:
                return "Купить контактные линзы  в интернет-магазине и салонах оптики «Счастливый взгляд»";
            case 27:
                return "Аксессуары по выгодным ценам в сети салонов оптики «Счастливый взгляд»";
            case 22:
                return "Солнцезащитные очки в сети оптик «Счастливый взгляд». Оригинальная продукция.";
            case 31:
                return "Большой выбор оправ от брендовых до недорогих моделей в оптике Счастливый взгляд.";
        }
    }

    public function writeCategories()
    {
        $this->xml->startElement('categories');

        $categories = [
            18 => 'Контактные линзы',
            31 => 'Оправы',
            27 => 'Аксессуары',
            22 => 'Солнцезащитные очки'
        ];

        foreach ($categories as $id => $name) {
            $this->xml->startElement('category');
            $this->xml->writeAttribute('id', $id);
            $this->xml->text($name);
            $this->xml->endElement(); // category
        }

        foreach ($this->usedCategories as $parentId => $brands) {
            foreach ($brands as $brandName => $categoryId) {
                $parentCategoryName = $categories[$parentId];

                $this->xml->startElement('category');
                $this->xml->writeAttribute('id', $categoryId);
                $this->xml->writeAttribute('parentId', $parentId);
                $this->xml->text($parentCategoryName . ' ' . $brandName);
                $this->xml->endElement(); // category
            }
        }
        $this->xml->endElement(); // categories
    }
}