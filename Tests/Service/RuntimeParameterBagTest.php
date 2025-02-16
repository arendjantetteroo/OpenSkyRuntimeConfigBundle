<?php

namespace OpenSky\Bundle\RuntimeConfigBundle\Tests\Service;

use OpenSky\Bundle\RuntimeConfigBundle\Model\ParameterProviderInterface;
use OpenSky\Bundle\RuntimeConfigBundle\Service\RuntimeParameterBag;
use OpenSky\Bundle\RuntimeConfigBundle\Service\RuntimeParameterBagLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

class RuntimeParameterBagTest extends TestCase
{
    public function testShouldImplementContainerAwareInterface()
    {
        $bag = new RuntimeParameterBag($this->getMockParameterProvider());

        $this->assertInstanceOf(ContainerAwareInterface::class, $bag);
    }

    public function testAllShouldReturnAllParameters()
    {
        $parameters = [
            'foo' => 'bar',
            'fuu' => 'baz',
            'fii' => null,
        ];

        $bag = new RuntimeParameterBag($this->getMockParameterProvider($parameters));

        $this->assertEquals($parameters, $bag->all());
    }

    public function testHasShouldReturnWhetherAParameterExists()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $bag = new RuntimeParameterBag($this->getMockParameterProvider($parameters));

        $this->assertTrue($bag->has('foo'));
        $this->assertFalse($bag->has('bar'));
    }

    public function testHasShouldCheckContainer()
    {
        $parameters = array(
            'foo' => 'bar',
        );

        $bag = new RuntimeParameterBag($this->getMockParameterProvider($parameters));
        $container = $this->createMock(ContainerInterface::class);
        $bag->setContainer($container);
        $container->expects($this->exactly(2))
            ->method('hasParameter')
            ->will($this->returnValueMap([
                ['bar', false],
                ['baz', true],
            ]));

        $this->assertTrue($bag->has('foo'));
        $this->assertFalse($bag->has('bar'));
        $this->assertTrue($bag->has('baz'));
    }

    public function testGetShouldReturnParameterValues()
    {
        $parameters = [
            'foo' => 'bar',
            'fuu' => 'baz',
            'fii' => null,
        ];

        $bag = new RuntimeParameterBag($this->getMockParameterProvider($parameters));

        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertEquals('baz', $bag->get('fuu'));
        $this->assertSame(null, $bag->get('fii'));
    }

    public function testDeinitialize()
    {
        $provider = $this->createMock(ParameterProviderInterface::class);

        $bag = new RuntimeParameterBag($provider);

        $parameters1 = [
            'foo' => 'bar',
            'fuu' => 'baz',
        ];

        $parameters2 = [
            'foo2' => 'bar2',
            'fuu2' => 'baz2',
        ];

        $provider->expects($this->exactly(2))
            ->method('getParametersAsKeyValueHash')
            ->willReturnOnConsecutiveCalls($parameters1, $parameters2);

        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertEquals('baz', $bag->get('fuu'));

        $bag->deinitialize();

        $this->assertEquals('bar2', $bag->get('foo2'));
        $this->assertEquals('baz2', $bag->get('fuu2'));
    }

    public function testGetShouldDeferToContainerForUndefinedParameterWithContainer()
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('getParameter')
            ->with('foo')
            ->willReturn('bar');

        $bag = new RuntimeParameterBag($this->getMockParameterProvider());
        $bag->setContainer($container);

        $this->assertEquals('bar', $bag->get('foo'));
    }

    public function testGetShouldThrowExceptionForUndefinedParameterWithoutContainer()
    {
        $this->expectException(ParameterNotFoundException::class);

        $bag = new RuntimeParameterBag($this->getMockParameterProvider());

        $bag->setContainer(new Container());

        $bag->get('foo');
    }

    public function testGetShouldLogNonexistentParameterWithAvailableLogger()
    {
        $this->expectException(ParameterNotFoundException::class);

        $bag = new RuntimeParameterBag($this->getMockParameterProvider(), $this->getMockRuntimeParameterBagLogger('foo'));

        $bag->setContainer(new Container());

        $bag->get('foo');
    }

    private function getMockParameterProvider(array $parameters = [])
    {
        $provider = $this->createMock(ParameterProviderInterface::class);

        $provider->expects($this->any())
            ->method('getParametersAsKeyValueHash')
            ->willReturn($parameters);

        return $provider;
    }

    private function getMockRuntimeParameterBagLogger($expectedLogArgumentContains)
    {
        $logger = $this->createMock(RuntimeParameterBagLogger::class);

        $logger->expects($this->any())
            ->method('log')
            ->with($this->stringContains($expectedLogArgumentContains));

        return $logger;
    }
}
