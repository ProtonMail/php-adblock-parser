<?php
namespace Limonte\Tests;

use Limonte\AdblockRule;
use Limonte\InvalidRuleException;

class AdblockRuleTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRegex()
    {
        $rule = new AdblockRule('/slashes should be trimmed/');
        $this->assertEquals('slashes should be trimmed', $rule->getRegex());
    }

    /**
     * @expectedException Limonte\InvalidRuleException
     */
    public function testInvalidRegex()
    {
        $this->expectException(InvalidRuleException::class);
        $invalidRule = new AdblockRule('//');
    }

    public function testEscapeSpecialCharacters()
    {
        $rule = new AdblockRule('.$+?{}()[]/\\');
        $this->assertSame('\.\$\+\?\{\}\(\)\[\]\/\\\\', $rule->getRegex());
    }

    public function testCaret()
    {
        $rule = new AdblockRule('domain^');
        $this->assertSame('domain([^\w\d_\-.%]|$)', $rule->getRegex());
    }

    public function testAsterisk()
    {
        $rule = new AdblockRule('domain*');
        $this->assertSame('domain.*', $rule->getRegex());
    }

    public function testVerticalBars()
    {
        $rule = new AdblockRule('||domain');
        $this->assertSame('^([^:\/?#]+:)?(\/\/([^\/?#]*\.)?)?domain', $rule->getRegex());

        $rule = new AdblockRule('|domain');
        $this->assertSame('^domain', $rule->getRegex());

        $rule = new AdblockRule('domain|bl||ah');
        $this->assertSame('domain\|bl\|\|ah', $rule->getRegex());
    }

    public function testMatchUrl()
    {
        $rule = new AdblockRule('swf|');
        $this->assertTrue($rule->matchUrl("http://example.com/annoyingflash.swf"));
        $this->assertFalse($rule->matchUrl("http://example.com/swf/index.html"));
    }

    public function testComment()
    {
        $rule = new AdblockRule('!this is comment');
        $this->assertTrue($rule->isComment());
        $rule = new AdblockRule('[Adblock Plus 1.1]');
        $this->assertTrue($rule->isComment());
        $rule = new AdblockRule('non-comment rule');
        $this->assertFalse($rule->isComment());
    }
}
