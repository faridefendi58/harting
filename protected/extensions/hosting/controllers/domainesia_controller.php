<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;

class DomainesiaController extends BaseController
{
    const COMPANY_ID = 4;

    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/[{package}]', [$this, 'get_paket']);
    }

    public function get_paket($request, $response, $args)
    {
        if (!empty($args['package'])) {
            $params = [
                'hosting_company_id' => self::COMPANY_ID,
                'title' => $args['package']
            ];
            $c_model = new \ExtensionsModel\HostingPlanModel();
            $data = $c_model->getQuery($params);
            if (!is_array($data)) {
                return $this->_container->response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'text/html')
                    ->write('Page not found!');
            }

            return $this->_container->view->render($response, 'hosting.phtml', [
                'name' => $args['package'],
                'data' => $data
            ]);
        }
    }
}