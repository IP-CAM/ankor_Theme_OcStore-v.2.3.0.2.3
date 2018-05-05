<?php
class ControllerExtensionModulePridesMain extends Controller {

	public function index($setting = []) {
        $this->load->model('catalog/prides');
        $this->load->model('tool/image');
        $items = $this->model_catalog_prides->getListForMain(7);
        $imgList = 'list_main_prides.jpg';
        $data['imgList'] = $this->model_tool_image->resize($imgList, 273, 273);
        foreach ($items as $result) {

            if($result['image']){
                $image = $this->model_tool_image->resize($result['image'], 273, 273);
            }else{
                $image = false;
            }

            $data['items'][] = array(
                'title' => $result['title'],
                'titleOrigin' => $result['titleOrigin'],
                'thumb' => $image,
                'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES,
                    'UTF-8')), 0, 90),
                'href' => $this->url->link('information/prides/info', 'id=' . $result['id']),
            );
        }
        $data['linkList'] = $this->url->link('information/prides');
        $data['title'] = 'Наша гордость';
        if ($items) {
            return $this->load->view('extension/module/prides_main', $data);
        }
	}
}
