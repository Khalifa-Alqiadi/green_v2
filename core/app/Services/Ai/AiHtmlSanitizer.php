<?php

namespace App\Services\Ai;

use DOMDocument;
use DOMElement;
use DOMXPath;
use HTMLPurifier;
use HTMLPurifier_Config;

class AiHtmlSanitizer
{
    public function sanitize(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'h2[class],h3[class],h4[class],p[class],ul[class],ol[class],li[class],strong[class],em[class],blockquote[class],table[class],thead,tbody,tr,th[colspan|rowspan|scope],td[colspan|rowspan],section[class],div[class],a[class|href|title|target|rel],br,hr[class]');
        $config->set('CSS.AllowedProperties', []);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('URI.DisableExternalResources', true);
        $config->set('Attr.EnableID', false);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.DefinitionID', 'smartend-ai-html5');
        $config->set('HTML.DefinitionRev', 1);
        if ($definition = $config->maybeGetRawHTMLDefinition()) {
            $definition->addElement('section', 'Block', 'Flow', 'Common', ['class' => 'Class']);
        }

        $clean = (new HTMLPurifier($config))->purify($html);

        return $this->filterAttributes($clean);
    }

    public function plainText(string $value, int $limit = 50000): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($value))), 0, $limit);
    }

    private function filterAttributes(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="ai-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//*[@class]') ?: [] as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }
            $classes = array_filter(preg_split('/\s+/', $element->getAttribute('class')) ?: [], fn ($class) => $this->allowedClass($class));
            $classes === [] ? $element->removeAttribute('class') : $element->setAttribute('class', implode(' ', array_unique($classes)));
        }

        foreach ($xpath->query('//a[@target="_blank"]') ?: [] as $link) {
            $link->setAttribute('rel', 'noopener noreferrer');
        }

        $root = $dom->getElementById('ai-root');
        $output = '';
        foreach ($root?->childNodes ?? [] as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private function allowedClass(string $class): bool
    {
        return preg_match('/^(row|container(-fluid)?|col(-(sm|md|lg|xl|xxl))?-(1[0-2]|[1-9])|card|card-(body|header|footer|title|text)|alert|alert-(primary|secondary|success|danger|warning|info|light|dark)|table|table-(responsive|striped|bordered|hover)|list-(group|unstyled)|list-group-item|btn|btn-(primary|secondary|outline-primary|outline-secondary|lg|sm)|text-(center|start|end|primary|secondary|dark|white|muted)|display-[1-6]|lead|fw-(bold|semibold)|small|d-(flex|grid|block)|align-items-(center|start|stretch)|justify-content-(between|center|start|end)|gap-[1-5]|h-100|w-100|shadow-sm|rounded(-3)?|bg-(light|primary|secondary|dark)|[mp][trblxy]?-[0-5])$/', $class) === 1;
    }
}
