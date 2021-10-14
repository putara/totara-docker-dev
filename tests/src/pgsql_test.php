<?php

require_once(__DIR__ . '/testcase.php');

class PgsqlTest extends TestCase {
    /**
     * @param integer $intver
     * @dataProvider dataPgsqlVersions
     */
    public function testComposerYaml(int $intver): void {
        // services/pgsql{intver}
        static::assertArrayHasKey("pgsql{$intver}", $this->yaml['services']);
        static::assertStringStartsWith("postgres:{$intver}", $this->yaml['services']["pgsql{$intver}"]['image']);
        static::assertEquals("totara_docker_pgsql{$intver}", $this->yaml['services']["pgsql{$intver}"]['container_name']);
        static::assertContains('TZ=Pacific/Auckland', $this->yaml['services']["pgsql{$intver}"]['environment']);
        static::assertContains("pgsql{$intver}-socket:/run/postgresql/", $this->yaml['services']["pgsql{$intver}"]['volumes']);
        static::assertContains("../db/pgsql{$intver}/:/var/lib/postgresql/data/", $this->yaml['services']["pgsql{$intver}"]['volumes']);
        static::assertContains("./pgsql{$intver}/postgres.conf:/etc/postgresql/postgresql.conf:ro", $this->yaml['services']["pgsql{$intver}"]['volumes']);
    }
    /**
     * @param integer $intver
     * @dataProvider dataPgsqlVersions
     */
    public function testPostgresConf(int $intver): void {
        $file = static::getContents("pgsql{$intver}/postgres.conf");
        $this->assertIsInt(preg_match_all('/^\s*(?!#)\s*([^\s=]+)\s*=\s*(.*)$/m', $file, $matches, PREG_SET_ORDER));
        $entries = array_reduce($matches, function ($entries, $match) {
            return $entries + [$match[1] => $match[2]];
        }, []);
        static::assertEquals("''", $entries['listen_addresses']);
    }
}
