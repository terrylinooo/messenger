<?php declare(strict_types=1);
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shieldon\Messenger\Messenger;

use function is_array;
use function is_bool;
use function json_encode;
use function curl_exec;
use function curl_error;
use function curl_close;
use function curl_getinfo;

/**
 * Messenger Interface
 *
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
Trait MessengerTrait
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
     * @param string $resturnType
     *
     * @return string
     */
    public function printResult(string $returnType = 'plaintext'): string
    {
        $data = '';

        switch ($returnType) {
            case 'json':
                $data = json_encode($this->resultData, JSON_PRETTY_PRINT);
                break;
            case 'plaintext':
                foreach ($this->resultData as $key => $value) {
                    if (is_array($value) && ! empty($value)) {
                        $data .= '--- ' . $key . ' ---' . "\n";
                        foreach ($value as $key2 => $value2) {
                            if (is_bool($value2)) {
                                $value2 = $value2 ? 'true' : 'false';
                            }
                            $value2 = (string) $value2;
                            $data .= $key2 . ': ' . trim($value2) . "\n";
                        }
                    } else {
                        if (is_bool($value)) {
                            $value = $value ? 'true' : 'false';
                        }
                        if (empty($value)) {
                            $value = '';
                        }
                        $value = (string) $value;
                        $data .= $key . ': ' . trim($value) . "\n";
                    }
                }
                break;
            default:
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
     * Close the connection quickly.
     *
     * @return void
     */
    public function setTimeout(int $second)
    {
        $this->timeout = $second;
    }

    /**
     * Excute CURL process and parse the result.
     * 
     * @param resource $ch         CURL resource.
     * @param bool     $allowEmpty Allow target returns empty string.
     *
     * @return array 
     */
    protected function executeCurl($ch, $allowEmpty = false): array
    {
        $success = true; // bool
        $message = '';   // string
        $result = '';    // string | array

        $data = curl_exec($ch);

        if (! curl_error($ch)) {
            $success = true;
            $message = 'CURL has fetched data from target server successfully.';
            $result = $data;

        } else {

            if ($data === false) {
                $success = false;
                $message = 'CURL failed to fetch data. (error code #' . curl_error($ch) . ')';
            }
    
            if (empty($data) && ! $allowEmpty) {
                $success = false;
                $message = 'The target returned an empty string.';
            }
        }

        $this->resultData = [
            'success'  => $success,
            'message'  => $message,
            'result'   => $result,
            'httpcode' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        ];

        curl_close($ch);

        return $this->resultData;
    }
}