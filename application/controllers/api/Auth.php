<?php
use Restserver\Libraries\REST_Controller;
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
//To Solve File REST_Controller not found
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

// use Restserver\Libraries\REST_Controller;
/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 * 
 */


class Auth extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Crud');
        $this->load->library('Authorization_Token');	  

    }
    public function register_post()
	{   
		$token_data['email'] = 'dimasmaspur@gmail.com';
		$token_data['name'] = 'dimas'; 
		$token_data['role'] = 'superadmin'; 
		$token_data['password'] = 'dimas123';

		$tokenData = $this->authorization_token->generateToken($token_data);

		$final = array();
		$final['token'] = $tokenData;
		$final['status'] = 'ok';
 
		$this->response($final); 

	}

    public function verify_post()
	{  
		$headers = $this->input->request_headers(); 
		$decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
        if($decodedToken){
        $this->response($decodedToken);
        }else{
        $this->response('jancuk');
        }
    }
    
    public function test_get(){
        $verify = $this->verify_post();

        if($verify){
            $this->response('eaeaeea');
        }else{
            $this->response('aso');
        }
    }
}