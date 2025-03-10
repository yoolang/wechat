<?php

namespace EasyWeChat\Tests\Kernel;

use EasyWeChat\Kernel\ServerResponse;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ServerResponseTest extends TestCase
{
    public function test_to_string()
    {
        $response = ServerResponse::make(new Response(200, ['X-Foo' => 'bar'], 'foo'));

        $response = explode("\r\n", $response);
        $this->assertEquals('HTTP/1.1 200 OK', $response[0]);
        $this->assertEquals('X-Foo: bar', $response[1]);
    }

    public function test_it_can_send_response()
    {
        \ob_start();
        $response = ServerResponse::make(new Response(200, ['X-Foo' => 'bar'], 'foo'));
        $response->sendContent();
        $contents = \ob_get_contents();
        \ob_end_clean();

        $this->assertSame('foo', $contents);
    }
}
