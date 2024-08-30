<?php

namespace Yagooru\ProductFeedSearch;

class Agent
{
    public static function generateFeedForSearch()
    {
        try {
            $feed = new ProductFeedCreate();
            $feed->generateProductFeed();
        } catch (\Throwable $e) {
            $application = \Bitrix\Main\Application::getInstance();
            $exceptionHandler = $application->getExceptionHandler();
            $exceptionHandler->writeToLog($e);
        } finally {
            return "\Yagooru\ProductFeedSearch\Agent::generateFeedForSearch();";
        }
    }
    public static function generateFeedForRegionSearch()
    {
        try {
            $feed = new ProductFeedRegionalCreate();
            $feed->generateProductFeed();
        } catch (\Throwable $e) {
            \COption::SetOptionInt("main", "search_feed_run_time", time() + 600);
            $application = \Bitrix\Main\Application::getInstance();
            $exceptionHandler = $application->getExceptionHandler();
            $exceptionHandler->writeToLog($e);
        } finally {
            return "\Yagooru\ProductFeedSearch\Agent::generateFeedForRegionSearch();";
        }
    }
}
