<?php

namespace App\Services\Ai;

use DOMDocument;
use DOMNode;

class HtmlTranslationService
{
    public function __construct(private readonly AiHtmlSanitizer $sanitizer) {}

    public function segments(string $field, string $value, bool $rich): array
    {
        if (!$rich) {
            return [['id' => $field, 'text' => $this->sanitizer->plainText($value)]];
        }

        [$dom, $root] = $this->document($this->sanitizer->sanitize($value));
        $segments = [];
        $index = 0;
        $this->walkText($root, function (DOMNode $node) use (&$segments, &$index, $field) {
            if (trim($node->nodeValue ?? '') !== '') {
                $segments[] = ['id' => $field.':'.$index++, 'text' => $node->nodeValue];
            }
        });

        return $segments;
    }

    public function reconstruct(string $field, string $value, array $translated, bool $rich): string
    {
        if (!$rich) {
            return $this->sanitizer->plainText((string) ($translated[$field] ?? $value));
        }

        [$dom, $root] = $this->document($this->sanitizer->sanitize($value));
        $index = 0;
        $this->walkText($root, function (DOMNode $node) use (&$index, $field, $translated) {
            if (trim($node->nodeValue ?? '') !== '') {
                $id = $field.':'.$index++;
                if (array_key_exists($id, $translated)) {
                    $node->nodeValue = (string) $translated[$id];
                }
            }
        });

        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return $this->sanitizer->sanitize($html);
    }

    private function document(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="translation-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        return [$dom, $dom->getElementById('translation-root')];
    }

    private function walkText(?DOMNode $node, callable $callback): void
    {
        if (!$node) {
            return;
        }
        foreach (iterator_to_array($node->childNodes) as $child) {
            $child->nodeType === XML_TEXT_NODE ? $callback($child) : $this->walkText($child, $callback);
        }
    }
}
