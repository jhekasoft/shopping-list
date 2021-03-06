<?php

namespace ShoppingList\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use ShoppingList\Model\ShoppingList;
use ShoppingList\Form\ShoppingListForm;

class ShoppingListController extends AbstractActionController
{
    protected $shoppingListTable;
    
    protected function renderList()
    {
        $viewModel = new ViewModel(array(
            'shoppingList' => $this->getShoppingListTable()->fetchAll(),
        ));
        $viewModel->setTemplate('shopping-list/shopping-list/list');
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
        return $viewRender->render($viewModel);
    }
    
    protected function renderEditForm($form)
    {
        $viewModel = new ViewModel(array(
            'editForm' => $form,
        ));
        $viewModel->setTemplate('shopping-list/shopping-list/edit-form');
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
        return $viewRender->render($viewModel);
    }
    
    public function indexAction()
    {
        $config = $this->getServiceLocator()->get('Config');
        $model = new ShoppingList();
        $addForm = new ShoppingListForm();
        $addForm->setInputFilter($model->getInputFilter());
        
        $uri = $this->getRequest()->getUri();
        
        return new ViewModel(array(
            'shoppingList' => $this->getShoppingListTable()->fetchAll(),
            'addForm' => $addForm,
            'webSocketsUlr' => 'ws://' . $uri->getHost() . ':' . $config['web_sockets']['port'],
        ));
    }

    public function addAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new \Exception("Not ajax request");
        }
        
        $form = new ShoppingListForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $model = new ShoppingList();
            $form->setInputFilter($model->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $model->exchangeArray($form->getData());
                $nowDateTime = new \DateTime();
                $model->datetime = $nowDateTime->format(\DateTime::ISO8601);
                $this->getShoppingListTable()->saveItem($model);

                $form->reset();
                return new JsonModel(array(
                    'result' => 'ok',
                    'listHtml' => $this->renderList(),
                    'addFormHtml' => $this->renderEditForm($form),
                ));
            }
            
            return new JsonModel(array(
                'result' => 'fail',
                'addFormHtml' => $this->renderEditForm($form),
            ));
        }
        
        return new JsonModel(array(
            'result' => 'fail'
        ));
    }

    public function getEditFormAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new \Exception("Not ajax request");
        }
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = (int) $request->getPost('id', 0);

            if ($id) {
                try {
                    $item = $this->getShoppingListTable()->getItem($id);
                }
                catch (\Exception $ex) {
                    return new JsonModel(array(
                        'result' => 'fail',
                        'errorDetails' => $ex->getMessage(),
                    ));
                }
                
                $form = new ShoppingListForm();
                $form->bind($item);
                
                return new JsonModel(array(
                    'result' => 'ok',
                    'editFormHtml' => $this->renderEditForm($form),
                ));
            }
        }
        
        return new JsonModel(array(
            'result' => 'fail'
        ));
    }
    
    public function editAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new \Exception("Not ajax request");
        }
        
        $form = new ShoppingListForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = (int) $request->getPost('id', 0);
            try {
                $item = $this->getShoppingListTable()->getItem($id);
            }
            catch (\Exception $ex) {
                return new JsonModel(array(
                    'result' => 'fail',
                    'errorDetails' => $ex->getMessage(),
                ));
            }

            $form->setInputFilter($item->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $item->exchangeArray($form->getData());
                $nowDateTime = new \DateTime();
                $item->datetime = $nowDateTime->format(\DateTime::ISO8601);
                $this->getShoppingListTable()->saveItem($item);

                $form->reset();
                return new JsonModel(array(
                    'result' => 'ok',
                    'listHtml' => $this->renderList(),
                    'addFormHtml' => $this->renderEditForm($form),
                ));
            }
            
            return new JsonModel(array(
                'result' => 'fail',
                'addFormHtml' => $this->renderEditForm($form),
            ));
        }
        
        return new JsonModel(array(
            'result' => 'fail'
        ));
    }

    public function deleteAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new \Exception("Not ajax request");
        }
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = (int) $request->getPost('id', 0);

            if ($id) {
                $this->getShoppingListTable()->deleteItem($id);
                
                return new JsonModel(array(
                    'result' => 'ok',
                    'html' => $this->renderList(),
                ));
            }
        }
        
        return new JsonModel(array(
            'result' => 'fail'
        ));
    }
    
    public function changeStatusAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new \Exception("Not ajax request");
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = (int) $request->getPost('id', 0);
            try {
                $item = $this->getShoppingListTable()->getItem($id);
            }
            catch (\Exception $ex) {
                return new JsonModel(array(
                    'result' => 'fail',
                    'errorDetails' => $ex->getMessage(),
                ));
            }

            $item->status = 'done';

            if ($request->getPost('status') == 'undone') {
                $item->status = 'undone';
            }
            $this->getShoppingListTable()->saveItem($item);

            return new JsonModel(array(
                'result' => 'ok',
                'listHtml' => $this->renderList(),
            ));
        }
        
        return new JsonModel(array(
            'result' => 'fail'
        ));
    }
    
    public function getListAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new \Exception("Not ajax request");
        }
        
        return new JsonModel(array(
            'result' => 'ok',
            'listHtml' => $this->renderList(),
        ));
    }
    
    public function getShoppingListTable()
    {
        if (!$this->shoppingListTable) {
            $sm = $this->getServiceLocator();
            $this->shoppingListTable = $sm->get('ShoppingList\Model\ShoppingListTable');
        }
        return $this->shoppingListTable;
    }

}
