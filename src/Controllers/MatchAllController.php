<?php

namespace Bolt\Extension\Postitief\MatchAll\Controllers;

use Bolt\Config;
use Bolt\Controller\Frontend;
use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;

class MatchAllController
{
    /**
     * The bolt application
     *
     * @var
     */
    protected $app;

    /**
     * The path to the config.yml
     *
     * @var string
     */
    protected $configPath = __DIR__ . '/../../config/config.yml';

    public function matchAll(Request $request, $slug, Application $application)
    {
        $this->app = $application;

//        var_dump($this->getConfig());die;
        $contentTypes = ['provincies', 'vervolg', 'plaats'];

        foreach($contentTypes as $contentType)
        {
            if(null !== $result = $this->record($request, $contentType, $slug)) {
                return $result;
            }
        }

        $this->abort(Response::HTTP_NOT_FOUND, "Page $slug not found.");
        return null;
    }

    protected function getConfig()
    {
        return Yaml::parse(file_get_contents($this->configPath));
    }

    public function record(Request $request, $contenttypeslug, $slug = '')
    {
        $contenttype = $this->getContentType($contenttypeslug);

        // Perhaps we don't have a slug. Let's see if we can pick up the 'id', instead.
        if (empty($slug)) {
            $slug = $request->get('id');
        }

        $slug = $this->app['slugify']->slugify($slug);

        // First, try to get it by slug.
        $content = $this->getContent($contenttype['slug'], ['slug' => $slug, 'returnsingle' => true, 'log_not_found' => !is_numeric($slug)]);

        if (!$content && is_numeric($slug)) {
            // And otherwise try getting it by ID
            $content = $this->getContent($contenttype['slug'], ['id' => $slug, 'returnsingle' => true]);
        }

        // No content, no page!
        if (!$content) {
            return null;
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $this->templateChooser()->record($content);

        // Setting the canonical URL.
        if ($content->isHome() && ($template === $this->getOption('general/homepage_template'))) {
            $url = $this->app['resources']->getUrl('rooturl');
        } else {
            $url = $this->app['resources']->getUrl('rooturl') . ltrim($content->link(), '/');
        }
        $this->app['resources']->setUrl('canonicalurl', $url);

        // Setting the editlink
        $this->app['editlink'] = $this->generateUrl('editcontent', ['contenttypeslug' => $contenttype['slug'], 'id' => $content->id]);
        $this->app['edittitle'] = $content->getTitle();

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = [
            'record'                      => $content,
            $contenttype['singular_slug'] => $content,
        ];

        return $this->render($template, [], $globals);
    }

    /**
     * Get the contenttype as an array, based on the given slug.
     *
     * @param string $slug
     *
     * @return boolean|array
     */
    protected function getContentType($slug)
    {
        return $this->storage()->getContentType($slug);
    }

    /**
     * Returns the Entity Manager.
     *
     * @return \Bolt\Storage\EntityManager
     */
    protected function storage()
    {
        return $this->app['storage'];
    }

    /**
     * Shortcut to abort the current request by sending a proper HTTP error.
     *
     * @param integer $statusCode The HTTP status code
     * @param string  $message    The status message
     * @param array   $headers    An array of HTTP headers
     *
     * @throws HttpExceptionInterface
     */
    protected function abort($statusCode, $message = '', array $headers = [])
    {
        $this->app->abort($statusCode, $message, $headers);
    }

    /**
     * Shortcut for {@see \Bolt\Legacy\Storage::getContent()}
     *
     * @param string $textquery
     * @param array  $parameters
     * @param array  $pager
     * @param array  $whereparameters
     *
     * @return \Bolt\Legacy\Content|\Bolt\Legacy\Content[]
     *
     * @see \Bolt\Legacy\Storage::getContent()
     */
    protected function getContent($textquery, $parameters = [], &$pager = [], $whereparameters = [])
    {
        return $this->storage()->getContent($textquery, $parameters, $pager, $whereparameters);
    }

    /**
     * Return the Bolt\TemplateChooser provider.
     *
     * @return \Bolt\TemplateChooser
     */
    protected function templateChooser()
    {
        return $this->app['templatechooser'];
    }

    /**
     * Shortcut for {@see UrlGeneratorInterface::generate}
     *
     * @param string $name          The name of the route
     * @param array  $params        An array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string
     */
    protected function generateUrl($name, $params = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        /** @var UrlGeneratorInterface $generator */
        $generator = $this->app['url_generator'];

        return $generator->generate($name, $params, $referenceType);
    }

    /**
     * Renders a template
     *
     * @param string $template  the template name
     * @param array  $variables array of context variables
     * @param array  $globals   array of global variables
     *
     * @return \Bolt\Response\BoltResponse
     */
    protected function render($template, array $variables = [], array $globals = [])
    {
        return $this->app['render']->render($template, $variables, $globals);
    }
}