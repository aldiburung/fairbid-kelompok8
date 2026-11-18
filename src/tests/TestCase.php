<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->refreshApplication();

        if (DB::connection()->getDatabaseName() !== 'fairbid_test') {
            throw new RuntimeException('Pengujian harus memakai database fairbid_test.');
        }

        parent::setUp();
    }
}
