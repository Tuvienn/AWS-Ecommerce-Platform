<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Seed dữ liệu danh mục/tài khoản mẫu cho từng test độc lập.
     */
    protected $seed = true;
}
