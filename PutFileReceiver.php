<?php
/****************************************************************************\
 * PutFileReceiver.php
 * https://github.com/antgraf/put_multipart
 * (C) 2015 Anton Pototsky
\****************************************************************************/

class PutFileReceiver
{
    public static $PUT_CHUNK_LENGTH = 4096;
    public static $PUT_FILE_PREFIX = 'php_put_';

    private static $HEADER_END_EVIDENCE = "\r\n\r\n";
    private static $BLOCK_NAME_REGEX = '/name\s*=\s*"([^;]+?)"\s*;/s';
    private static $BOUNDARY_REGEX = '%^multipart/form-data;\s+boundary\s*=\s*(.*)$%';
    private static $BOUNDARY_PREFIX = '--';

    public static function Log($var, $msg)
    {
        print($var . ': ' . print_r($msg, true) . "\r\n");
    }

    public static function SaveAll($stream = null)
    {
        $boundary = self::GetBoundary();
        if(!$boundary) {
            return false;
        }

        $savedFiles = false;

        //  PUT data comes in to the input stream
        $put = $stream ? $stream : fopen('php://input', 'r');
        if ($put) {
            $savedFiles = array();
            $prefix = '';
            while(true) {
                //  Open a temp file for writing
                $filename = tempnam(sys_get_temp_dir(), self::$PUT_FILE_PREFIX);
                $file = fopen($filename, 'w');
                if (!$file) {
                    // Cleanup saved files on error
                    fclose($file);
                    self::CleanupFile($filename);
                    foreach($savedFiles as $savedFile) {
                        self::CleanupFile($savedFile['tmp_name']);
                    }
                    $savedFiles = false;
                    break;
                }

                //  Find and write a block to file
                $block = self::FindBlock($put, $file, $boundary, $tail, $prefix);
                $prefix = $tail;
                if (!$block) {
                    // Cleanup temp file on error
                    fclose($file);
                    self::CleanupFile($filename);
                    break;
                }

                // Save data
                $savedFiles[$block] = array();
                $savedFiles[$block]['tmp_name'] = $filename;
                $savedFiles[$block]['type'] = '';
                $savedFiles[$block]['size'] = filesize($filename);
                $savedFiles[$block]['error'] = 0;
                $savedFiles[$block]['name'] = $block;

                // Close file
                fclose($file);
            }
        }

        //  Close stream
        if(!$stream) {
            fclose($put);
        }

        return $savedFiles;
    }

    private static function GetBoundary()
    {
        $ret = null;
        if (preg_match(self::$BOUNDARY_REGEX, $_SERVER['CONTENT_TYPE'], $regs)) {
            $ret = $regs[1];
        }
        return self::$BOUNDARY_PREFIX . $ret;
    }

    private static function CleanupFile($filename)
    {
        unlink($filename);
    }

    private static function SaveToStreamOrString($outStream, &$str, $data)
    {
        if($outStream) {
            fwrite($outStream, $data);
        }
        else {
            $str .= $data;
        }
    }

    private static function FindBoundary($inStream, $outStream, $boundary, &$head, &$tail, $prefix = '', $ignoreCase = false)
    {
        $head = '';
        $tail = '';
        $prev = $prefix;

        $end = true;
        while ($chunk = fread($inStream, self::$PUT_CHUNK_LENGTH)) {
            $end = false;
            $doubleChunk = $prev . $chunk;
            $pos = $ignoreCase ? stripos($doubleChunk, $boundary) : strpos($doubleChunk, $boundary);
            if ($pos !== false) {
                $save = substr($doubleChunk, 0, $pos);
                $tail = substr($doubleChunk, $pos + strlen($boundary));
                self::SaveToStreamOrString($outStream, $head, $save);
                return true;
            }
            else {
                self::SaveToStreamOrString($outStream, $head, $prev);
                $prev = $chunk;
            }
        }
        if($end) {
            $pos = $ignoreCase ? stripos($prev, $boundary) : strpos($prev, $boundary);
            if ($pos !== false) {
                $save = substr($prev, 0, $pos);
                $tail = substr($prev, $pos + strlen($boundary));
                self::SaveToStreamOrString($outStream, $head, $save);
                return true;
            }
            else {
                self::SaveToStreamOrString($outStream, $head, $prev);
            }
        }

        return false;
    }

    private static function FindBlock($inStream, $outStream, $boundary, &$tail, $prefix)
    {
        $name = null;

        if(self::FindBoundary($inStream, null, self::$HEADER_END_EVIDENCE, $head, $tail2, $prefix)) {
            if (preg_match(self::$BLOCK_NAME_REGEX, $head, $regs)) {
                $name = $regs[1];
            } else {
                throw new Exception('Error parsing multipart/form-data. No header.');
            }

            if(!self::FindBoundary($inStream, $outStream, $boundary, $null, $tail, $tail2)) {
                throw new Exception('Error parsing multipart/form-data. No boundary.');
            }
        }

        return $name;
    }
}