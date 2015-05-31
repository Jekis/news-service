<?php

use Jekis\NewsService\Security\Authentication;
use Jekis\ServiceBuilder;
use Symfony\Component\HttpFoundation\Request;
use Jekis\NewsService\Controller\NewsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();


ServiceBuilder::registerServices($app, array(
        'dbal' => array(
            'db.options' => array(
                'driver'   => 'pdo_mysql',
                'dbname'   => 'newsservice',
                'host'     => 'localhost',
                'user'     => 'root',
                'password' => 'root',
            ),
        ),
        'redis' => array(
            'predis.options' => array(
                'prefix'  => 'silexnewsservice:',
            ),
        )
    ));

$app['news_service'] = $app->share(function () use ($app) {
        return new NewsController($app['db']);
    });
$app['authentication'] = $app->share(function () use ($app) {
        return new Authentication($app['predis']);
    });


$app->post('/news/{hash}/get', function(Silex\Application $app, Request $request, $hash) {
        if (!$app['authentication']->authenticate($hash)) {
            throw new HttpException(401, 'Unauthorized');
        }

        if ($id = $request->get('id')) {
            if ($news = $app['news_service']->get($id)) {
                return $app->json($news);
            }
        }

        throw new NotFoundHttpException('News not found.');
    });

$app->post('/news/{hash}/list', function(Silex\Application $app, Request $request, $hash) {
        if (!$app['authentication']->authenticate($hash)) {
            throw new HttpException(401, 'Unauthorized');
        }

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);
        $sort = $request->get('sort', '-created');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');
        $news = $app['news_service']->getList($limit, $offset, $sort, $startDate, $endDate);

        return $app->json($news);
    });

$app->post('/news/{hash}/push', function(Silex\Application $app, Request $request, $hash) {
        if (!$app['authentication']->authenticate($hash)) {
            throw new HttpException(401, 'Unauthorized');
        }

        try {
            if ($news = $app['news_service']->push($request->request->all(), $request->get('id'))) {
                return $app->json($news);
            }
        } catch (\InvalidArgumentException $ex) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException($ex->getMessage(), $ex);
        }

        throw new NotFoundHttpException('News not found.');
    });

$app->error(function (\Exception $e, $code) use ($app) {
        return $app->json(array(
                'error' => $e->getMessage(),
                'code' => $code,
            ));
    });

$app->run();
