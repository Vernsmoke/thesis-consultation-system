<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $database = $app['config']->get('database.connections.mysql');

        // Runs before RefreshDatabase can migrate/reset anything.
        if ($app['config']->get('database.default') !== 'mysql'
            || $database['database'] !== 'thesis_consultation_week13_test'
            || $database['username'] !== 'thesis_week13_test'
            || $database['host'] !== '127.0.0.1'
            || (string) $database['port'] !== '3306'
            || ! empty($database['url'])
            || ! empty($database['unix_socket'])) {
            throw new \RuntimeException('Tests require the isolated Project 29 test database and test-only account.');
        }

        return $app;
    }
}
