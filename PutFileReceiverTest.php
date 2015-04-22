<?php
/****************************************************************************\
 * PutFileReceiverTest.php
 * https://github.com/antgraf/put_multipart
 * (C) 2015 Anton Pototsky
\****************************************************************************/

require_once('PutFileReceiver.php');

class PutFileReceiverTest extends PHPUnit_Framework_TestCase {
    private static $TEST_DATA = "------WebKitFormBoundarypA17XSnpDSefaKXB
Content-Disposition: form-data; name=\"file\"; filename=\"1.txt\"
Content-Type: application/octet-stream

Some content 1
------WebKitFormBoundarypA17XSnpDSefaKXB
Content-Disposition: form-data; name=\"file2\"; filename=\"2.txt\"
Content-Type: application/octet-stream

Some content 2";
    private static $TEST_DATA_END = '------WebKitFormBoundarypA17XSnpDSefaKXB--';
    private static $TEST_TYPE = 'multipart/form-data; boundary=----WebKitFormBoundarypA17XSnpDSefaKXB';

    public function testReadFiles() {
        $_SERVER['CONTENT_TYPE'] = self::$TEST_TYPE;

        $stream = fopen('php://memory','r+');
        fwrite($stream, self::$TEST_DATA);
        fwrite($stream, self::$TEST_DATA_END);
        rewind($stream);
        $result = PutFileReceiver::SaveAll($stream);
        fclose($stream);

        PutFileReceiver::Log('$result', $result);

        $this->assertArrayHasKey('file', $result, "file info exists");
        $this->assertArrayHasKey('file2', $result, "file2 info exists");
        $this->assertArrayHasKey('tmp_name', $result['file'], "file name exists");
        $this->assertArrayHasKey('tmp_name', $result['file2'], "file2 name exists");
        $this->assertFileExists($result['file']['tmp_name'], "file exists");
        $this->assertFileExists($result['file2']['tmp_name'], "file2 exists");

        unlink($result['file']['tmp_name']);
        unlink($result['file2']['tmp_name']);
    }

    public function testReadLargeFiles() {
        $_SERVER['CONTENT_TYPE'] = self::$TEST_TYPE;

        $stream = fopen('php://memory','r+');
        fwrite($stream, self::$TEST_DATA);
        for($i = 0; $i < PutFileReceiver::$PUT_CHUNK_LENGTH; $i++) {
            fwrite($stream, 'A');
        }
        fwrite($stream, self::$TEST_DATA_END);
        rewind($stream);
        $result = PutFileReceiver::SaveAll($stream);
        fclose($stream);

        PutFileReceiver::Log('$result', $result);

        $this->assertArrayHasKey('file', $result, "file info exists");
        $this->assertArrayHasKey('file2', $result, "file2 info exists");
        $this->assertArrayHasKey('tmp_name', $result['file'], "file name exists");
        $this->assertArrayHasKey('tmp_name', $result['file2'], "file2 name exists");
        $this->assertFileExists($result['file']['tmp_name'], "file exists");
        $this->assertFileExists($result['file2']['tmp_name'], "file2 exists");

        unlink($result['file']['tmp_name']);
        unlink($result['file2']['tmp_name']);
    }

    public function testReadFilesWithBoundaryOnChunkEdge() {
        $_SERVER['CONTENT_TYPE'] = self::$TEST_TYPE;

        $stream = fopen('php://memory','r+');
        fwrite($stream, self::$TEST_DATA);
        for($i = strlen(self::$TEST_DATA); $i < PutFileReceiver::$PUT_CHUNK_LENGTH - 2; $i++) {
            fwrite($stream, 'A');
        }
        fwrite($stream, self::$TEST_DATA_END);
        rewind($stream);
        $result = PutFileReceiver::SaveAll($stream);
        fclose($stream);

        PutFileReceiver::Log('$result', $result);

        $this->assertArrayHasKey('file', $result, "file info exists");
        $this->assertArrayHasKey('file2', $result, "file2 info exists");
        $this->assertArrayHasKey('tmp_name', $result['file'], "file name exists");
        $this->assertArrayHasKey('tmp_name', $result['file2'], "file2 name exists");
        $this->assertFileExists($result['file']['tmp_name'], "file exists");
        $this->assertFileExists($result['file2']['tmp_name'], "file2 exists");

        unlink($result['file']['tmp_name']);
        unlink($result['file2']['tmp_name']);
    }
}
 