<?php

abstract class TestCase extends PHPUnit\Framework\TestCase {
    /** @var array */
    protected $yaml;

    public function setUp(): void {
        $this->yaml = Spyc::YAMLLoadString(static::getContents('docker-compose.yml'));
        parent::setUp();
    }
    public function tearDown(): void {
        $this->yaml = null;
        parent::tearDown();
    }
    public static function getPath(string $path): string {
        return __DIR__ . '/../../' . $path;
    }
    public static function getContents(string $path): string {
        $contents = file_get_contents(self::getPath($path));
        static::assertNotFalse($contents);
        return $contents;
    }
    public function dataPgsqlVersions(): array {
        return [[12]];
    }
    public function dataPhpVersions(): array {
        return [[73], [74], [80], [81]];
    }
}
