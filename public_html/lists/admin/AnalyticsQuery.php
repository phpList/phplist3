<?php

/**
 * This interface is implemented by internal phplist classes.
 * A plugin can also implement this interface to customise the parameters for analytic tracking, such as Google Analytics or Matomo.
 */
interface AnalyticsQuery
{
    /**
     * Provide the query parameters to be added to a URL.
     *
     * @param string $emailFormat HTML or text
     * @param array  $messageData
     *
     * @return array query parameters as key => value
     */
    public function trackingParameters($emailFormat, $messageData);

    /**
     * Provide the query parameters that can be edited on the Finish tab.
     *
     * @param array $messageData
     *
     * @return array query parameters as key => default value
     */
    public function editableParameters($messageData);

    /**
     * Provide the prefix of the tracking parameters.
     * This is used to remove existing tracking parameters from a URL.
     *
     * @return string
     */
    public function prefix();
}
