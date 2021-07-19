<?php
/**
 * Twig Environment for GbWeiss Plugin
 *
 * @package GbWeissTwig
 */

namespace GbWeiss\includes;

use Exception;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * Twig Environment Singleton
 */
class TwigEnvironment extends Singleton
{
    /**
     * Twig Loader
     *
     * @var FilesystemLoader
     */
    private $loader;

    /**
     * Twig Environment
     *
     * @var Environment
     */
    private $twig;

    /**
     * Instatiate new Twig Environment
     */
    public function __construct()
    {
        $this->loader = new FilesystemLoader(__DIR__ . '/../templates/');
        $this->twig = new Environment($this->loader, [
        'debug' => true
        ]);
        $this->addTwigExtensions();
    }

    /**
     * Prevent the instance from being cloned (which would create a second instance of it)
     */
    protected function __clone()
    {
    }

    /**
     * Prevent from being unserialized (which would create a second instance of it)
     *
     * @throws Exception If tried to unserialize.
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Add Twig Extensions
     *
     * @return void
     */
    private function addTwigExtensions(): void
    {
        $this->twig->addFunction(new TwigFunction('__', function ($text, $textdomain = 'gbw-woocommerce') {
            return __($text, $textdomain);
        }));
        $this->twig->addFunction(new TwigFunction('settings_fields', function ($group) {
            \settings_fields($group);
        }));
        $this->twig->addFunction(new TwigFunction('do_settings_sections', function ($group) {
            \do_settings_sections($group);
        }));
        $this->twig->addFunction(new TwigFunction('do_settings_fields', function ($section, $page = GbWeiss::OPTIONPAGESLUG) {
            \do_settings_fields($page, $section);
        }));
        $this->twig->addFunction(new TwigFunction('esc_attr', function ($attribute) {
            return \esc_attr($attribute);
        }));
        $this->twig->addFunction(new TwigFunction('get_option', function ($optionName) {
            return \get_option($optionName);
        }));
        $this->twig->addFunction(new TwigFunction('submit_button', function () {
            \submit_button();
        }));
        $this->twig->addExtension(new DebugExtension());
    }

    /**
     * Renders given twig file within environment with given data
     *
     * @param string $file File path to be rendered relative to environment.
     * @param array  $data Array of data to be rendered.
     * @return string Rendered string
     */
    public static function render(string $file, array $data = null): string
    {
      /**
       * Twig Environment
       *
       * @var TwigEnvironment
       */
        $environment = static::getInstance();
        return $environment->twig->render($file, $data);
    }
}