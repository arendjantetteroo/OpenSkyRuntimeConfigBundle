<?php

namespace OpenSky\Bundle\RuntimeConfigBundle\Twig\Extension;

use OpenSky\Bundle\RuntimeConfigBundle\Service\RuntimeParameterBag;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RuntimeConfigExtension extends AbstractExtension
{
    protected $runtimeConfig;

    /**
     * Constructor.
     *
     * @param RuntimeParameterBag $runtimeConfig
     */
    public function __construct(RuntimeParameterBag $runtimeConfig)
    {
        $this->runtimeConfig = $runtimeConfig;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('runtime_config', [$this, 'getRuntimeConfig']),
        ];
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'runtime_config';
    }

    public function getRuntimeConfig($name)
    {
        try {
            return $this->runtimeConfig->get($name);
        } catch (ParameterNotFoundException|\InvalidArgumentException $e) {
            return null;
        }
    }
}
