<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\TestCase;

/**
 * AuthController::isMissingTableError()/isUniqueConstraintError() only recognized
 * MySQL's SQLSTATE codes (42S02, 23000) and MySQL's "Duplicate entry" message text.
 * This app deploys on Postgres (Neon) in production, where the same failures carry
 * different codes (42P01, 23505) and message text ("duplicate key value violates
 * unique constraint") - so a duplicate-email registration or a missing-table error
 * on Postgres fell through to `throw $e`, a raw 500, instead of the friendly
 * validation message these checks exist to produce. Verifies both drivers' shapes
 * are now recognized, using synthetic QueryExceptions (no real DB connection needed).
 */
class AuthControllerDbErrorDetectionTest extends TestCase
{
    private function makeException(string $sqlstate, string $message): QueryException
    {
        // Real PDO SQLSTATE codes are strings (e.g. '42S02', '42P01') even though
        // PDOException's own constructor type-hints an int $code - PDO sets the
        // protected property directly from C, bypassing that check. Reflection
        // reproduces the same real-world shape here.
        $previous = new \PDOException($message);
        (new \ReflectionProperty(\PDOException::class, 'code'))->setValue($previous, $sqlstate);

        return new QueryException('pgsql', 'select 1', [], $previous);
    }

    private function invoke(string $method, QueryException $e): bool
    {
        $controller = new AuthController;
        $reflection = new \ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, $e);
    }

    public function test_detects_mysql_unique_violation(): void
    {
        $e = $this->makeException('23000', "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'a@b.com' for key 'users_email_unique'");
        $this->assertTrue($this->invoke('isUniqueConstraintError', $e));
    }

    public function test_detects_postgres_unique_violation(): void
    {
        $e = $this->makeException('23505', 'SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "users_email_unique"');
        $this->assertTrue($this->invoke('isUniqueConstraintError', $e));
    }

    public function test_detects_mysql_missing_table(): void
    {
        $e = $this->makeException('42S02', "SQLSTATE[42S02]: Base table or view not found: 1146 Table 'inventory_db.users' doesn't exist");
        $this->assertTrue($this->invoke('isMissingTableError', $e));
    }

    public function test_detects_postgres_missing_table(): void
    {
        $e = $this->makeException('42P01', 'SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "users" does not exist');
        $this->assertTrue($this->invoke('isMissingTableError', $e));
    }

    public function test_unrelated_errors_are_not_misclassified(): void
    {
        $e = $this->makeException('42601', 'SQLSTATE[42601]: Syntax error or access violation');
        $this->assertFalse($this->invoke('isUniqueConstraintError', $e));
        $this->assertFalse($this->invoke('isMissingTableError', $e));
    }
}
