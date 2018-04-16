<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;

class CompaniesController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/view', [$this, 'view']);
        $app->map(['GET', 'POST'], '/create', [$this, 'create']);
        $app->map(['GET', 'POST'], '/update/[{id}]', [$this, 'update']);
        $app->map(['POST'], '/delete/[{id}]', [$this, 'delete']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['view', 'create', 'update', 'delete'],
                'users'=> ['@'],
            ],
            ['deny',
                'users' => ['*'],
            ],
        ];
    }

    public function view($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\HostingCompanyModel();
        $companies = $model->getData();

        return $this->_container->module->render($response, 'hostings/view.html', [
            'companies' => $companies
        ]);
    }

    public function create($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $languages = \ExtensionsModel\PostLanguageModel::model()->findAll();
        $model = new \ExtensionsModel\PostModel('create');
        $categories = \ExtensionsModel\PostCategoryModel::model()->findAll();
        $post_id = 0;

        if (isset($_POST['Post'])){
            $model->status = $_POST['Post']['status'];
            $model->allow_comment = ($_POST['Post']['allow_comment'] == 'on')? 1 : 0;
            $model->post_type = $_POST['Post']['post_type'];
            $model->author_id = $this->_user->id;
            if (!empty($_POST['Post']['tags'])) {
                $model->tags = $_POST['Post']['tags'];
            }
            $model->created_at = date('Y-m-d H:i:s');
            $model->updated_at = date('Y-m-d H:i:s');
            $create = \ExtensionsModel\PostModel::model()->save(@$model);
            if ($create > 0) {
                $post_content = \ExtensionsModel\PostContentModel::model();
                foreach ($_POST['PostContent']['title'] as $lang => $title) {
                    if (!empty($title) && !empty($_POST['PostContent']['content'][$lang])) {
                        $model2 = new \ExtensionsModel\PostContentModel;
                        $model2->post_id = $model->id;
                        $model2->title = $title;
                        if (!empty($_POST['PostContent']['slug'][$lang])){
                            $cek_slug = $post_content->findByAttributes(['slug'=>$_POST['PostContent']['slug'][$lang]]);
                            if ($cek_slug instanceof \RedBeanPHP\OODBBean) {
                                $model2->slug = $_POST['PostContent']['slug'][$lang].'2';
                            } else {
                                $model2->slug = $_POST['PostContent']['slug'][$lang];
                            }
                        } else
                            $model2->slug = $model->createSlug($title);

                        $model2->language = $lang;
                        $model2->content = $_POST['PostContent']['content'][$lang];
                        $model2->meta_keywords = $_POST['PostContent']['meta_keywords'][$lang];
                        $model2->meta_description = $_POST['PostContent']['meta_description'][$lang];
                        $model2->created_at = date("Y-m-d H:i:s");
                        $model2->updated_at = date("Y-m-d H:i:s");
                        $create_content = $post_content->save($model2);
                    }
                }
                $post_in_category = \ExtensionsModel\PostInCategoryModel::model();
                if (!empty($_POST['Post']['post_category']) && is_array($_POST['Post']['post_category'])) {
                    foreach ($_POST['Post']['post_category'] as $ci => $category_id) {
                        $model3 = new \ExtensionsModel\PostInCategoryModel();
                        $model3->post_id = $model->id;
                        $model3->category_id = $category_id;
                        $model3->created_at = date("Y-m-d H:i:s");
                        $post_in_category->save($model3);
                    }
                }

                $message = 'Your post is successfully created.';
                $success = true;
                $post_id = $model->id;
            } else {
                $message = 'Failed to create new post.';
                $success = false;
            }
        }

        return $this->_container->module->render($response, 'hostings/create.html', [
            'languages' => $languages,
            'status_list' => $model->getListStatus(),
            'categories' => $categories,
            'message' => ($message) ? $message : null,
            'success' => $success,
            'post_id' => $post_id
        ]);
    }

    public function update($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (empty($args['id']))
            return false;

        $model = \ExtensionsModel\HostingCompanyModel::model()->findByPk($args['id']);
        $hcmodel = new \ExtensionsModel\HostingCompanyModel();
        $detail = $hcmodel->getDetail($args['id']);

        if (isset($_POST['HostingCompany'])){
            $model->status = $_POST['HostingCompany']['status'];
            $model->updated_at = date('Y-m-d H:i:s');
            $update = \ExtensionsModel\HostingCompanyModel::model()->update($model);
            if ($update) {
                $detail = $hcmodel->getDetail($model->id);
                $message = 'Your post is successfully updated.';
                $success = true;
            } else {
                $message = 'Failed to update new post.';
                $success = false;
            }
        }

        return $this->_container->module->render($response, 'hostings/update.html', [
            'model' => $model,
            'detail' => $detail,
            'message' => ($message) ? $message : null,
            'success' => $success
        ]);
    }

    public function delete($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $model = \ExtensionsModel\PostModel::model()->findByPk($args['id']);
        $delete = \ExtensionsModel\PostModel::model()->delete($model);
        if ($delete) {
            $delete2 = \ExtensionsModel\PostContentModel::model()->deleteAllByAttributes(['post_id'=>$args['id']]);
            $delete3 = \ExtensionsModel\PostInCategoryModel::model()->deleteAllByAttributes(['post_id'=>$args['id']]);
            $message = 'Your page is successfully created.';
            echo true;
        }
    }
}