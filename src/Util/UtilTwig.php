<?php

namespace Mcx\StripPacker\Util;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * 03/2021 created
 */
class UtilTwig
{
    /**
     * attention: could be security threat if you use some user-input as part of template path
     *
     * @param string $pathAbsTemplate
     * @param array $vars
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public static function renderTemplate(string $pathAbsTemplate, array $vars)
    {
        $loader = new FilesystemLoader(dirname($pathAbsTemplate));
        $twig = new Environment($loader);
        $template = $twig->load(basename($pathAbsTemplate));

        return $template->render($vars);
    }

}
