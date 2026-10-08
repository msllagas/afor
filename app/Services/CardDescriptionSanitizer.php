<?php

namespace App\Services;

use App\Enums\BoardListColor;
use Dom\HTMLDocument;
use Illuminate\Container\Attributes\Singleton;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Limits a card description to the markup its editor (components/Tiptap.vue) can produce, so what is stored is
 * safe for anything that renders it later. Keep the allow-list in step with the editor's extensions.
 */
#[Singleton]
class CardDescriptionSanitizer
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            // The request already caps the size, and the default limit would cut a long description short without a word.
            ->withMaxInputLength(-1)
            // The same links the editor accepts, so one it let a user make is never removed behind their back.
            ->allowLinkSchemes(['http', 'https', 'ftp', 'ftps', 'mailto', 'tel', 'callto', 'sms', 'cid', 'xmpp'])
            ->allowRelativeLinks()
            // The editor opens every link in a new tab and never passes the page on to it.
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withAttributeSanitizer($this->attributeSanitizer());

        foreach (['p', 'h1', 'h2', 'h3', 'h4', 'strong', 'em', 's', 'u', 'blockquote', 'pre', 'ul', 'li', 'br', 'hr', 'span'] as $element) {
            $config = $config->allowElement($element);
        }

        $this->sanitizer = new HtmlSanitizer(
            $config
                ->allowElement('a', ['href', 'target', 'rel'])
                ->allowElement('ol', ['start', 'type'])
                ->allowElement('code', ['class'])
                ->allowElement('mark', ['class'])
        );
    }

    /**
     * The description with everything the editor can't produce taken out, written the way the editor writes it.
     */
    public function sanitize(string $html): string
    {
        $safeHtml = $this->sanitizer->sanitize($html);

        if ($safeHtml === '') {
            return '';
        }

        // The sanitizer escapes quotes and writes `<br />`, which differs from the editor's own HTML.
        // The card dialog saves whenever the two differ, so opening a card would send a save for nothing.
        // Writing the clean markup out again as a browser does makes the two match.
        $document = HTMLDocument::createFromString("<!DOCTYPE html><body>{$safeHtml}</body>", LIBXML_NOERROR);

        return $document->body->innerHTML;
    }

    /**
     * Checks the attributes whose values the editor limits to a few.
     */
    private function attributeSanitizer(): AttributeSanitizerInterface
    {
        $highlightClasses = array_map(fn (BoardListColor $color) => "list-{$color->value}", BoardListColor::cases());

        return new class($highlightClasses) implements AttributeSanitizerInterface
        {
            /**
             * @param  list<string>  $highlightClasses
             */
            public function __construct(private readonly array $highlightClasses) {}

            public function getSupportedElements(): array
            {
                return ['mark', 'code', 'ol'];
            }

            public function getSupportedAttributes(): array
            {
                return ['class', 'start', 'type'];
            }

            public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
            {
                $isValid = match ("{$element}.{$attribute}") {
                    'mark.class' => in_array($value, $this->highlightClasses, true),
                    'code.class' => preg_match('/^language-[\w+#.-]+$/', $value) === 1,
                    'ol.start'   => preg_match('/^-?\d{1,9}$/', $value) === 1,
                    'ol.type'    => in_array($value, ['1', 'a', 'A', 'i', 'I'], true),
                    default      => false,
                };

                return $isValid ? $value : null;
            }
        };
    }
}
