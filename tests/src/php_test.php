<?php

require_once(__DIR__ . '/testcase.php');

class PhpTest extends TestCase {
    /**
     * @param integer $intver
     * @dataProvider dataPhpVersions
     */
    public function testDockerfile(int $intver): void {
        $file = static::getContents("php{$intver}/Dockerfile");
        $this->assertStringStartsWith("FROM totara/docker-dev-php{$intver}\n", $file);
    }
    /**
     * @param integer $intver
     * @dataProvider dataPhpVersions
     */
    public function testFpmConf(int $intver): void {
        $ver = intdiv($intver, 10) . '.' . ($intver % 10);
        $file = static::getContents("php{$intver}/fpm.conf");
        $this->assertIsInt(preg_match_all('/^\s*(?!;)\s*([^\s=]+)\s*=\s*(.*)$/m', $file, $matches, PREG_SET_ORDER));
        $entries = array_reduce($matches, function ($entries, $match) {
            return $entries + [$match[1] => $match[2]];
        }, []);
        static::assertEquals('totara', $entries['user']);
        static::assertEquals('totara', $entries['group']);
        static::assertEquals('totara', $entries['listen.owner']);
        static::assertEquals('totara', $entries['listen.group']);
        static::assertEquals("/run/php{$ver}/php-fpm.sock", $entries['listen']);
    }
    /**
     * @param integer $intver
     * @dataProvider dataPhpVersions
     */
    public function testPhpIni(int $intver): void {
        $file = static::getContents("php{$intver}/php.ini");
        $this->assertIsInt(preg_match_all('/^\s*(?![;#])\s*([^\s=]+)\s*=\s*(.*)$/m', $file, $matches, PREG_SET_ORDER));
        $entries = array_reduce($matches, function ($entries, $match) {
            return $entries + [$match[1] => $match[2]];
        }, []);
        static::assertEquals('On', $entries['log_errors']);
        static::assertEquals('/dev/stderr', $entries['error_log']);
        static::assertEquals('Pacific/Auckland', $entries['date.timezone']);
        static::assertEquals('1', $entries['cgi.fix_pathinfo']);
    }
    /**
     * @param integer $intver
     * @dataProvider dataPhpVersions
     */
    public function testComposerYaml(int $intver): void {
        $ver = intdiv($intver, 10) . '.' . ($intver % 10);
        // services/php-{ver}
        static::assertArrayHasKey("php-{$ver}", $this->yaml['services']);
        static::assertEquals("php{$intver}", $this->yaml['services']["php-{$ver}"]['build']);
        static::assertEquals("totara_docker_php{$intver}", $this->yaml['services']["php-{$ver}"]['container_name']);
        static::assertContains('TZ=Pacific/Auckland', $this->yaml['services']["php-{$ver}"]['environment']);
        static::assertEquals('/var/www/totara/src', $this->yaml['services']["php-{$ver}"]['working_dir']);
        static::assertContains('../src/:/var/www/totara/src/', $this->yaml['services']["php-{$ver}"]['volumes']);
        static::assertContains('../data/:/var/www/totara/data/', $this->yaml['services']["php-{$ver}"]['volumes']);
        static::assertContains("php{$intver}-socket:/run/php{$ver}/", $this->yaml['services']["php-{$ver}"]['volumes']);
        static::assertContains('memcached-socket:/run/memcached/', $this->yaml['services']["php-{$ver}"]['volumes']);
        static::assertContains('totara-docker', $this->yaml['services']["php-{$ver}"]['networks']);
        foreach ($this->dataPgsqlVersions() as [$pgsqlver]) {
            static::assertContains("pgsql{$pgsqlver}-socket:/run/pgsql{$pgsqlver}/", $this->yaml['services']["php-{$ver}"]['volumes']);
        }
        // services/lighttpd
        static::assertContains("php{$intver}-socket:/run/php{$ver}/", $this->yaml['services']['lighttpd']['volumes']);
        static::assertContains("totara{$intver}", $this->yaml['services']['lighttpd']['networks']['totara-docker']['aliases']);
        static::assertContains("behat.totara{$intver}", $this->yaml['services']['lighttpd']['networks']['totara-docker']['aliases']);
        for ($i = 0; $i <= 9; $i++) {
            static::assertContains("behat{$i}.totara{$intver}", $this->yaml['services']['lighttpd']['networks']['totara-docker']['aliases']);
        }
        // volumes
        static::assertArrayHasKey("php{$intver}-socket", $this->yaml['volumes']);
    }
    /**
     * @param integer $intver
     * @dataProvider dataPhpVersions
     */
    public function testLighttpdConf(int $intver): void {
        $ver = intdiv($intver, 10) . '.' . ($intver % 10);
        $file = static::getContents('lighttpd/lighttpd.conf');
        if ($intver === 73) {
            static::assertMatchesRegularExpression("/\\\$HTTP\[\"host\"\]\s*=~\s*\"\"\s*{\s*var\.phpver\s*=\s*\"{$ver}\"/", $file);
        } else {
            static::assertMatchesRegularExpression("/\\\$HTTP\[\"host\"\]\s*=~\s*\"totara{$intver}\\\$\"\s*{\s*var\.phpver\s*=\s*\"{$ver}\"/", $file);
        }
    }
    /**
     * @param integer $intver
     * @dataProvider dataPhpVersions
     */
    public function testBash(int $intver): void {
        $ver = intdiv($intver, 10) . '.' . ($intver % 10);
        $file = static::getContents("bin/tbash-{$intver}");
        [$line1, $line2,] = explode("\n", $file, 3);
        static::assertEquals('#!/usr/bin/env bash', $line1);
        static::assertEquals("\"\${0%/*}/tbash\" php-{$ver} /bin/bash", $line2);
    }
}
