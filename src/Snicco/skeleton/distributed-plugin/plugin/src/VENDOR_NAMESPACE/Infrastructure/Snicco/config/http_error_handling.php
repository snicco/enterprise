<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Bundle\Templating\TemplatingExceptionDisplayer;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Http\ErrorHandling\DomainExceptionTransformer;

return [
    /*
     * The log prefix is prepended to all log entries if the default logger is used.
     * This is a great way to make your log files more meaningful and easier to understand.
     *
     * If you are distributing code, you should set this value to "VENDOR_SLUG.request" as you will always be able
     * to quickly grep entries logged by your plugin.
     */
    HttpErrorHandlingOption::LOG_PREFIX => 'VENDOR_SLUG.request',

    /*
     * A list of ExceptionDisplayer classes that will be used to display exceptions inside your middleware pipeline.
     *
     * Feel free to remove this option if you don't need any custom exception-displayers as the framework already comes
     * with prebuild displayers.
     */
    HttpErrorHandlingOption::DISPLAYERS => [TemplatingExceptionDisplayer::class],

    /*
     * A list of class names that implement ExceptionTransformer.
     * An exception-transformer can be used to transform some exceptions into other exception objects before they are rendered.
     *
     * Feel free to remove this option if you don't need any custom exception-transformers.
     */
    HttpErrorHandlingOption::TRANSFORMERS => [DomainExceptionTransformer::class],

    /*
     * A list of class names that implement RequestLogContext.
     *
     * A custom RequestLogContext can be used to add information about the current request to the logged error
     * if an exception occurs.
     *
     * Feel free to remove this option if you don't need any custom request-log-context.
     */
    HttpErrorHandlingOption::REQUEST_LOG_CONTEXT => [],

    /*
     * You can use this option to customize the PSR-3 log-level for exceptions.
     * By default, all exceptions will be logged as CRITICAL if the http-status code is >= 500 or as ERROR
     * if the http-status code is <= 499.
     *
     * Feel free to remove this option if you don't need it.
     */
    HttpErrorHandlingOption::LOG_LEVELS => [
        //        Throwable::class => \Psr\Log\LogLevel::EMERGENCY
    ],
];
