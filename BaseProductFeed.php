<?php

namespace Yagooru\ProductFeedSearch;

abstract class BaseProductFeed
{
    protected $xml;
    protected $connection;
    const PROTOCOL = 'https://';
    protected $arSite = [];
    const FORMAT_FILE = 'yml';

    public function __construct(){
        $this->connection = \Bitrix\Main\Application::getConnection();
        $this->xml = new \XMLWriter();
        $this->arSite = \CSite::GetByID("s1")->Fetch();
    }

    public abstract function generateProductFeed();

    public function getXml()
    {
        return $this->xml;
    }

    public function moveCategoriesBeforeOffers($filePath)
    {
        $content = file_get_contents($filePath);
        preg_match('/(<categories>.*<\/categories>)/s', $content, $categoriesMatch); // ищем категории

        if (isset($categoriesMatch[0])) {
            $categoriesBlock = $categoriesMatch[0];
            $content = str_replace($categoriesBlock, '', $content); // удаляем категории

            $pos = strpos($content, '<offers>'); // место вставки перед offers
            if ($pos !== false) {
                $content = substr_replace($content, $categoriesBlock, $pos, 0); // ставим блок категорий перед offers
                file_put_contents($filePath, $content);
            }
        }
    }

}