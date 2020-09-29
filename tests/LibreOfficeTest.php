<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Amp\Parallel\Worker;
use Amp\Promise;

class LibreOfficeTest extends TestCase {

    private $dir = 'storage/';
    private $total_random_items_to_create = 5;
    private $random_strings = [];

    public function __construct()
    {
        parent::__construct();
        $this->generateRandomStrings();
    }

    public function __destruct()
    {
        if (empty($this->random_strings)) return;

        $dir = $this->dir;

        foreach ($this->random_strings as $random_string) {
            if (file_exists("$dir$random_string.docx"))
                unlink("$dir$random_string.docx");
            
            if (file_exists("$dir$random_string.pdf"))
                unlink("$dir$random_string.pdf");

            if (file_exists("$dir$random_string"))
                exec("rm -rf $dir$random_string");
        }
    }

    public function testCommandNotExists()
    {
        $output = shell_exec('which whatever-fake-command');

        $this->assertEmpty($output);
    }

    public function testCommandExists()
    {
        $output = shell_exec('which lowriter');

        $this->assertNotEmpty($output, 'Command doesnt exists, run `apt install libreoffice-writer` to install');
    }

    public function testCommandWithoutFolderInstallationFlag()
    {
        $dir = $this->dir;
        $total_converted = 0;
        $promises = [];
        $random_strings = $this->getRandomStrings();

        $this->createRandomFiles();
        foreach ($random_strings as $random_string) {
            $random_input_file = "$dir$random_string.docx";
            $promises[$random_string] = Worker\enqueueCallable('exec', "lowriter --convert-to pdf $random_input_file --outdir $dir > /dev/null 2>&1");
        }

        $responses = Promise\wait(Promise\all($promises));
        foreach ($responses as $random_string => $response) {
            $random_output_file = "$dir$random_string.pdf";
            if (file_exists($random_output_file))
                $total_converted++;
        }

        $this->assertNotEquals(count($random_strings), $total_converted, 'All files are successfully converted without UserInstallation flag which is not expected!');
    }

    public function testCommandWithFolderInstallationFlag()
    {
        $dir = $this->dir;
        $total_converted = 0;
        $promises = [];
        $random_strings = $this->getRandomStrings();

        $this->createRandomFiles();
        foreach ($random_strings as $random_string) {
            $random_input_file = "$dir$random_string.docx";
            $random_dir = realpath($dir). DIRECTORY_SEPARATOR .$random_string;
            $promises[$random_string] = Worker\enqueueCallable('exec', "lowriter -env:UserInstallation=file://$random_dir --convert-to pdf $random_input_file --outdir $dir > /dev/null 2>&1");
        }

        $responses = Promise\wait(Promise\all($promises));
        foreach ($responses as $random_string => $response) {
            $random_output_file = "$dir$random_string.pdf";
            if (file_exists($random_output_file))
                $total_converted++;
        }

        $this->assertEquals(count($random_strings), $total_converted, 'Some files are not successfully converted with UserInstallation flag which is not expected!');
    }

    private function createRandomFiles()
    {
        $dir = $this->dir;
        foreach ($this->getRandomStrings() as $random_string) {
            copy($dir .'sample-word.docx', $dir.$random_string .'.docx');
        }
    }

    public function generateRandomStrings()
    {
        if (!empty($this->random_strings)) return;

        for ($i = 0; $i < $this->total_random_items_to_create; $i++) {
            $this->random_strings[] = $this->generateRandomString();
        }
    }

    private function getRandomStrings()
    {
        return $this->random_strings;
    }

    private function generateRandomString()
    {
        return uniqid(rand(), true);
    }
}