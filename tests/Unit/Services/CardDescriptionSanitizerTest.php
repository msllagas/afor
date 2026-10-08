<?php

use App\Enums\BoardListColor;
use App\Services\CardDescriptionSanitizer;

test('markup the editor produces is kept', function (string $html) {
    expect(app(CardDescriptionSanitizer::class)->sanitize($html))->toBe($html);
})->with([
    'text styles'                     => '<p>Plain <strong>bold</strong> <em>italic</em> <u>underlined</u> <s>struck</s> <code>code</code></p>',
    'headings'                        => '<h1>One</h1><h2>Two</h2><h3>Three</h3><h4>Four</h4>',
    'a bullet list'                   => '<ul><li><p>One</p><ul><li><p>Nested</p></li></ul></li></ul>',
    'a numbered list'                 => '<ol start="3"><li><p>Third</p></li></ol>',
    'a numbered list with letters'    => '<ol start="2" type="a"><li><p>First</p></li></ol>',
    'a quote'                         => '<blockquote><p>Quoted</p></blockquote>',
    'a code block'                    => '<pre><code>let a = 1 &lt; 2 &amp;&amp; "x";</code></pre>',
    'a code block with a language'    => '<pre><code class="language-php">echo 1;</code></pre>',
    'a line break'                    => '<p>One<br>Two</p>',
    'a divider'                       => '<p>Above</p><hr><p>Below</p>',
    'a web link'                      => '<p><a target="_blank" rel="noopener noreferrer nofollow" href="https://example.com/tent?size=2&amp;a=&quot;1&quot;#top">link</a></p>',
    'an email link'                   => '<p><a target="_blank" rel="noopener noreferrer nofollow" href="mailto:mandy@example.com">mandy</a></p>',
    'a phone link'                    => '<p><a target="_blank" rel="noopener noreferrer nofollow" href="tel:+123">call</a></p>',
    'a link to another page here'     => '<p><a target="_blank" rel="noopener noreferrer nofollow" href="/boards/1?card=2#top">card</a></p>',
    'a link to a place on the page'   => '<p><a target="_blank" rel="noopener noreferrer nofollow" href="#top">top</a></p>',
    'special characters'              => '<p>Fish &amp; chips &lt;3 &gt; nothing</p>',
    'quotes and a non-breaking space' => '<p>Don\'t forget the "big" tent&nbsp;bag</p>',
    'accents and emoji'               => '<p>café 🏕️ ✓</p>',
    'text with a style but no markup' => '<p><span>Plain</span></p>',
]);

test('every list colour keeps its highlight', function (BoardListColor $color) {
    $html = "<p><mark class=\"list-{$color->value}\">marked</mark></p>";

    expect(app(CardDescriptionSanitizer::class)->sanitize($html))->toBe($html);
})->with(BoardListColor::cases());

test('unsafe markup is removed', function (string $html, string $expected) {
    expect(app(CardDescriptionSanitizer::class)->sanitize($html))->toBe($expected);
})->with([
    'a script, with its code'             => ['<p>Hi</p><script>steal()</script>', '<p>Hi</p>'],
    'a style sheet, with its rules'       => ['<p>Hi</p><style>p { display: none }</style>', '<p>Hi</p>'],
    'an event handler'                    => ['<p onclick="steal()">Hi</p>', '<p>Hi</p>'],
    'an inline style'                     => ['<p style="position: fixed">Hi</p>', '<p>Hi</p>'],
    'an image'                            => ['<p>Hi<img src="x" onerror="steal()"></p>', '<p>Hi</p>'],
    'a frame'                             => ['<p>Hi</p><iframe src="https://evil.example"></iframe>', '<p>Hi</p>'],
    'a form'                              => ['<form action="https://evil.example"><input name="password"></form>', ''],
    'an svg'                              => ['<svg onload="steal()"><circle r="1"></circle></svg>', ''],
    'a comment'                           => ['<p>Hi</p><!-- steal() -->', '<p>Hi</p>'],
    'a link that opens in this tab'       => [
        '<p><a href="https://example.com" target="_self" rel="opener">Hi</a></p>',
        '<p><a href="https://example.com" target="_blank" rel="noopener noreferrer nofollow">Hi</a></p>',
    ],
    'a highlight with any other class'    => ['<p><mark class="fixed inset-0">Hi</mark></p>', '<p><mark>Hi</mark></p>'],
    'a highlight pasted with a colour'    => [
        '<p><mark data-color="red" style="background-color: red; color: inherit;">Hi</mark></p>',
        '<p><mark>Hi</mark></p>',
    ],
    'a code class that is not a language' => ['<pre><code class="fixed inset-0">a</code></pre>', '<pre><code>a</code></pre>'],
    'a list start that is not a number'   => ['<ol start="1&quot; onclick=&quot;steal()"><li><p>a</p></li></ol>', '<ol><li><p>a</p></li></ol>'],
]);

test('a link that could run code loses its address', function (string $href) {
    $html = '<p><a href="'.str_replace('"', '&quot;', $href).'">Hi</a></p>';

    expect(app(CardDescriptionSanitizer::class)->sanitize($html))
        ->toBe('<p><a target="_blank" rel="noopener noreferrer nofollow">Hi</a></p>');
})->with([
    'javascript'                    => 'javascript:steal()',
    'javascript in capitals'        => 'JaVaScRiPt:steal()',
    'javascript after a space'      => ' javascript:steal()',
    'javascript after a new line'   => "\njavascript:steal()",
    'javascript after a control'    => "\x01javascript:steal()",
    'javascript split by a tab'     => "java\tscript:steal()",
    'javascript split by a newline' => "java\nscript:steal()",
    'javascript written as markup'  => 'java&Tab;script&colon;steal()',
    'vbscript'                      => 'vbscript:msgbox(1)',
    'a data page'                   => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
    'a local file'                  => 'file:///etc/passwd',
    'a blob'                        => 'blob:https://example.com/abc',
    'an unknown scheme'             => 'steal:now',
]);

test('sanitizing twice changes nothing more', function (string $html) {
    $sanitizer = app(CardDescriptionSanitizer::class);
    $once = $sanitizer->sanitize($html);

    expect($sanitizer->sanitize($once))->toBe($once);
})->with([
    'script in a link' => '<p><a href="javascript:steal()" onclick="steal()">Hi</a><script>steal()</script></p>',
    'quotes in a link' => '<p><a href="https://example.com/?q=&quot;x&quot;">Don\'t</a></p>',
    'nested markup'    => '<ul><li><p><strong><em>Hi</em></strong></p></li></ul><noscript><p title="</noscript><img src=x onerror=steal()>">',
]);

test('a description with no text left is empty', function (string $html) {
    expect(app(CardDescriptionSanitizer::class)->sanitize($html))->toBe('');
})->with([
    'only a script' => '<script>steal()</script>',
    'only an image' => '<img src="x" onerror="steal()">',
]);

test('a long description is not cut short', function () {
    $html = '<p>'.str_repeat('a', 65000).'</p>';

    expect(app(CardDescriptionSanitizer::class)->sanitize($html))->toBe($html);
});
