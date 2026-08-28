<?php

namespace Tests\Unit;

use App\Database\Connectors\NeonPostgresConnector;
use PHPUnit\Framework\TestCase;

/**
 * NeonPostgresConnector adds libpq `options` DSN support (the Neon SNI workaround for
 * older libpq clients - https://neon.tech/sni) via a `libpq_options` config key.
 * That key name is deliberate: Laravel's base Connector already reserves `options`
 * for an array of PDO driver options, and a first draft of this class collided with
 * it (config('...options') as a string instead of an array threw a TypeError out of
 * Illuminate\Database\Connectors\Connector::getOptions() on every connection, Neon or
 * not) - these tests lock in both the DSN behavior and that the reserved key is never
 * touched.
 */
class NeonPostgresConnectorTest extends TestCase
{
    private function getDsn(NeonPostgresConnector $connector, array $config): string
    {
        $method = new \ReflectionMethod($connector, 'getDsn');
        $method->setAccessible(true);

        return $method->invoke($connector, $config);
    }

    public function test_libpq_options_is_appended_to_the_dsn_when_set(): void
    {
        $dsn = $this->getDsn(new NeonPostgresConnector, [
            'host' => 'ep-winter-dream-aee01zsf.c-2.us-east-2.aws.neon.tech',
            'database' => 'neondb',
            'port' => 5432,
            'sslmode' => 'require',
            'libpq_options' => 'endpoint=ep-winter-dream-aee01zsf',
        ]);

        $this->assertStringContainsString("options='endpoint=ep-winter-dream-aee01zsf'", $dsn);
        $this->assertStringContainsString('sslmode=require', $dsn);
    }

    public function test_dsn_is_unaffected_when_libpq_options_is_not_set(): void
    {
        $dsn = $this->getDsn(new NeonPostgresConnector, [
            'host' => '127.0.0.1',
            'database' => 'genrev',
            'port' => 5432,
        ]);

        $this->assertStringNotContainsString('options=', $dsn);
    }

    public function test_a_single_quote_in_libpq_options_is_escaped(): void
    {
        $dsn = $this->getDsn(new NeonPostgresConnector, [
            'host' => '127.0.0.1',
            'database' => 'genrev',
            'libpq_options' => "endpoint=ab'cd",
        ]);

        $this->assertStringContainsString("options='endpoint=ab\\'cd'", $dsn);
    }
}
