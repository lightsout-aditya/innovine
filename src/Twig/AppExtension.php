<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('env', [$this, 'getEnvironmentVariable']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('camelToSpace', [$this, 'camelToSpace']),
            new TwigFilter('md5', [$this, 'md5']),
        ];
    }

    public function getEnvironmentVariable($varName)
    {
        return $_ENV[$varName];
    }

    function camelToSpace($str): string
    {
        preg_match_all('/((?:^|[A-Z])[a-z]+)/', $str, $matches);
        return ucfirst(implode(' ', $matches[0]));
    }

    function md5($str): string
    {
        return md5($str);
    }
}