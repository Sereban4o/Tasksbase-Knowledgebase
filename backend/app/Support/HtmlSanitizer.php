<?php

namespace App\Support;

/**
 * Minimal HTML sanitizer for user-generated rich-text content.
 *
 * Removes dangerous elements and attributes that enable stored XSS while
 * keeping the markup produced by the editor.
 */
class HtmlSanitizer
{
    private const DANGEROUS_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'link', 'meta', 'base'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($doc);

        // Remove dangerous elements entirely.
        foreach (self::DANGEROUS_TAGS as $tag) {
            foreach (iterator_to_array($xpath->query("//{$tag}") ?: []) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Remove event handlers and dangerous URLs.
        foreach (iterator_to_array($xpath->query('//*[@*]') ?: []) as $element) {
            if (! $element instanceof \DOMElement) {
                continue;
            }

            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);

                if (str_starts_with($name, 'on')) {
                    $element->removeAttribute($attribute->name);
                    continue;
                }

                if (! in_array($name, ['href', 'src', 'xlink:href', 'formaction', 'srcdoc'], true)) {
                    continue;
                }

                $compact = strtolower(preg_replace('/\s+/', '', $value) ?? '');

                if (str_starts_with($compact, 'javascript:')
                    || str_starts_with($compact, 'vbscript:')
                    || $name === 'srcdoc'
                ) {
                    $element->removeAttribute($attribute->name);
                    continue;
                }

                if (str_starts_with($compact, 'data:')
                    && ! ($name === 'src' && preg_match('#^data:image/(png|jpe?g|gif|webp);#i', $value))
                ) {
                    $element->removeAttribute($attribute->name);
                }
            }
        }

        $root = $doc->getElementById('__root__');
        if (! $root) {
            return '';
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $doc->saveHTML($child);
        }

        return $output;
    }
}
