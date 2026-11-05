<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_removes_script_tags(): void
    {
        $out = HtmlSanitizer::clean('<p>ok</p><script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringContainsString('ok', $out);
    }

    public function test_removes_event_handler_attributes(): void
    {
        $out = HtmlSanitizer::clean('<img src="/api/v1/files/1" onerror="alert(1)">');

        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringContainsString('/api/v1/files/1', $out);
    }

    public function test_removes_javascript_urls(): void
    {
        $out = HtmlSanitizer::clean('<a href="javascript:alert(1)">x</a>');

        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringContainsString('x', $out);
    }

    public function test_keeps_safe_markup(): void
    {
        $out = HtmlSanitizer::clean('<h2>Title</h2><p><strong>bold</strong></p>');

        $this->assertStringContainsString('<h2>Title</h2>', $out);
        $this->assertStringContainsString('<strong>bold</strong>', $out);
    }
}
