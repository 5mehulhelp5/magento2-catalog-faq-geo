<?php
/**
 * Magendoo Faq Structured Data Block Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Block\Faq;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Block\Faq\StructuredData;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Pins the JSON-LD encoding of shopper-supplied FAQ content.
 *
 * The output is emitted inside a <script type="application/ld+json"> element,
 * so a literal "</script>" in a question title terminates the element and
 * executes attacker markup (stored XSS). The encoder must emit the HEX_TAG/
 * AMP/APOS/QUOT-escaped form while remaining decodable JSON.
 */
#[CoversClass(StructuredData::class)]
#[AllowMockObjectsWithoutExpectations]
class StructuredDataTest extends TestCase
{
    /**
     * @var StructuredData
     */
    private StructuredData $block;

    protected function setUp(): void
    {
        /** @var StructuredData $block */
        $block = (new ObjectManagerHelper($this))->getObject(StructuredData::class);
        $this->block = $block;
    }

    public function testScriptBreakoutPayloadInTitleIsNeutralised(): void
    {
        $payload = '</script><img src=x onerror=alert(1)>';
        $this->block->setQuestions([$this->createQuestion($payload, 'Safe answer.')]);

        $json = $this->block->getStructuredDataJson();

        $this->assertNotSame('', $json);
        // The raw payload must never appear literally in the emitted JSON.
        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringNotContainsString('<img', $json);
        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
        // The closing tag must be present only in its escaped form:
        // JSON_HEX_TAG renders < as \u003C and > as \u003E, and the default
        // forward-slash escaping renders "/" as "\/".
        $this->assertStringContainsString('\u003C\/script\u003E', $json);
    }

    public function testEncodedJsonDecodesBackToOriginalTitle(): void
    {
        $payload = '</script><img src=x onerror=alert(1)>';
        $this->block->setQuestions([$this->createQuestion($payload, 'Safe answer.')]);

        $decoded = json_decode($this->block->getStructuredDataJson(), true);

        $this->assertIsArray($decoded);
        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('FAQPage', $decoded['@type']);
        $this->assertSame('Question', $decoded['mainEntity'][0]['@type']);
        $this->assertSame($payload, $decoded['mainEntity'][0]['name']);
        $this->assertSame('Safe answer.', $decoded['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function testAmpersandsAndQuotesAreHexEscaped(): void
    {
        $title = 'Tom & "Jerry" don\'t match';
        $this->block->setQuestions([$this->createQuestion($title, 'Answer.')]);

        $json = $this->block->getStructuredDataJson();

        $this->assertStringNotContainsString('&', $json);
        $this->assertStringNotContainsString('"Jerry"', $json);
        // JSON_HEX_AMP / JSON_HEX_QUOT / JSON_HEX_APOS escape &, " and '.
        $this->assertStringContainsString('\u0026', $json);
        $this->assertStringContainsString('\u0022', $json);
        $this->assertStringContainsString('\u0027', $json);
        $this->assertSame($title, json_decode($json, true)['mainEntity'][0]['name']);
    }

    public function testAnswerPrefersFullAnswerAndStripsHtml(): void
    {
        $question = $this->createQuestion(
            'How do I return an item?',
            '<p>Use the <strong>returns portal</strong>.</p>',
            'Short version.'
        );
        $this->block->setQuestions([$question]);

        $decoded = json_decode($this->block->getStructuredDataJson(), true);

        $this->assertSame(
            'Use the returns portal.',
            $decoded['mainEntity'][0]['acceptedAnswer']['text']
        );
    }

    public function testAnswerFallsBackToShortAnswerWhenFullAnswerIsEmpty(): void
    {
        $question = $this->createQuestion(
            'How do I return an item?',
            '',
            '<em>Short</em> version.'
        );
        $this->block->setQuestions([$question]);

        $decoded = json_decode($this->block->getStructuredDataJson(), true);

        $this->assertSame(
            'Short version.',
            $decoded['mainEntity'][0]['acceptedAnswer']['text']
        );
    }

    public function testNoQuestionsProducesEmptyString(): void
    {
        $this->block->setQuestions([]);

        $this->assertSame('', $this->block->getStructuredDataJson());
    }

    public function testUnicodeContentSurvivesEncoding(): void
    {
        $title = 'Livrare în România — cât durează?';
        $this->block->setQuestions([$this->createQuestion($title, 'Răspuns.')]);

        $json = $this->block->getStructuredDataJson();

        // JSON_UNESCAPED_UNICODE keeps the diacritics readable.
        $this->assertStringContainsString('România', $json);
        $this->assertSame($title, json_decode($json, true)['mainEntity'][0]['name']);
    }

    /**
     * Build a question stub with the fields the block reads.
     *
     * @param string $title
     * @param string $fullAnswer
     * @param string $shortAnswer
     * @return QuestionInterface
     */
    private function createQuestion(string $title, string $fullAnswer, string $shortAnswer = ''): QuestionInterface
    {
        $question = $this->createStub(QuestionInterface::class);
        $question->method('getTitle')->willReturn($title);
        $question->method('getFullAnswer')->willReturn($fullAnswer);
        $question->method('getShortAnswer')->willReturn($shortAnswer);

        return $question;
    }
}
