<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;

/**
 * Neon requires TLS SNI to route a connection to the right compute endpoint. Older
 * libpq builds (notably the one bundled with several XAMPP-for-Windows PHP releases)
 * don't send SNI, so a direct connection fails with "Endpoint ID is not specified"
 * even though host/port/credentials are all correct - see https://neon.tech/sni.
 * Neon's own documented workaround is to pass the endpoint ID via the libpq `options`
 * connection parameter, but Laravel's stock PostgresConnector only ever appends the
 * four SSL-related keys (sslmode/sslcert/sslkey/sslrootcert) to the DSN. This adds
 * support for a `libpq_options` config key (deliberately not named `options` - that
 * key is already reserved by Laravel's base Connector for an array of PDO driver
 * options, and colliding with it breaks every connection with a TypeError) so the
 * workaround can be set entirely from config/env, with no effect on connections that
 * don't set it (e.g. plain local Postgres, or any libpq new enough to not need this).
 */
class NeonPostgresConnector extends PostgresConnector
{
    protected function addSslOptions($dsn, array $config)
    {
        $dsn = parent::addSslOptions($dsn, $config);

        if (! empty($config['libpq_options'])) {
            $dsn .= ";options='".str_replace("'", "\\'", $config['libpq_options'])."'";
        }

        return $dsn;
    }
}
