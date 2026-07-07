<?php
namespace ExcelleInsights\WhatsApp\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use ExcelleInsights\WhatsApp\Support\DatabaseLogger;

class DatabaseLoggerTest extends TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            $_ENV['DB_DSN'] ?? 'mysql:host=127.0.0.1;dbname=whatsapp',
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    protected function setUp(): void
    {
        self::$pdo->exec('DELETE FROM http_request_logs');
    }

    public function testInfoLogsToHttpRequestLogs(): void
    {
        $logger = new DatabaseLogger(self::$pdo);

        $logger->info('Test info message', [
            'method'           => 'POST',
            'url'              => 'https://graph.facebook.com/v22.0/test',
            'request_headers'  => ['Authorization' => 'Bearer test'],
            'request_body'     => ['key' => 'value'],
            'response_status'  => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
            'response_body'    => '{"success":true}',
        ]);

        $stmt = self::$pdo->query('SELECT * FROM http_request_logs ORDER BY id DESC LIMIT 1');
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row, 'No row inserted');
        $this->assertEquals('POST', $row['method']);
        $this->assertStringContainsString('graph.facebook.com', $row['url']);
        $this->assertStringContainsString('Bearer', $row['request_headers']);
        $this->assertStringContainsString('value', $row['request_body']);
        $this->assertEquals(200, (int) $row['response_status']);
    }

    public function testErrorLogsToHttpRequestLogs(): void
    {
        $logger = new DatabaseLogger(self::$pdo);

        $logger->error('Test error message', [
            'method'          => 'GET',
            'url'             => 'https://graph.facebook.com/v22.0/fail',
            'response_status' => 400,
            'response_body'   => '{"error":{"message":"Bad request"}}',
        ]);

        $stmt = self::$pdo->query('SELECT * FROM http_request_logs ORDER BY id DESC LIMIT 1');
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row, 'No row inserted');
        $this->assertEquals('GET', $row['method']);
        $this->assertEquals(400, (int) $row['response_status']);
    }

    public function testLogsWithNullOptionalFields(): void
    {
        $logger = new DatabaseLogger(self::$pdo);

        $logger->info('Minimal log', [
            'method' => 'DELETE',
            'url'    => 'https://graph.facebook.com/v22.0/resource',
        ]);

        $stmt = self::$pdo->query('SELECT * FROM http_request_logs ORDER BY id DESC LIMIT 1');
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row, 'No row inserted');
        $this->assertEquals('DELETE', $row['method']);
        $this->assertNull($row['request_body']);
        $this->assertNull($row['response_body']);
    }

    protected function tearDown(): void
    {
        self::$pdo->exec('DELETE FROM http_request_logs');
    }
}
