<?php

namespace EasyApiTests\Tests\Unit\Core;

use EasyApiTests\Core\FileBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileBagTest extends TestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        // Create a temporary test file
        $this->testFilePath = tempnam(sys_get_temp_dir(), 'test_file_');
        file_put_contents($this->testFilePath, 'test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    public function testAddFile(): void
    {
        $fileBag = new FileBag();
        $fileBag->addFile('test_field', $this->testFilePath, 'test.txt');

        $data = $fileBag->getData();
        $this->assertCount(1, $data);

        $fileData = $data[0];
        $this->assertSame('test_field', $fileData['name']);
        $this->assertSame('test.txt', $fileData['filename']);
        $this->assertInstanceOf(UploadedFile::class, $fileData['file']);
        $this->assertSame([], $fileData['headers']);
    }

    public function testAddFileWithHeaders(): void
    {
        $fileBag = new FileBag();
        $headers = ['X-Custom-Header' => 'value'];
        $fileBag->addFile('test_field', $this->testFilePath, 'test.txt', $headers);

        $data = $fileBag->getData();
        $fileData = $data[0];

        $this->assertSame($headers, $fileData['headers']);
    }

    public function testAddFileWithoutFileName(): void
    {
        $fileBag = new FileBag();
        $fileBag->addFile('test_field', $this->testFilePath);

        $data = $fileBag->getData();
        $fileData = $data[0];

        $this->assertNull($fileData['filename']);
        // But the UploadedFile should have the basename as originalName
        $this->assertSame(basename($this->testFilePath), $fileData['file']->getClientOriginalName());
    }

    public function testAddMultipleFiles(): void
    {
        $fileBag = new FileBag();

        // Create second test file
        $testFilePath2 = tempnam(sys_get_temp_dir(), 'test_file2_');
        file_put_contents($testFilePath2, 'test content 2');

        try {
            $fileBag->addFile('field1', $this->testFilePath, 'file1.txt');
            $fileBag->addFile('field2', $testFilePath2, 'file2.txt');

            $data = $fileBag->getData();
            $this->assertCount(2, $data);

            $this->assertSame('field1', $data[0]['name']);
            $this->assertSame('field2', $data[1]['name']);
        } finally {
            if (file_exists($testFilePath2)) {
                unlink($testFilePath2);
            }
        }
    }

    public function testGetFiles(): void
    {
        $fileBag = new FileBag();
        $fileBag->addFile('test_field', $this->testFilePath, 'test.txt');
        $fileBag->addFile('another_field', $this->testFilePath, 'another.txt');

        $files = $fileBag->getFiles();

        $this->assertCount(2, $files);
        $this->assertArrayHasKey('test_field', $files);
        $this->assertArrayHasKey('another_field', $files);
        $this->assertInstanceOf(UploadedFile::class, $files['test_field']);
        $this->assertInstanceOf(UploadedFile::class, $files['another_field']);
    }

    public function testGetDataEmpty(): void
    {
        $fileBag = new FileBag();

        $this->assertEmpty($fileBag->getData());
        $this->assertEmpty($fileBag->getFiles());
    }

    public function testMimeTypeDetection(): void
    {
        // Create a JSON test file
        $jsonFilePath = tempnam(sys_get_temp_dir(), 'test_json_');
        file_put_contents($jsonFilePath, '{"test": true}');

        try {
            $fileBag = new FileBag();
            $fileBag->addFile('json_field', $jsonFilePath, 'test.json');

            $files = $fileBag->getFiles();
            $uploadedFile = $files['json_field'];

            // The UploadedFile should have detected the MIME type
            $this->assertNotNull($uploadedFile->getMimeType());
        } finally {
            if (file_exists($jsonFilePath)) {
                unlink($jsonFilePath);
            }
        }
    }
}
