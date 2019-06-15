<?php

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
    	define("TEST_TOKEN", "1LQxW0CjRz8ZaY1GvOxoCuHlNS7oecmQxEYJ4V/Fpd+WmfeUOwRVhw==");
        return require __DIR__.'/../bootstrap/app.php';
    }
}
