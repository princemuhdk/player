<?php

/*
 * This file is part of the Blackfire Player package.
 *
 * (c) Fabien Potencier <fabien@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Player\Tests\ExpressionLanguage;

use Blackfire\Player\Context;
use Blackfire\Player\Exception\SecurityException;
use Blackfire\Player\ExpressionLanguage\ExpressionLanguage;
use Blackfire\Player\ExpressionLanguage\Provider;
use Blackfire\Player\ExpressionLanguage\UploadFile;
use Blackfire\Player\ValueBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ProviderTest extends TestCase
{
    public function testItHasFunctions()
    {
        $provider = new Provider();

        $language = new ExpressionLanguage(null, [$provider]);

        $res = $language->evaluate('trim("   hello  ")');
        $this->assertEquals('hello', $res);

        $res = $language->evaluate('file("file", "name")');
        $this->assertInstanceOf(UploadFile::class, $res);
    }

    public function testSandboxModeFileAbsoluteFile()
    {
        $provider = new Provider(null, true);
        $language = new ExpressionLanguage(null, [$provider]);

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('The "file" provider does not support relative file paths in the sandbox mode (use the "fake()" function instead).');
        $language->evaluate('file("file", "name")');
    }

    public function testSandboxModeFakerImageProvider()
    {
        $provider = new Provider(null, true);
        $language = new ExpressionLanguage(null, [$provider]);
        $tmpDir = sprintf('%s/blackfire-tmp-dir/%s/%s', sys_get_temp_dir(), date('y-m-d-H-m-s'), mt_rand());
        $extra = new ValueBag();
        $extra->set('tmp_dir', $tmpDir);
        $fs = new Filesystem();
        $fs->mkdir($tmpDir);

        $res = $language->evaluate('fake("image")', ['_extra' => $extra]);
        $this->assertStringStartsWith($tmpDir, $res);
    }

    public function testSandboxModeFakerFileProvider()
    {
        $provider = new Provider(null, true);
        $language = new ExpressionLanguage(null, [$provider]);

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('The "file" faker provider is not supported in sandbox mode.');
        $language->evaluate('fake("file", "a")');
    }
}