<?php

namespace Config;

use App\Libraries\CohereChatService;
use App\Libraries\CohereClient;
use App\Libraries\SemanticSearchService;
use App\Services\ReviewService;
use App\Services\MailService;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */

    /**
     * Server-side Cohere Embed client. Never instantiate this in a view.
     */
    public static function cohere(bool $getShared = true): CohereClient
    {
        if ($getShared) {
            return static::getSharedInstance('cohere');
        }

        return new CohereClient(config(Cohere::class));
    }

    /**
     * AI semantic product search.
     */
    public static function semanticSearch(bool $getShared = true): SemanticSearchService
    {
        if ($getShared) {
            return static::getSharedInstance('semanticSearch');
        }

        return new SemanticSearchService(config(Cohere::class), static::cohere());
    }

    /**
     * Conversational layer for the RHK Assistant chat.
     */
    public static function cohereChat(bool $getShared = true): CohereChatService
    {
        if ($getShared) {
            return static::getSharedInstance('cohereChat');
        }

        return new CohereChatService(config(Cohere::class), static::cohere());
    }

    /**
     * Rating and review business logic.
     */
    public static function reviewService(bool $getShared = true): ReviewService
    {
        if ($getShared) {
            return static::getSharedInstance('reviewService');
        }

        return new ReviewService();
    }

    public static function mailService(bool $getShared = true): MailService
    {
        if ($getShared) {
            return static::getSharedInstance('mailService');
        }

        return new MailService();
    }

    public static function paymongoService(bool $getShared = true): \App\Services\PaymongoService
    {
        if ($getShared) {
            return static::getSharedInstance('paymongoService');
        }

        return new \App\Services\PaymongoService();
    }
}
