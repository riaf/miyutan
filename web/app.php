<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Services/Amazon.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__.'/../views',
]);

$config = @include(__DIR__ . '/../config.php');

if (!$config) {
    throw new RuntimeException('config.php');
}

$amazon = new Services_Amazon($config['amazon']['api_key'], $config['amazon']['secret']);
$amazon->setLocale($config['amazon']['locale']);

$app->get('/search', function (Application $app, Request $request) {
    $q = $request->get('q');

    return $app->redirect($app['url_generator']->generate('item', [
        'category' => 'Books',
        'keywords' => $q,
    ]));
})
->bind('search');

$app->get('/item/{category}/{keywords}', function(Application $app, Request $request, $category, $keywords) use ($amazon, $config) {
    $response = $amazon->ItemSearch($category, [
        'Keywords'      => $keywords,
        'ResponseGroup' => 'Medium',
        'Sort'          => 'daterank', // Books only
        'AssociateTag'  => $config['amazon']['associate_tag'],
    ]);

    if (!isset($response['Item'])) {
        return 'Error';
    }

    return $app['twig']->render('item.html.twig', [
        'keywords' => $keywords,
        'items'    => $response['Item'],
    ]);
})
->bind('item');

$app->get('/', function (Application $app, Request $request) {
    return $app['twig']->render('index.html.twig');
})
->bind('homepage');

$app->run();

