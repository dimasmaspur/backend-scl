<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */

	function __construct()
    {
        // Construct the parent class
		parent::__construct();
		$this->load->library('ciqrcode');


    }
	public function index()
	{
		$config['cacheable']	= true; //boolean, the default is true
		$config['cachedir']		= './assets/qrcode/'; //string, the default is application/cache/
		$config['imagedir']		= './assets/qrcode/'; //string, the default is application/cache/
		$config['quality']		= true; //boolean, the default is true
		$config['size']			= '1024'; //interger, the default is 1024
		$config['black']		= array(224,255,255); // array, default is array(255,255,255)
		$config['white']		= array(70,130,180); // array, default is array(0,0,0)
		$this->ciqrcode->initialize($config);

		$params['data'] = 'This is a text to encode become QR Code';
		$params['level'] = 'H';
		$params['size'] = 5;
		$params['savename'] = FCPATH.$config['imagedir'].'tes.png';
		$this->ciqrcode->generate($params);

		echo '<img src="'.base_url().'tes.png" />';
	}
}
