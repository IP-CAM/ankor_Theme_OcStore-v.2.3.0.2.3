<?php
use app\core\App;
use app\models\Callback;
use app\models\Certificate;
use app\models\SeoUrl;

class ControllerCatalogCertificates extends Controller {
    private $data;
	private $error = array();
    protected $typeSeoUrl = 'certificate';

	public function index() {
		$this->load->language('catalog/certificates');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->model_setting_setting->editSetting('', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

                $this->response->redirect($this->url->link('catalog/certificates', 'token=' . $this->session->data['token'], true));
	
		}
		$this->getList();
	}

	public function add() {
		$this->load->language('catalog/certificates');
		$this->document->setTitle($this->language->get('heading_title'));
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $item = new Certificate();
            $item->load($this->request->post);
            $item->save();
            $this->artSaveKeywordSeoUrl($item->id);

			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirectToEdit();
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/certificates');

        $this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $item = Certificate::findOneById($this->request->get['id']);
            $item->load($this->request->post);
            $item->save();
            $this->artSaveKeywordSeoUrl($item->id);

			$this->session->data['success'] = $this->language->get('text_success');
            $this->redirectToEdit();
		}

		$this->getForm();
	}

    protected function redirectToEdit(){
        $params = [
            'token' => $this->session->data['token'],
        ];
        if (isset( $this->request->get['id'])) {
            $params['id'] = $this->request->get['id'];
            $this->response->redirect($this->url->link('catalog/certificates/edit', $params , true));
        }else {
            $this->response->redirect($this->url->link('catalog/certificates', $params , true));
        }

	}

	public function delete() {
		$this->load->language('catalog/certificates');
		$this->document->setTitle($this->language->get('heading_title'));
        $selected = [];
        if (isset($this->request->post['selected'])) {
            $selected = $this->request->post['selected'];
        } elseif ($this->request->get['id']) {
            $selected[] = $this->request->get['id'];
        }

		if (!empty($selected) && $this->validateDelete()) {
			foreach ($selected as $id) {
                Certificate::delete($id);
			}
			$this->session->data['success'] = $this->language->get('text_success');
			$url = '';
			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}
			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}
			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}
			$this->response->redirect($this->url->link('catalog/certificates', 'token=' . $this->session->data['token'] . $url, true));
		}
		$this->getList();
	}

	private function getList() {
        $data = $this->language->all();
        $data = $this->getAlerts($data);
        $data['controller'] = $this;
        $data['breadcrumbs'] = $this->getBreadcrumbs();
        $data['add'] = $this->url->link('catalog/certificates/add', 'token=' . $this->session->data['token'], true);
        $data['delete'] = $this->url->link('catalog/certificates/delete', 'token=' . $this->session->data['token'], true);
        $data['setting'] = $this->url->link('catalog/certificates/setting', 'token=' . $this->session->data['token'], true);

        $data['items'] = Certificate::getListAdmin($data);

        $data['pagination'] = $this->getPagination(count($data['items']));

        //MAIN DATA
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/certificates/list', $data));

	}

	private function getPagination($total) {
	    if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->limit = 150;
        $pagination->url = $this->url->link('catalog/certificates', 'token=' . $this->session->data['token'] . '' . '&page={page}', true);

        return $pagination->render();
    }

	private function getAlerts($data) {
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        return $data;
    }

    private function getBreadcrumbs(){

        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'href'      => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true),
            'text'      => $this->language->get('text_home'),
            'separator' => false
        );

        $breadcrumbs[] = array(
            'href'      => $this->url->link('catalog/certificates', 'token=' . $this->session->data['token'], true),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: '
        );
        return $breadcrumbs;

	}

	private function getForm() { 

		$this->load->language('catalog/certificates');

        $this->document->setTitle($this->language->get('heading_title'));
        $data = [];
        $this->data = &$data;
        if ((isset($this->request->get['id'])) ) {
            $item = Certificate::findOneById($this->request->get['id']);
        } else {
            $item = new Certificate();
        }
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $item->load($this->request->post);
        }

        if (!isset($this->request->get['id'])) {
            $data['action'] = $this->url->link('catalog/certificates/add', 'token=' . $this->session->data['token'], true);
        } else {
            $data['action'] = $this->url->link('catalog/certificates/edit', 'token=' . $this->session->data['token'] . '&id=' . $this->request->get['id'], true);
        }

        $data['item'] = $item;
        $this->data['keyword'] = SeoUrl::findKeyword($this->typeSeoUrl,$item->id);

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_list'] = $this->language->get('text_list');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_no_results'] = $this->language->get('text_no_results');

        $data['column_image'] = $this->language->get('column_image');
        $data['column_title'] = $this->language->get('column_title');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_viewed'] = $this->language->get('column_viewed');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['cancel'] = $this->url->link('catalog/certificates', 'token=' . $this->session->data['token'], true);

        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_setting'] = $this->language->get('button_setting');

        $data = $this->getAlerts($data);

        $data['breadcrumbs'] = $this->getBreadcrumbs();
        $this->loadImages();
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

        $data['controller'] = $this;

		$this->response->setOutput($this->load->view('catalog/certificates/form', $this->data));

	}

    protected function loadImages(){
        $this->load->model('tool/image');
        //IMAGE
        if ($this->data['item']->image && file_exists(DIR_IMAGE . $this->data['item']->image)) {
            $this->data['mainImage'] = $this->model_tool_image->resize($this->data['item']->image, 100, 100);
        } else {
            $this->data['mainImage'] = $this->model_tool_image->resize('placeholder.png', 100, 100);
        }
        // MORE IMAGES
        $this->data['item']->getImages();
        $this->data['moreImages'] = $this->data['item']->images;
        foreach ($this->data['moreImages'] as &$moreImage) {
            $moreImage['thumb'] = $this->model_tool_image->resize($moreImage['src'], 100, 100);
        }
        // no image
        $this->data['noImage'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        $this->data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
	}

	public function setting() {
		$this->load->language('catalog/certificates');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
//		$this->load->model('catalog/certificates');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSetting()) {
			$this->model_setting_setting->editSetting('certificates_setting', $this->request->post);
				if (isset($this->request->post['news_url'])) {
					$this->model_catalog_news->setNewsListUrl($this->request->post['news_url']);
				}	
			$this->session->data['success'] = $this->language->get('text_success');

			$this->cache->delete('news_setting');

			$this->response->redirect($this->url->link('catalog/certificates', 'token=' . $this->session->data['token'], true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['thumb'])) {
			$data['error_thumb'] = $this->error['thumb'];
		} else {
			$data['error_thumb'] = '';
		}

		if (isset($this->error['popup'])) {
			$data['error_popup'] = $this->error['popup'];
		} else {
			$data['error_popup'] = '';
		}

		if (isset($this->error['description_limit'])) {
			$data['error_limit'] = $this->error['description_limit'];
		} else {
			$data['error_limit'] = '';
		}		

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['entry_thumb'] = $this->language->get('entry_thumb');
		$data['entry_popup'] = $this->language->get('entry_popup');
		$data['entry_share'] = $this->language->get('entry_share');
		$data['entry_limit'] = $this->language->get('entry_limit');
		$data['entry_news_url'] = $this->language->get('entry_news_url');

		$data['entry_width'] = $this->language->get('entry_width');
		$data['entry_height'] = $this->language->get('entry_height');

		$data['action'] = $this->url->link('catalog/certificates/setting', 'token=' . $this->session->data['token'], true);
		$data['cancel'] = $this->url->link('catalog/certificates', 'token=' . $this->session->data['token'], true);
	
		$data['breadcrumbs'] = array();
	
		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true),
			'text'      => $this->language->get('text_home'),
			'separator' => false
		);
	
		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('catalog/certificates', 'token=' . $this->session->data['token'], true),
			'text'      => $this->language->get('heading_title'),
			'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('catalog/certificates/setting', 'token=' . $this->session->data['token'], true),
			'text'      => $this->language->get('text_news_setting'),
			'separator' => ' :: '
		);	

		if (isset($this->request->post['news_setting'])) {
			$news_setting = $this->request->post['news_setting'];
		} elseif ($this->config->get('news_setting')) {
			$news_setting = $this->config->get('news_setting');
		} else {
			$news_setting = array();
		}

		if (isset($news_setting['news_thumb_width'])) {
			$data['news_thumb_width'] = $news_setting['news_thumb_width'];
		} else {
			$data['news_thumb_width'] = '';
		}	

		if (isset($news_setting['news_thumb_height'])) {
			$data['news_thumb_height'] = $news_setting['news_thumb_height'];
		} else {
			$data['news_thumb_height'] = '';
		}	

		if (isset($news_setting['news_popup_width'])) {
			$data['news_popup_width'] = $news_setting['news_popup_width'];
		} else {
			$data['news_popup_width'] = '';
		}	

		if (isset($news_setting['news_popup_height'])) {
			$data['news_popup_height'] = $news_setting['news_popup_height'];
		} else {
			$data['news_popup_height'] = '';
		}	

		if (isset($news_setting['description_limit'])) {
			$data['description_limit'] = $news_setting['description_limit'];
		} else {
			$data['description_limit'] = '';
		}

		if (isset($news_setting['news_share'])) {
			$data['news_share'] = $news_setting['news_share'];
		} else {
			$data['news_share'] = '';
		}								

		$news_url = $this->model_catalog_news->getNewsListUrl('information/news');

		if($news_url){
			$data['news_url'] = $news_url;
		}else{
			$data['news_url'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('catalog/news_setting', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/certificates')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/certificates')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
	
		return !$this->error;
	}

	protected function validateSetting() {
		if (!$this->user->hasPermission('modify', 'catalog/certificates')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		$news_setting = $this->request->post['news_setting'];

		if (!$news_setting['news_thumb_width'] || !$news_setting['news_thumb_height']) {
			$this->error['thumb'] = $this->language->get('error_thumb');
		}
	
		if (!$news_setting['news_popup_width'] || !$news_setting['news_popup_height']) {
			$this->error['popup'] = $this->language->get('error_popup');
		}

		if (!$news_setting['description_limit']) {
			$this->error['description_limit'] = $this->language->get('error_description_limit');
		}		
	
        return !$this->error;
	}

}
?>