<?php
/**
 * 2019 https://www.la-dame-du-web.com
 *
 * @author    Nicolas PETITJEAN <n.petitjean@la-dame-du-web.com>
 * @date      2019-03-17
 * @copyright 2019 Nicolas PETITJEAN
 * @license   MIT License
 */

class LddwLogger
{
    public static function AddLog($message, $severity = 2, array $extradata = [], $trace = false, $filenameIdentifier = '', $headers = false)
    {
        static $nb = 0;
        static $identifier;

        if (empty($identifier)) {
            $identifier = md5(uniqid(rand(), true));
        }

        $severity = (int)$severity;
        $now = new \DateTime('now');

        // Try to figure out who writes in logs
        if(Context::getContext()->customer instanceof Customer) {
            $user_id = Context::getContext()->customer->id;
        } elseif (Context::getContext()->employee instanceof Employee) {
            $user_id = Context::getContext()->employee->id;
        } else {
            $user_id = 'undefined';
        }
        $text = "[{$identifier}_{$nb}] [{$now->format('Y-m-d H:i')}] [user_id: {$user_id}] [severity: {$severity}] | ".Tools::safeOutput($message);

        // Add extradata
        if (!empty($extradata)) {
            ob_start();
            print_r($extradata);
            $more = ob_get_clean();
            $text .= " | Extradata :\n{$more}\n";
        }

        // Add trace
        if ($trace) {
            ob_start();
            debug_print_backtrace();
            $more = ob_get_clean();
            $text .= "== PHP TRACE ==\n{$more}\n== /PHP TRACE ==\n";
        }

        // Add headers
        if ($headers) {
            $more = self::getHeaders();
            $text .= "== REQUEST HEADERS ==\n{$more}== /REQUEST HEADERS ==\n";
        }

        // Build filename
        if (empty($filenameIdentifier)) {
            $filenameIdentifier = 'logs';
        } else {
            $filenameIdentifier = strip_tags($filenameIdentifier);
        }

        // Check if log file exists
        $file = _PS_MODULE_DIR_."/lddw_logger/logs/{$now->format('Ymd')}.{$filenameIdentifier}.txt";
        if (!file_exists($file)) {
            touch($file);
        }

        // Write now
        $fp = fopen($file, 'a+');
        fwrite($fp, "{$text}\n");
        fclose($fp);
        $nb++;
    }

    private static function getHeaders()
    {
        $headerList = [];
        foreach ($_SERVER as $name => $value) {
            if (preg_match('/^HTTP_/', $name)) {
                // convert HTTP_HEADER_NAME to Header-Name
                $name = strtr(substr($name, 5), '_', ' ');
                $name = ucwords(strtolower($name));
                $name = strtr($name, ' ', '-');
                // add to list
                $headerList[$name] = $value;
            }
        }

        $data = sprintf(
            "%s %s %s\n\nHTTP headers:\n",
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['SERVER_PROTOCOL']
        );
        foreach ($headerList as $name => $value) {
            $data .= $name.': '.$value."\n";
        }

        return $data;
    }
}