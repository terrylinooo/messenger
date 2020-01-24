<?php declare(strict_types=1);
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Messenger;

/**
 * Messenger Interface
 *
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
Trait Messenger
{
    /**
     * The connection timeout when conntecting a SMTP server or a third-party API service.
     *
     * @var int
     */
    protected $timeout = 5;

    /**
     * The connection result.
     *
     * @var array
     */
    protected $resultData = [];

    /**
     * Debug mode.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Print the connection result. (for debugging purpose)
     *
     * @return string
     */
    public function printResult(): string
    {
        $data = '';

        foreach ($this->resultData as $key => $value) {
            $data .= $key . ': ' . $value . "\n";

            if (is_array($value) && ! empty($value) && $key === 'result') {
                $data .= '--- result ---' . "\n";
                foreach ($value as $key2 => $value2) {
                    $data .= $key2 . ': ' . $value2 . "\n";
                }
            }
        }

        return $data;
    }

    /**
     * Debug mode.
     *
     * @return void
     */
    public function debugMode(bool $mode = false): void
    {
        $this->debug = $mode;
    }

    /**
     * Debug mode.
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Excute CURL process and parse the result.
     * 
     * @param resource CURL resource.
     *
     * @return array 
     */
    protected function executeCurl($ch): array
    {
        $success = true; // bool
        $message = '';   // string
        $result = '';    // string | array

        $data = curl_exec($ch);

        if (! empty($data) && ! curl_error($ch)) {
            $success = true;
            $message = 'CURL has fetched data from target server successfully.';
            $result = $data;

        } else {

            if ($data === false) {
                $success = false;
                $message = 'CURL failed to fetch data. (error code #' . curl_error($ch) . ')';
            }
    
            if ($data === '') {
                $success = false;
                $message = 'The target returned an empty string.';
            }
        }

        $this->resultData = [
            'success' => $success,
            'message' => $message,
            'result'  => $result,
        ];

        curl_close($ch);

        return $this->resultData;
    }
}